<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\RechercheBonCommande;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * A-02 — liste des bons de commande (SPEC_UX A-02).
 *
 * Trois exigences y sont vérifiées : les filtres et la recherche « tous
 * formats » sélectionnent bien ce qu'ils annoncent ; le pied de tableau décrit
 * le FILTRE COURANT et non la page affichée ; la grille actions × statut ne
 * laisse jamais fuiter une action dont l'utilisateur n'a pas la permission.
 */
class ListeBonsCommandeTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS_LECTURE = ['achat.dashboard.view', 'achat.bons_commande.index'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);
    }

    private function utilisateur(array $permissions = self::PERMISSIONS_LECTURE, string $email = 'liste@example.com'): User
    {
        // Un test peut appeler ce fabricant plusieurs fois pour le MÊME acteur
        // (une fois pour agir, une fois pour relire ses actions) : on rend
        // l'utilisateur existant plutôt que d'échouer sur l'unicité.
        $existant = User::query()->where('email', $email)->first();

        if ($existant !== null) {
            return $existant;
        }

        $user = User::create([
            'name' => 'Test',
            'last_name' => 'Liste',
            'user_name' => 'user_'.md5($email),
            'email' => $email,
            'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    /** Un bon avec ses lignes, pour que les montants et compteurs soient réels. */
    private function bon(array $etats = [], int $lignes = 2, array $attributs = []): BonCommande
    {
        $factory = BonCommande::factory();

        foreach ($etats as $etat) {
            $factory = $factory->{$etat}();
        }

        $bon = $factory->create($attributs);

        LigneCommande::factory()->count($lignes)->create([
            'bon_commande_id' => $bon->id,
            'prix_unitaire_ht' => 100000,
            'quantite' => 10,
            'taux_tva' => 18,
        ]);

        $bon->forceFill([
            'montant_ht' => 1000000 * $lignes,
            'montant_tva' => 180000 * $lignes,
            'montant_ttc' => 1180000 * $lignes,
        ])->save();

        return $bon->refresh();
    }

    private function data(User $user, array $parametres = []): array
    {
        return $this->actingAs($user)
            ->getJson(route('achat.bons-commande.data', $parametres))
            ->assertOk()
            ->json();
    }

    // ── Permissions serveur (SFD §5 et §8) ─────────────────────────────────

    public function test_la_liste_exige_la_permission_index(): void
    {
        $sansDroit = $this->utilisateur(['achat.dashboard.view'], 'sansdroit@example.com');

        $this->actingAs($sansDroit)
            ->get(route('achat.bons-commande.index'))
            ->assertForbidden();
    }

    /**
     * Le point de données est aussi exposé que l'écran : sans permission
     * serveur, il livrerait les montants engagés à qui sait forger une URL.
     */
    public function test_le_point_de_donnees_exige_la_permission_index(): void
    {
        $sansDroit = $this->utilisateur(['achat.dashboard.view'], 'sansdroit2@example.com');

        $this->actingAs($sansDroit)
            ->getJson(route('achat.bons-commande.data'))
            ->assertForbidden();
    }

    public function test_la_page_403_nomme_la_permission_manquante(): void
    {
        $sansDroit = $this->utilisateur(['achat.dashboard.view'], 'sansdroit3@example.com');

        $this->actingAs($sansDroit)
            ->get(route('achat.bons-commande.index'))
            ->assertForbidden()
            ->assertSee('achat.bons_commande.index');
    }

    public function test_la_liste_s_affiche_avec_la_permission(): void
    {
        $this->bon(['valide']);

        $this->actingAs($this->utilisateur())
            ->get(route('achat.bons-commande.index'))
            ->assertOk()
            ->assertSee('Bons de commande')
            ->assertSee(route('achat.bons-commande.data'));
    }

    // ── Filtres ────────────────────────────────────────────────────────────

    public function test_le_filtre_statut_accepte_plusieurs_pilules(): void
    {
        $this->bon(['soumis']);
        $this->bon(['valide']);
        $this->bon([]); // brouillon

        $reponse = $this->data($this->utilisateur(), ['statut' => ['SOUMIS', 'VALIDE']]);

        $this->assertSame(2, $reponse['total']);
        $this->assertEqualsCanonicalizing(
            ['SOUMIS', 'VALIDE'],
            array_column($reponse['rows'], 'statut')
        );
    }

    /** Le lien « À valider » du tableau de bord ne passe qu'un statut simple. */
    public function test_le_filtre_statut_accepte_une_valeur_simple(): void
    {
        $this->bon(['soumis']);
        $this->bon(['valide']);

        $reponse = $this->data($this->utilisateur(), ['statut' => 'SOUMIS']);

        $this->assertSame(1, $reponse['total']);
        $this->assertSame('SOUMIS', $reponse['rows'][0]['statut']);
    }

    /** Un statut inventé ne doit pas devenir un filtre neutre qui montre tout. */
    public function test_un_statut_inconnu_est_ecarte(): void
    {
        $this->bon(['valide']);

        $reponse = $this->data($this->utilisateur(), ['statut' => ['INEXISTANT']]);

        $this->assertSame(1, $reponse['total'], 'Un statut inconnu ne doit pas filtrer.');
    }

    public function test_le_filtre_fournisseur(): void
    {
        $attendu = $this->bon(['valide']);
        $this->bon(['valide']);

        $reponse = $this->data($this->utilisateur(), ['fournisseur_id' => $attendu->fournisseur_id]);

        $this->assertSame(1, $reponse['total']);
        $this->assertSame($attendu->id, $reponse['rows'][0]['id']);
    }

    public function test_le_filtre_periode_est_inclusif_sur_les_bornes(): void
    {
        $this->bon(['valide'], 1, ['date_document' => '2026-07-01']);
        $dansLaPeriode = $this->bon(['valide'], 1, ['date_document' => '2026-08-05']);
        $this->bon(['valide'], 1, ['date_document' => '2026-09-01']);

        $reponse = $this->data($this->utilisateur(), ['du' => '2026-08-01', 'au' => '2026-08-31']);

        $this->assertSame(1, $reponse['total']);
        $this->assertSame($dansLaPeriode->id, $reponse['rows'][0]['id']);

        // Bornes incluses : un bon posé exactement sur la borne est retenu.
        $surLaBorne = $this->data($this->utilisateur(), ['du' => '2026-08-05', 'au' => '2026-08-05']);
        $this->assertSame(1, $surLaBorne['total']);
    }

    public function test_le_filtre_regularisations(): void
    {
        $regularisation = $this->bon(['valide', 'regularisation']);
        $this->bon(['valide']);

        $reponse = $this->data($this->utilisateur(), ['regularisations' => 1]);

        $this->assertSame(1, $reponse['total']);
        $this->assertSame($regularisation->id, $reponse['rows'][0]['id']);
        $this->assertTrue($reponse['rows'][0]['est_regularisation']);
    }

    public function test_le_filtre_mes_brouillons_ne_montre_que_les_miens(): void
    {
        $moi = $this->utilisateur();
        $autre = $this->utilisateur(self::PERMISSIONS_LECTURE, 'autre@example.com');

        $lemien = $this->bon([], 1, ['created_by' => $moi->id]);
        $this->bon([], 1, ['created_by' => $autre->id]);
        // Un bon validé m'appartenant n'est pas un brouillon.
        $this->bon(['valide'], 1, ['created_by' => $moi->id]);

        $reponse = $this->data($moi, ['mes_brouillons' => 1]);

        $this->assertSame(1, $reponse['total']);
        $this->assertSame($lemien->id, $reponse['rows'][0]['id']);
    }

    // ── Recherche « tous formats » (UX3-01) ────────────────────────────────

    public function test_la_recherche_par_numero_de_bon(): void
    {
        $bon = $this->bon(['valide']);
        $this->bon(['valide']);

        $reponse = $this->data($this->utilisateur(), ['search' => $bon->numero]);

        $this->assertSame(1, $reponse['total']);
        $this->assertSame($bon->id, $reponse['rows'][0]['id']);
        $this->assertSame(RechercheBonCommande::FORMAT_BON_COMMANDE, $reponse['recherche']['format']);
    }

    public function test_la_recherche_par_identifiant_de_brouillon(): void
    {
        $brouillon = $this->bon();
        $this->bon();

        foreach (["Brouillon #{$brouillon->id}", "#{$brouillon->id}"] as $terme) {
            $reponse = $this->data($this->utilisateur(), ['search' => $terme]);

            $this->assertSame(1, $reponse['total'], "Format non reconnu : {$terme}");
            $this->assertSame($brouillon->id, $reponse['rows'][0]['id']);
            $this->assertSame(RechercheBonCommande::FORMAT_BROUILLON, $reponse['recherche']['format']);
        }
    }

    /**
     * Le raccordement PRQ-05 a posé `stock_entrees.bon_commande_id` : taper le
     * numéro du bon d'entrée doit ramener le bon de commande qu'il a livré.
     */
    public function test_la_recherche_par_numero_de_bon_entree_suit_le_lien_du_raccordement(): void
    {
        $bon = $this->bon(['valide']);
        $this->bon(['valide']);

        $this->creerBonEntree('ENT-2026-0034', $bon->id);

        $reponse = $this->data($this->utilisateur(), ['search' => 'ENT-2026-0034']);

        $this->assertSame(1, $reponse['total']);
        $this->assertSame($bon->id, $reponse['rows'][0]['id']);
        $this->assertSame(RechercheBonCommande::FORMAT_ENTREE, $reponse['recherche']['format']);
    }

    /** Un bon d'entrée inconnu ne ramène rien, et le dit. */
    public function test_la_recherche_d_un_bon_entree_inconnu_explique_le_vide(): void
    {
        $this->bon(['valide']);

        $reponse = $this->data($this->utilisateur(), ['search' => 'ENT-2026-9999']);

        $this->assertSame(0, $reponse['total']);
        $this->assertStringContainsString('ENT-2026-9999', $reponse['recherche']['message']);
    }

    public function test_la_recherche_libre_porte_sur_le_fournisseur(): void
    {
        $fournisseur = Fournisseur::factory()->create(['raison_sociale' => 'Sonabel-Info']);
        $bon = $this->bon(['valide'], 1, ['fournisseur_id' => $fournisseur->id]);
        $this->bon(['valide']);

        // Casse indifférente : LOWER() des deux côtés, portable SQLite/pgsql.
        $reponse = $this->data($this->utilisateur(), ['search' => 'sonabel']);

        $this->assertSame(1, $reponse['total']);
        $this->assertSame($bon->id, $reponse['rows'][0]['id']);
        $this->assertSame(RechercheBonCommande::FORMAT_LIBRE, $reponse['recherche']['format']);
    }

    public function test_la_recherche_libre_porte_sur_la_designation_des_articles(): void
    {
        $bon = $this->bon(['valide'], 1);
        $bon->lignes()->first()->update(['designation' => 'Toner HP 85A']);
        $this->bon(['valide'], 1);

        $reponse = $this->data($this->utilisateur(), ['search' => 'toner hp']);

        $this->assertSame(1, $reponse['total']);
        $this->assertSame($bon->id, $reponse['rows'][0]['id']);
    }

    // ── Totaux du pied de tableau (SPEC_UX §0.4) ───────────────────────────

    /**
     * Le pied décrit le jeu FILTRÉ entier, pas la page affichée : c'est toute
     * son utilité, sinon l'utilisateur additionnerait lui-même les pages.
     */
    public function test_le_total_du_pied_couvre_le_filtre_entier_et_non_la_page(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->bon(['valide'], 1); // 1 180 000 FCFA TTC chacun
        }

        $reponse = $this->data($this->utilisateur(), ['limit' => 1, 'offset' => 0]);

        $this->assertCount(1, $reponse['rows'], 'La page ne doit contenir qu\'une ligne.');
        $this->assertSame(3, $reponse['total']);
        $this->assertEqualsWithDelta(3 * 1180000, $reponse['montant_ttc_affiche'], 0.01);
    }

    public function test_le_total_du_pied_suit_les_filtres(): void
    {
        $this->bon(['valide'], 1);
        $this->bon(['soumis'], 1);

        $reponse = $this->data($this->utilisateur(), ['statut' => ['VALIDE']]);

        $this->assertSame(1, $reponse['total']);
        $this->assertEqualsWithDelta(1180000, $reponse['montant_ttc_affiche'], 0.01);
    }

    // ── Colonnes ───────────────────────────────────────────────────────────

    /** La progression n'a de sens qu'une fois le bon engagé. */
    public function test_la_progression_est_absente_avant_engagement(): void
    {
        $this->bon(['soumis'], 1);

        $reponse = $this->data($this->utilisateur());

        $this->assertNull($reponse['rows'][0]['progression']);
    }

    public function test_la_progression_reflete_les_quantites_livrees(): void
    {
        $bon = BonCommande::factory()->partiel()->create();
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'quantite' => 10,
            'quantite_livree' => 6,
        ]);

        $reponse = $this->data($this->utilisateur());
        $progression = $reponse['rows'][0]['progression'];

        $this->assertEqualsWithDelta(6, $progression['livre'], 0.001);
        $this->assertEqualsWithDelta(10, $progression['commande'], 0.001);
        $this->assertSame(60, $progression['pourcentage']);
    }

    /**
     * Le libellé photographié à la validation prime : un bon engagé reste
     * lisible même si le fournisseur change de raison sociale au Catalogue.
     */
    public function test_le_libelle_fournisseur_photographie_prime(): void
    {
        $fournisseur = Fournisseur::factory()->create(['raison_sociale' => 'Nouveau nom']);
        $this->bon(['valide'], 1, [
            'fournisseur_id' => $fournisseur->id,
            'fournisseur_libelle' => 'Nom au moment de la commande',
        ]);

        $reponse = $this->data($this->utilisateur());

        $this->assertSame('Nom au moment de la commande', $reponse['rows'][0]['fournisseur']);
    }

    public function test_le_tri_est_limite_aux_colonnes_declarees(): void
    {
        $this->bon(['valide'], 1);
        $this->bon(['valide'], 1);

        // Une colonne inventée ne doit ni faire échouer la requête ni être
        // injectée dans le SQL : le tri retombe sur `id`.
        $reponse = $this->data($this->utilisateur(), ['sort' => 'montant_ttc); DROP TABLE achat_bons_commande;--']);

        $this->assertSame(2, $reponse['total']);
    }

    // ── Grille actions × statut (SFD §1.4, SPEC_UX §0.3) ───────────────────

    private function actions(User $user, int $bonId): array
    {
        $ligne = collect($this->data($user)['rows'])->firstWhere('id', $bonId);

        return collect($ligne['actions'])->keyBy('cle')->all();
    }

    public function test_un_brouillon_offre_voir_modifier_supprimer_soumettre(): void
    {
        $utilisateur = $this->utilisateur([
            ...self::PERMISSIONS_LECTURE,
            'achat.bons_commande.update',
            'achat.bons_commande.destroy',
            'achat.bons_commande.soumettre',
        ]);
        $bon = $this->bon([], 1);

        $this->assertEqualsCanonicalizing(
            ['voir', 'modifier', 'supprimer', 'soumettre'],
            array_keys($this->actions($utilisateur, $bon->id))
        );
    }

    /** Doctrine §0.3 : sans le droit, l'action est ABSENTE, pas grisée. */
    public function test_une_action_sans_permission_est_absente_et_non_grisee(): void
    {
        $lecteur = $this->utilisateur();
        $bon = $this->bon([], 1);

        $actions = $this->actions($lecteur, $bon->id);

        $this->assertArrayHasKey('voir', $actions);
        $this->assertArrayNotHasKey('modifier', $actions);
        $this->assertArrayNotHasKey('supprimer', $actions);
        $this->assertArrayNotHasKey('soumettre', $actions);
    }

    /** Doctrine §0.3 : bloquée par l'ÉTAT, l'action reste visible et motivée. */
    public function test_sur_un_bon_valide_modifier_est_grise_avec_son_diagnostic(): void
    {
        $utilisateur = $this->utilisateur([
            ...self::PERMISSIONS_LECTURE,
            'achat.bons_commande.update',
            'achat.bons_commande.destroy',
        ]);
        $bon = $this->bon(['valide'], 1);

        $actions = $this->actions($utilisateur, $bon->id);

        $this->assertFalse($actions['modifier']['actif']);
        $this->assertStringContainsString('annulation ou la clôture', $actions['modifier']['titre']);
        $this->assertFalse($actions['supprimer']['actif']);
    }

    public function test_un_brouillon_sans_ligne_ne_peut_pas_etre_soumis_et_le_dit(): void
    {
        $utilisateur = $this->utilisateur([...self::PERMISSIONS_LECTURE, 'achat.bons_commande.soumettre']);
        $bon = BonCommande::factory()->create();

        $actions = $this->actions($utilisateur, $bon->id);

        $this->assertFalse($actions['soumettre']['actif']);
        $this->assertStringContainsString('aucune ligne', $actions['soumettre']['titre']);
    }

    public function test_un_bon_soumis_offre_valider_et_renvoyer_au_validateur(): void
    {
        $validateur = $this->utilisateur([...self::PERMISSIONS_LECTURE, 'achat.bons_commande.valider']);
        $bon = $this->bon(['soumis'], 1);

        $actions = $this->actions($validateur, $bon->id);

        $this->assertArrayHasKey('valider', $actions);
        $this->assertArrayHasKey('renvoyer', $actions);
    }

    /** « Reprendre » est le retour à soi-même : réservé à l'auteur du bon. */
    public function test_reprendre_n_est_offert_qu_a_l_auteur(): void
    {
        $auteur = $this->utilisateur([...self::PERMISSIONS_LECTURE, 'achat.bons_commande.soumettre']);
        $autre = $this->utilisateur(
            [...self::PERMISSIONS_LECTURE, 'achat.bons_commande.soumettre'],
            'tiers@example.com'
        );

        $bon = $this->bon(['soumis'], 1, ['created_by' => $auteur->id]);

        $this->assertArrayHasKey('reprendre', $this->actions($auteur, $bon->id));
        $this->assertArrayNotHasKey('reprendre', $this->actions($autre, $bon->id));
    }

    /** Un bon annulé est sans effet : il n'a pas de PDF (SPEC_UX A-02). */
    public function test_un_bon_annule_n_offre_pas_de_pdf(): void
    {
        $bon = $this->bon(['annule'], 1);

        $actions = $this->actions($this->utilisateur(), $bon->id);

        $this->assertSame(['voir'], array_keys($actions));
    }

    public function test_un_bon_livre_offre_voir_et_pdf(): void
    {
        $bon = $this->bon(['livre'], 1);

        $this->assertEqualsCanonicalizing(
            ['voir', 'pdf'],
            array_keys($this->actions($this->utilisateur(), $bon->id))
        );
    }

    /**
     * Tant que la fiche A-04 n'existe pas, l'action reste visible mais sans
     * URL : la liste documente la grille sans produire d'ancre morte.
     */
    public function test_une_action_dont_l_ecran_n_existe_pas_encore_n_a_pas_d_url(): void
    {
        $bon = $this->bon(['valide'], 1);

        $voir = $this->actions($this->utilisateur(), $bon->id)['voir'];

        $this->assertSame(
            \Illuminate\Support\Facades\Route::has('achat.bons-commande.show')
                ? route('achat.bons-commande.show', $bon->id)
                : null,
            $voir['url']
        );
    }

    // ── Menu de régularisation (A15) ───────────────────────────────────────

    public function test_le_menu_de_regularisation_est_absent_quand_la_porte_est_fermee(): void
    {
        app(\Modules\Achat\Services\AchatParametres::class)->set('regularisation_active', false);

        $utilisateur = $this->utilisateur([...self::PERMISSIONS_LECTURE, 'achat.bons_commande.regulariser']);

        $this->actingAs($utilisateur)
            ->get(route('achat.bons-commande.index'))
            ->assertOk()
            ->assertDontSee('BC de régularisation');
    }

    public function test_le_menu_de_regularisation_est_absent_sans_la_permission(): void
    {
        $this->actingAs($this->utilisateur())
            ->get(route('achat.bons-commande.index'))
            ->assertOk()
            ->assertDontSee('BC de régularisation');
    }

    /** Crée un bon d'entrée Stock lié, sans dépendre du modèle du Stock. */
    private function creerBonEntree(string $numero, int $bonCommandeId): void
    {
        \Illuminate\Support\Facades\DB::table('stock_entrees')->insert([
            'numero' => $numero,
            'statut' => 'VALIDE',
            'nature' => 'livraison',
            'date_document' => '2026-08-20',
            'magasin_id' => $this->magasinDeTest(),
            'bon_commande_id' => $bonCommandeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function magasinDeTest(): int
    {
        return \Modules\Stock\Models\Magasin::factory()->create()->id;
    }
}
