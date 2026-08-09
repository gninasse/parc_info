<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\ChronologieBonCommande;
use Modules\Achat\Services\ReceptionsBonCommande;
use Modules\Achat\Services\SignauxService;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Magasin;
use Tests\TestCase;

/**
 * BR-04 — le rapprochement BL ↔ saisie : l'écart entre ce que le bordereau
 * du fournisseur ANNONCE et ce qui est réellement COMPTÉ au quai.
 *
 * Aujourd'hui cet écart se perd dans une observation en texte libre, donc il
 * n'est ni imprimable proprement sur la pièce de réclamation, ni agrégeable :
 * le fournisseur qui annonce 10 et livre 8 chaque trimestre reste invisible.
 *
 * LE critère de ce lot, celui que toute la suite protège : l'écart DOCUMENTE
 * et SIGNALE, il ne compte pas. Les reliquats d'Achat ne connaissent que le
 * compté (RGC-02 inchangé). C'est précisément ce qui permet au magasinier de
 * le déclarer sans risque — et donc de le déclarer.
 */
class EcartsBlTest extends TestCase
{
    use RefreshDatabase;

    private User $magasinier;

    private Magasin $magasin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder::class);
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);
        $this->seed(\Modules\Achat\Database\Seeders\AchatPermissionsSeeder::class);
        $this->seed(\Modules\Achat\Database\Seeders\AchatParametresSeeder::class);

        $this->magasinier = User::create([
            'name' => 'Salamata Magasinier', 'last_name' => 'Test', 'user_name' => 'magasinier_br4',
            'email' => 'magasinier-br4@example.com', 'password' => bcrypt('password'),
        ]);
        $this->magasinier->assignRole('Magasinier');
        $this->magasinier->givePermissionTo('achat.bons_commande.index');

        $this->magasin = Magasin::factory()->create();
    }

    /** La charge d'un brouillon d'entrée : 8 comptés là où le BL en annonce 10. */
    private function charge(Article $article, array $surcharges = []): array
    {
        return array_merge([
            'magasin_id' => $this->magasin->id,
            'date_document' => now()->toDateString(),
            'nature' => 'livraison',
            'observation_type' => Entree::OBSERVATION_ECART_BL,
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 8, 'cout_unitaire' => 42000],
            ],
            'ecarts_bl' => [
                [
                    'article_id' => $article->id,
                    'quantite_annoncee_bl' => 10,
                    'quantite_comptee' => 8,
                    'motif' => 'manquant',
                ],
            ],
        ], $surcharges);
    }

    private function enregistrer(array $charge)
    {
        return $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.store'), $charge);
    }

    // ── Saisie : facultative, mais cohérente ───────────────────────────────

    public function test_un_ecart_se_saisit_avec_son_motif(): void
    {
        $article = Article::factory()->consommable()->create(['nom' => 'Toner 26A']);

        $reponse = $this->enregistrer($this->charge($article))->assertOk();

        $entree = Entree::query()->findOrFail($reponse->json('data.id'));
        $ecarts = $entree->ecartsDeclares();

        $this->assertCount(1, $ecarts);
        // Comparaison numérique : un entier rond ressort du JSON en int, ce
        // qui n'a aucune importance pour un nombre de cartons.
        $this->assertEqualsWithDelta(10, $ecarts[0]['quantite_annoncee_bl'], 0.001);
        $this->assertEqualsWithDelta(8, $ecarts[0]['quantite_comptee'], 0.001);
        $this->assertSame('manquant', $ecarts[0]['motif']);
        // La désignation vient du CATALOGUE, jamais du client : un POST forgé
        // ne fait pas signer au livreur un libellé de son choix.
        $this->assertSame('Toner 26A', $ecarts[0]['designation']);
    }

    public function test_la_designation_envoyee_par_le_client_est_ignoree(): void
    {
        $article = Article::factory()->consommable()->create(['nom' => 'Toner 26A']);

        $charge = $this->charge($article);
        $charge['ecarts_bl'][0]['designation'] = 'Lingots d\'or 24 carats';

        $reponse = $this->enregistrer($charge)->assertOk();

        $this->assertSame(
            'Toner 26A',
            Entree::query()->findOrFail($reponse->json('data.id'))->ecartsDeclares()[0]['designation']
        );
    }

    public function test_l_ecart_reste_facultatif_au_quai(): void
    {
        $article = Article::factory()->consommable()->create();

        // Rien n'est déclaré : la réception passe exactement comme avant.
        // Un formulaire qui freine au comptoir n'est jamais rempli.
        $charge = $this->charge($article, ['observation_type' => 'livraison_conforme']);
        unset($charge['ecarts_bl']);

        $reponse = $this->enregistrer($charge)->assertOk();

        $this->assertFalse(
            Entree::query()->findOrFail($reponse->json('data.id'))->aUnEcartBl()
        );
    }

    public function test_un_ecart_exige_le_motif_d_observation_ecart_bl(): void
    {
        $article = Article::factory()->consommable()->create();

        // Déclarer des écarts sous « Livraison conforme » produirait un
        // bordereau qui se contredit lui-même.
        $this->enregistrer($this->charge($article, ['observation_type' => 'livraison_conforme']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ecarts_bl');
    }

    public function test_un_ecart_porte_sur_un_article_du_bon(): void
    {
        $article = Article::factory()->consommable()->create();
        $etranger = Article::factory()->consommable()->create();

        $charge = $this->charge($article);
        $charge['ecarts_bl'][0]['article_id'] = $etranger->id;

        $this->enregistrer($charge)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ecarts_bl.0.article_id');
    }

    public function test_deux_quantites_egales_ne_sont_pas_un_ecart(): void
    {
        $article = Article::factory()->consommable()->create();

        $charge = $this->charge($article);
        $charge['ecarts_bl'][0]['quantite_annoncee_bl'] = 8;

        $this->enregistrer($charge)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ecarts_bl.0.quantite_comptee');
    }

    public function test_un_motif_inconnu_est_refuse(): void
    {
        $article = Article::factory()->consommable()->create();

        $charge = $this->charge($article);
        $charge['ecarts_bl'][0]['motif'] = 'pas_content';

        $this->enregistrer($charge)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ecarts_bl.0.motif');
    }

    public function test_corriger_un_ecart_l_efface_du_dossier(): void
    {
        $article = Article::factory()->consommable()->create();

        $id = $this->enregistrer($this->charge($article))->assertOk()->json('data.id');

        // Le magasinier s'était trompé : il retire l'écart. Le bordereau ne
        // doit plus le mentionner — un écart fantôme se retournerait contre
        // l'établissement.
        $charge = $this->charge($article, ['observation_type' => 'livraison_conforme']);
        $charge['ecarts_bl'] = [];

        $this->actingAs($this->magasinier)
            ->putJson(route('stock.entrees.update', $id), $charge)
            ->assertOk();

        $this->assertFalse(Entree::query()->findOrFail($id)->aUnEcartBl());
    }

    // ── LE critère : aucun effet sur les compteurs (RGC-02) ────────────────

    public function test_l_ecart_ne_touche_aucun_compteur_d_achat(): void
    {
        $article = Article::factory()->consommable()->create(['nom' => 'Toner 26A']);
        $bon = BonCommande::factory()->valide()->create();

        $ligneBc = LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'designation' => 'Toner 26A',
            'nature' => 'consommable',
            'quantite' => 20,
            'quantite_livree' => 0,
            'prix_unitaire_ht' => 42000,
        ]);

        $charge = $this->charge($article, [
            'bon_commande_id' => $bon->id,
            'fournisseur_id' => $bon->fournisseur_id,
        ]);

        $id = $this->enregistrer($charge)->assertOk()->json('data.id');

        $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.valider', $id), ['jeton' => 'jeton-br4'])
            ->assertOk();

        // Le BL annonçait 10, on a compté 8 : c'est 8 qui entre en stock et 8
        // qui sortent du reste à livrer. Le 10 annoncé n'existe pour aucun
        // compteur — sinon on paierait deux cartons qu'on n'a jamais reçus.
        $ligneBc->refresh();
        $this->assertEqualsWithDelta(8, (float) $ligneBc->quantite_livree, 0.001);
        $this->assertEqualsWithDelta(12, (float) $ligneBc->reste, 0.001);
        $this->assertSame(BonCommande::STATUT_PARTIEL, $bon->fresh()->statut);
    }

    // ── Le bordereau : la pièce de réclamation ─────────────────────────────

    public function test_le_bordereau_porte_le_bloc_des_ecarts(): void
    {
        $article = Article::factory()->consommable()->create(['nom' => 'Toner 26A']);

        $id = $this->enregistrer($this->charge($article))->assertOk()->json('data.id');

        $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.valider', $id), ['jeton' => 'jeton-br4b'])
            ->assertOk();

        $entree = Entree::query()->findOrFail($id);

        $html = view('stock::pdf.bordereau_reception', [
            'entree' => $entree->load(['magasin', 'fournisseur', 'lignes.article', 'valideur:id,name']),
            'quantitatives' => $entree->lignes,
            'unites' => collect(),
            'blFournisseurs' => collect(),
            'afficherCouts' => false,
            'ecarts' => $entree->ecartsDeclares(),
            'contrePassation' => null,
            'qr' => null,
            'genereLe' => now(),
        ])->render();

        $this->assertStringContainsString('Écarts constatés à la réception', $html);
        $this->assertStringContainsString('Toner 26A', $html);
        $this->assertStringContainsString('Manquant', $html);
        // L'écart signé : -2. C'est ce chiffre que le livreur contresigne.
        $this->assertStringContainsString('-2', $html);
        $this->assertStringContainsString('Seules les quantités COMPTÉES entrent en stock', $html);
    }

    public function test_sans_ecart_le_bordereau_n_a_pas_le_bloc(): void
    {
        $article = Article::factory()->consommable()->create();

        $charge = $this->charge($article, ['observation_type' => 'livraison_conforme']);
        unset($charge['ecarts_bl']);

        $id = $this->enregistrer($charge)->assertOk()->json('data.id');
        $entree = Entree::query()->findOrFail($id);

        $html = view('stock::pdf.bordereau_reception', [
            'entree' => $entree->load(['magasin', 'lignes.article']),
            'quantitatives' => $entree->lignes,
            'unites' => collect(),
            'blFournisseurs' => collect(),
            'afficherCouts' => false,
            'ecarts' => $entree->ecartsDeclares(),
            'contrePassation' => null,
            'qr' => null,
            'genereLe' => now(),
        ])->render();

        $this->assertStringNotContainsString('Écarts constatés', $html);
    }

    // ── La remontée côté Achat ─────────────────────────────────────────────

    public function test_la_carte_de_reception_porte_la_pilule_et_le_detail(): void
    {
        [$bon] = $this->livraisonAvecEcart();

        $this->actingAs($this->magasinier);

        $carte = app(ReceptionsBonCommande::class)->pour($bon)['integrees']->first();

        $this->assertCount(1, $carte['ecarts_bl']);
        $this->assertSame('Toner 26A', $carte['ecarts_bl'][0]['designation']);
        $this->assertEqualsWithDelta(-2, $carte['ecarts_bl'][0]['ecart'], 0.001);
        $this->assertSame('Manquant', $carte['ecarts_bl'][0]['motif']);
    }

    public function test_la_fiche_du_bon_affiche_la_pilule_ecart_bl(): void
    {
        [$bon] = $this->livraisonAvecEcart();

        $this->actingAs($this->magasinier)
            ->get(route('achat.bons-commande.show', $bon->id))
            ->assertOk()
            ->assertSee('Écart BL')
            ->assertSee('ne modifie aucun compteur');
    }

    public function test_la_chronologie_porte_l_ecart_declare(): void
    {
        [$bon] = $this->livraisonAvecEcart();

        $phrases = app(ChronologieBonCommande::class)->pour($bon)->pluck('phrase');

        $this->assertTrue(
            $phrases->contains(fn ($p) => str_contains($p, 'écart BL déclaré : 1 ligne(s)')),
            'La chronologie doit porter l\'écart : '.$phrases->implode(' | ')
        );
    }

    public function test_une_reception_sans_ecart_ne_mentionne_rien(): void
    {
        [$bon] = $this->livraisonAvecEcart(avecEcart: false);

        $phrases = app(ChronologieBonCommande::class)->pour($bon)->pluck('phrase');

        $this->assertFalse(
            $phrases->contains(fn ($p) => str_contains($p, 'écart BL')),
            'Une livraison conforme ne doit rien mentionner.'
        );
    }

    // ── Le 9e signal ───────────────────────────────────────────────────────

    public function test_le_signal_donne_le_taux_de_livraisons_avec_ecart(): void
    {
        $fournisseur = Fournisseur::factory()->create(['raison_sociale' => 'Peltier SA']);
        $article = Article::factory()->consommable()->create(['nom' => 'Toner 26A']);

        // Trois livraisons validées de ce fournisseur, une seule en écart :
        // le taux (33,3 %) dit ce qu'un nombre brut ne dirait pas.
        foreach ([true, false, false] as $index => $avecEcart) {
            $charge = $this->charge($article, [
                'fournisseur_id' => $fournisseur->id,
                'observation_type' => $avecEcart ? Entree::OBSERVATION_ECART_BL : 'livraison_conforme',
            ]);

            if (! $avecEcart) {
                unset($charge['ecarts_bl']);
            }

            $id = $this->enregistrer($charge)->assertOk()->json('data.id');

            $this->actingAs($this->magasinier)
                ->postJson(route('stock.entrees.valider', $id), ['jeton' => 'jeton-signal-'.$index])
                ->assertOk();
        }

        $signal = app(SignauxService::class)->tous()['ecarts_bl'];
        $ligne = collect($signal['lignes'])->firstWhere('fournisseur', 'Peltier SA');

        $this->assertNotNull($ligne, 'Le fournisseur doit apparaître au signal.');
        $this->assertSame(3, $ligne['livraisons']);
        $this->assertSame(1, $ligne['livraisons_avec_ecart']);
        $this->assertEqualsWithDelta(33.3, $ligne['taux_pct'], 0.1);
        $this->assertSame(1, $ligne['lignes_en_ecart']);
    }

    public function test_un_fournisseur_sans_ecart_n_apparait_pas_au_signal(): void
    {
        $fournisseur = Fournisseur::factory()->create(['raison_sociale' => 'Fournisseur Exemplaire']);
        $article = Article::factory()->consommable()->create();

        $charge = $this->charge($article, [
            'fournisseur_id' => $fournisseur->id,
            'observation_type' => 'livraison_conforme',
        ]);
        unset($charge['ecarts_bl']);

        $id = $this->enregistrer($charge)->assertOk()->json('data.id');
        $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.valider', $id), ['jeton' => 'jeton-propre'])
            ->assertOk();

        // Le signal SIGNALE, il n'accuse pas : celui qui livre juste n'a pas
        // à figurer sur une liste.
        $this->assertNull(
            collect(app(SignauxService::class)->tous()['ecarts_bl']['lignes'])
                ->firstWhere('fournisseur', 'Fournisseur Exemplaire')
        );
    }

    public function test_les_neuf_signaux_sont_servis(): void
    {
        $this->assertArrayHasKey('ecarts_bl', app(SignauxService::class)->tous());
        $this->assertCount(9, app(SignauxService::class)->tous());
    }

    /**
     * Une livraison intégrée à un bon de commande, avec ou sans écart déclaré.
     *
     * @return array{0: BonCommande, 1: Entree}
     */
    private function livraisonAvecEcart(bool $avecEcart = true): array
    {
        $article = Article::factory()->consommable()->create(['nom' => 'Toner 26A']);
        $bon = BonCommande::factory()->valide()->create();

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'designation' => 'Toner 26A',
            'nature' => 'consommable',
            'quantite' => 20,
            'quantite_livree' => 0,
            'prix_unitaire_ht' => 42000,
        ]);

        $charge = $this->charge($article, [
            'bon_commande_id' => $bon->id,
            'fournisseur_id' => $bon->fournisseur_id,
            'observation_type' => $avecEcart ? Entree::OBSERVATION_ECART_BL : 'livraison_conforme',
        ]);

        if (! $avecEcart) {
            unset($charge['ecarts_bl']);
        }

        $id = $this->enregistrer($charge)->assertOk()->json('data.id');

        $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.valider', $id), ['jeton' => 'jeton-livraison-'.$id])
            ->assertOk();

        return [$bon->refresh(), Entree::query()->findOrFail($id)];
    }
}
