<?php

namespace Modules\Stock\Tests\Feature;

use Modules\Core\Models\User;
use Modules\Grh\Models\Employe;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Services\EntreeStockService;
use Modules\Stock\Services\SortieStockService;

class AlerteEtRapportTest extends StockTestCase
{
    protected Magasin $magasin;

    protected Employe $employe;

    protected User $compteResponsable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->magasin = $this->creerMagasin();
        $this->employe = Employe::factory()->create();
        $this->compteResponsable = User::factory()->create(['dossier_employe_id' => $this->employe->id]);

        $this->magasin->responsables()->create([
            'employe_id' => $this->employe->id,
            'role' => 'principal',
            'date_debut' => now()->toDateString(),
        ]);
    }

    protected function sortir(int $articleId, int $quantite): void
    {
        app(SortieStockService::class)->creer([
            'article_id' => $articleId,
            'magasin_id' => $this->magasin->id,
            'quantite' => $quantite,
            'type_cible' => 'EMPLOYE',
            'cible_id' => $this->employe->id,
        ], $this->utilisateur->id);
    }

    public function test_une_sortie_sous_le_seuil_notifie_les_responsables(): void
    {
        $article = $this->creerConsommable(['seuil_alerte' => 5]);

        app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 10,
            'cout_unitaire' => 100,
        ], $this->utilisateur->id);

        $this->sortir($article->id, 6); // reste 4 <= seuil 5

        $notification = $this->compteResponsable->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('ALERTE', $notification->data['niveau']);
    }

    public function test_une_rupture_notifie_les_responsables(): void
    {
        $article = $this->creerConsommable(['seuil_alerte' => 2]);

        app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 3,
            'cout_unitaire' => 100,
        ], $this->utilisateur->id);

        $this->sortir($article->id, 3); // reste 0

        $niveaux = $this->compteResponsable->notifications()->get()
            ->pluck('data.niveau');

        $this->assertContains('RUPTURE', $niveaux);
    }

    public function test_l_ecran_alertes_et_le_marquage_comme_lu(): void
    {
        $this->get(route('stock.alertes.index'))->assertOk();

        $article = $this->creerConsommable(['seuil_alerte' => 5]);
        app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 6,
            'cout_unitaire' => 100,
        ], $this->utilisateur->id);
        $this->sortir($article->id, 3);

        $this->actingAs($this->compteResponsable);

        $reponse = $this->getJson(route('stock.alertes.data'))->assertOk()->json();
        $this->assertSame(1, $reponse['total']);

        $this->postJson(route('stock.notifications.lire-tout'))->assertOk();
        $this->assertSame(0, $this->compteResponsable->unreadNotifications()->count());
    }

    public function test_les_quatre_rapports_repondent(): void
    {
        $article = $this->creerConsommable();
        app(EntreeStockService::class)->enregistrerEntree([
            'article_id' => $article->id,
            'magasin_id' => $this->magasin->id,
            'quantite' => 5,
            'cout_unitaire' => 100,
        ], $this->utilisateur->id);
        $this->sortir($article->id, 2);

        $this->get(route('stock.rapports.index'))->assertOk();

        $entrees = $this->getJson(route('stock.rapports.data', 'entrees'))->assertOk()->json();
        $this->assertSame(1, $entrees['total']);

        $sorties = $this->getJson(route('stock.rapports.data', 'sorties'))->assertOk()->json();
        $this->assertSame(1, $sorties['total']);

        $this->getJson(route('stock.rapports.data', 'transferts'))->assertOk();

        $stock = $this->getJson(route('stock.rapports.data', 'stock'))->assertOk()->json();
        $this->assertSame(1, $stock['total']);
        $this->assertSame(3, $stock['rows'][0]['quantite']);

        // Un rapport inconnu est refusé.
        $this->getJson(url('stock/rapports/inconnu/data'))->assertNotFound();
    }
}
