<?php

namespace Modules\Stock\Tests\Feature;

use Modules\Core\Models\User;
use Modules\Grh\Models\Employe;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockLot;
use Modules\Stock\Models\StockTransfert;
use Modules\Stock\Services\EntreeStockService;
use Modules\Stock\Services\TransfertService;

class TransfertTest extends StockTestCase
{
    protected Magasin $source;

    protected Magasin $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = $this->creerMagasin(['code' => 'MAG-SRC']);
        $this->destination = $this->creerMagasin(['code' => 'MAG-DST']);
    }

    protected function approvisionner(int $articleId, int $quantite, float $cout, string $date = '2026-01-01'): void
    {
        app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $articleId,
            'magasin_id' => $this->source->id,
            'quantite' => $quantite,
            'cout_unitaire' => $cout,
            'date_entree' => $date,
        ], $this->utilisateur->id);
    }

    public function test_l_ecran_des_transferts_s_affiche(): void
    {
        $this->get(route('stock.transferts.index'))->assertOk();
    }

    public function test_creation_d_un_transfert_en_attente_avec_notification(): void
    {
        $article = $this->creerConsommable();

        // Un responsable en cours sur le magasin destination, avec un compte.
        $employe = Employe::factory()->create();
        $compte = User::factory()->create(['dossier_employe_id' => $employe->id]);
        $this->destination->responsables()->create([
            'employe_id' => $employe->id,
            'role' => 'principal',
            'date_debut' => now()->toDateString(),
        ]);

        $this->postJson(route('stock.transferts.store'), [
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->destination->id,
            'article_id' => $article->id,
            'quantite' => 5,
            'motif_creation' => 'Rééquilibrage des réserves',
        ])->assertOk();

        $transfert = StockTransfert::first();
        $this->assertSame('EN_ATTENTE', $transfert->statut);
        $this->assertSame('TRF-'.date('Y').'-0001', $transfert->numero_transfert);

        // F8 — notification in-app au responsable de la destination.
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $compte->id,
            'notifiable_type' => $compte->getMorphClass(),
        ]);
    }

    public function test_source_et_destination_doivent_differer(): void
    {
        $article = $this->creerConsommable();

        // RG-F5-01
        $this->postJson(route('stock.transferts.store'), [
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->source->id,
            'article_id' => $article->id,
            'quantite' => 5,
            'motif_creation' => 'Erreur volontaire',
        ])->assertStatus(422);
    }

    public function test_validation_double_mouvement_et_cout_fifo_conserve(): void
    {
        $article = $this->creerConsommable();
        $this->approvisionner($article->id, 5, 100, '2026-01-01');
        $this->approvisionner($article->id, 5, 200, '2026-02-01');

        $transfert = app(TransfertService::class)->creer([
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->destination->id,
            'article_id' => $article->id,
            'quantite' => 8,
            'motif_creation' => 'Rééquilibrage des réserves',
        ], $this->utilisateur->id);

        $this->postJson(route('stock.transferts.valider', $transfert))->assertOk();

        $transfert->refresh();
        $this->assertSame('VALIDE', $transfert->statut);
        $this->assertNotNull($transfert->mouvement_sortant_id);
        $this->assertNotNull($transfert->mouvement_entrant_id);

        // Projections des deux magasins.
        $this->assertSame(2, StockArticleMagasin::where('magasin_id', $this->source->id)->value('quantite_actuelle'));
        $this->assertSame(8, StockArticleMagasin::where('magasin_id', $this->destination->id)->value('quantite_actuelle'));

        // RG-F5-06 — les lots destination reprennent le coût FIFO source :
        // 5 unités à 100 et 3 unités à 200.
        $lotsDestination = StockLot::where('magasin_id', $this->destination->id)
            ->orderBy('cout_unitaire')
            ->get();

        $this->assertCount(2, $lotsDestination);
        $this->assertSame([5, 3], $lotsDestination->pluck('quantite_restante')->all());
        $this->assertSame([100.0, 200.0], $lotsDestination->pluck('cout_unitaire')->map(fn ($c) => (float) $c)->all());

        // Valorisation : source 2×200 = 400, destination 5×100 + 3×200 = 1100.
        $this->assertSame(400.0, (float) StockArticleMagasin::where('magasin_id', $this->source->id)->value('valeur_stock_fifo'));
        $this->assertSame(1100.0, (float) StockArticleMagasin::where('magasin_id', $this->destination->id)->value('valeur_stock_fifo'));
    }

    public function test_validation_refusee_si_stock_insuffisant(): void
    {
        $article = $this->creerConsommable();
        $this->approvisionner($article->id, 3, 100);

        $transfert = app(TransfertService::class)->creer([
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->destination->id,
            'article_id' => $article->id,
            'quantite' => 10,
            'motif_creation' => 'Quantité excessive',
        ], $this->utilisateur->id);

        // RG-F5-03 — contrôle à la validation, pas à la création.
        $this->postJson(route('stock.transferts.valider', $transfert))->assertStatus(422);
        $this->assertSame('EN_ATTENTE', $transfert->refresh()->statut);
    }

    public function test_rejet_avec_motif(): void
    {
        $article = $this->creerConsommable();

        $transfert = app(TransfertService::class)->creer([
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->destination->id,
            'article_id' => $article->id,
            'quantite' => 2,
            'motif_creation' => 'Demande de dépannage',
        ], $this->utilisateur->id);

        $this->postJson(route('stock.transferts.rejeter', $transfert), [
            'motif' => 'Stock nécessaire sur place',
        ])->assertOk();

        $this->assertSame('REJETE', $transfert->refresh()->statut);
        $this->assertSame('Stock nécessaire sur place', $transfert->motif_rejet);
    }

    public function test_annulation_reservee_au_createur_ou_admin(): void
    {
        $article = $this->creerConsommable();

        $transfert = app(TransfertService::class)->creer([
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->destination->id,
            'article_id' => $article->id,
            'quantite' => 2,
            'motif_creation' => 'Demande de dépannage',
        ], $this->utilisateur->id);

        // Gate::before rend tout le monde admin dans ces tests : la règle
        // créateur-ou-admin se vérifie au niveau du service.
        $autreUtilisateur = User::factory()->create();

        try {
            app(TransfertService::class)->annuler($transfert, $autreUtilisateur->id, estAdmin: false);
            $this->fail('Une RegleMetierException était attendue.');
        } catch (\Modules\Stock\Exceptions\RegleMetierException) {
            $this->assertSame('EN_ATTENTE', $transfert->refresh()->statut);
        }

        // Le créateur, lui, peut annuler.
        app(TransfertService::class)->annuler($transfert, $this->utilisateur->id, estAdmin: false);
        $this->assertSame('ANNULE', $transfert->refresh()->statut);
    }

    public function test_un_transfert_termine_est_immuable(): void
    {
        $article = $this->creerConsommable();
        $this->approvisionner($article->id, 5, 100);

        $transfert = app(TransfertService::class)->creer([
            'magasin_source_id' => $this->source->id,
            'magasin_destination_id' => $this->destination->id,
            'article_id' => $article->id,
            'quantite' => 2,
            'motif_creation' => 'Demande de dépannage',
        ], $this->utilisateur->id);

        app(TransfertService::class)->valider($transfert, $this->utilisateur->id);

        $this->postJson(route('stock.transferts.valider', $transfert))->assertStatus(422);
        $this->postJson(route('stock.transferts.rejeter', $transfert), ['motif' => 'Trop tard pour rejeter'])->assertStatus(422);
        $this->postJson(route('stock.transferts.annuler', $transfert))->assertStatus(422);
    }
}
