<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\SignauxService;
use Modules\Achat\Services\StatistiquesAchatService;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-16 — rapports, exports et Signaux (A-07).
 *
 * LE critère du recueil est la COHÉRENCE CONTRACTUELLE : le tableau de
 * bord, la carte de rapport et le fichier exporté doivent porter le MÊME
 * chiffre. Le jour où l'écran annonce 12,4 M et l'export 12,1 M, plus
 * personne ne fait confiance à l'application — ce test l'interdit.
 */
class RapportsTest extends TestCase
{
    use RefreshDatabase;

    private User $lecteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        $this->lecteur = $this->utilisateur([
            'achat.dashboard.view',
            'achat.rapports.view',
            'achat.rapports.export',
        ], 'lecteur-rapports@example.com');
    }

    private function utilisateur(array $permissions, string $email): User
    {
        $existant = User::query()->where('email', $email)->first();

        if ($existant !== null) {
            return $existant;
        }

        $user = User::create([
            'name' => 'Utilisateur '.$email,
            'last_name' => 'Test',
            'user_name' => 'user_'.md5($email),
            'email' => $email,
            'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    /** Un bon engagé, daté, avec une ligne chiffrée. */
    private function bonEngage(
        float $prix = 100000,
        float $quantite = 10,
        ?string $date = null,
        bool $regularisation = false,
        ?Fournisseur $fournisseur = null,
        string $statut = BonCommande::STATUT_VALIDE,
    ): BonCommande {
        $bon = BonCommande::factory()->valide()->create([
            'fournisseur_id' => ($fournisseur ?? Fournisseur::factory()->create())->id,
            'date_document' => $date ?? now()->toDateString(),
            'est_regularisation' => $regularisation,
            'statut' => $statut,
        ]);

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => Article::factory()->consommable()->create()->id,
            'nature' => 'consommable',
            'quantite' => $quantite,
            'prix_unitaire_ht' => $prix,
            'taux_tva' => 18,
        ]);

        app(\Modules\Achat\Services\CalculMontantsService::class)->recalculer($bon);

        return $bon->refresh();
    }

    private function statistiques(): StatistiquesAchatService
    {
        return app(StatistiquesAchatService::class);
    }

    // ═══ Permissions ════════════════════════════════════════════════════════

    public function test_la_page_exige_la_permission_de_lecture(): void
    {
        $sansDroit = $this->utilisateur(['achat.dashboard.view'], 'sans-rapports@example.com');

        $this->actingAs($sansDroit)
            ->get(route('achat.rapports.index'))
            ->assertForbidden()
            ->assertSee('achat.rapports.view');
    }

    /**
     * L'écran annonce un NOMBRE d'indicateurs : il doit être celui que le
     * service sert réellement. Le libellé « 8 indicateurs » est resté faux
     * après l'ajout du 9e signal (BR-04) — un chiffre écrit en dur dans une
     * vue ne se met pas à jour tout seul, ce test s'en charge.
     */
    public function test_la_carte_signaux_annonce_le_bon_nombre_d_indicateurs(): void
    {
        $controleur = $this->utilisateur([
            'achat.rapports.view',
            'achat.rapports.signaux',
        ], 'controleur-libelle@example.com');

        $nombre = count(app(SignauxService::class)->tous());

        $this->actingAs($controleur)
            ->get(route('achat.rapports.index'))
            ->assertOk()
            ->assertSee("{$nombre} indicateurs de vigilance");
    }

    /** UX4-09 : les Signaux ont leur PROPRE permission. */
    public function test_les_signaux_exigent_leur_permission_dediee(): void
    {
        // Le lecteur a rapports.view ET export, mais PAS signaux.
        $this->actingAs($this->lecteur)
            ->getJson(route('achat.rapports.signaux'))
            ->assertForbidden()
            ->assertSee('achat.rapports.signaux');

        $controleur = $this->utilisateur([
            'achat.rapports.view',
            'achat.rapports.signaux',
        ], 'controleur@example.com');

        $this->actingAs($controleur)
            ->getJson(route('achat.rapports.signaux'))
            ->assertOk();
    }

    public function test_la_carte_signaux_est_absente_du_html_sans_permission(): void
    {
        $contenu = $this->actingAs($this->lecteur)
            ->get(route('achat.rapports.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-carte="signaux"', $contenu);
    }

    // ═══ LE critère : cohérence dashboard = rapport = export ════════════════

    public function test_le_dashboard_le_rapport_et_l_export_portent_le_meme_chiffre(): void
    {
        $this->bonEngage(prix: 100000, quantite: 10); // 1 000 000 HT
        $this->bonEngage(prix: 50000, quantite: 4);   //   200 000 HT
        // Une régularisation, exclue partout par défaut.
        $this->bonEngage(prix: 999999, quantite: 9, regularisation: true);

        // 1. Le service (source unique)
        $service = $this->statistiques()->engageDuMois()['ht'];
        $this->assertEqualsWithDelta(1200000, $service, 0.01);

        // 2. Le tableau de bord
        $dashboard = $this->actingAs($this->lecteur)
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->viewData('kpis')['engage_du_mois'];

        $this->assertEqualsWithDelta($service, $dashboard, 0.01);

        // 3. La carte de rapport
        $lignes = $this->actingAs($this->lecteur)
            ->getJson(route('achat.rapports.donnees', 'etat-bons'))
            ->assertOk()
            ->json('lignes');

        $valides = collect($lignes)->firstWhere('statut', BonCommande::STATUT_VALIDE);
        $this->assertEqualsWithDelta($service, $valides['montant_ht'], 0.01);

        // 4. L'export CSV : le même chiffre, dans le fichier.
        $csv = $this->actingAs($this->lecteur)
            ->get(route('achat.rapports.export', ['carte' => 'etat-bons', 'format' => 'csv']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('1200000', $csv);
    }

    // ═══ Les cartes ═════════════════════════════════════════════════════════

    public function test_l_etat_des_bons_liste_tous_les_statuts_meme_a_zero(): void
    {
        $this->bonEngage();

        $lignes = $this->actingAs($this->lecteur)
            ->getJson(route('achat.rapports.donnees', 'etat-bons'))
            ->assertOk()
            ->json('lignes');

        // Un statut absent se lirait « pas calculé » : ils sont tous là.
        $this->assertCount(count(BonCommande::STATUTS), $lignes);
        $this->assertSame(0, collect($lignes)->firstWhere('statut', BonCommande::STATUT_ANNULE)['nombre']);
    }

    public function test_les_depenses_par_fournisseur_agregent_et_trient(): void
    {
        $gros = Fournisseur::factory()->create(['raison_sociale' => 'Gros fournisseur']);
        $petit = Fournisseur::factory()->create(['raison_sociale' => 'Petit fournisseur']);

        $this->bonEngage(prix: 100000, quantite: 10, fournisseur: $gros);
        $this->bonEngage(prix: 100000, quantite: 10, fournisseur: $gros);
        $this->bonEngage(prix: 10000, quantite: 1, fournisseur: $petit);

        $lignes = $this->statistiques()->depensesParFournisseur();

        $this->assertSame('Gros fournisseur', $lignes[0]['fournisseur']);
        $this->assertSame(2, $lignes[0]['nombre']);
        $this->assertEqualsWithDelta(2000000, $lignes[0]['montant_ht'], 0.01);
    }

    /** Une commande peut mélanger des catégories : le calcul est sur LIGNES. */
    public function test_les_depenses_par_categorie_se_calculent_sur_les_lignes(): void
    {
        $bureautique = \Modules\Catalogue\Models\Categorie::create(['libelle' => 'Bureautique']);
        $reseau = \Modules\Catalogue\Models\Categorie::create(['libelle' => 'Réseau']);

        $bon = BonCommande::factory()->valide()->create();

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => Article::factory()->consommable()->create(['categorie_id' => $bureautique->id])->id,
            'quantite' => 10,
            'prix_unitaire_ht' => 1000,
        ]);
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => Article::factory()->consommable()->create(['categorie_id' => $reseau->id])->id,
            'quantite' => 2,
            'prix_unitaire_ht' => 50000,
        ]);

        $lignes = collect($this->statistiques()->depensesParCategorie());

        // Le bon n'est PAS imputé entier à une seule catégorie.
        $this->assertEqualsWithDelta(10000, $lignes->firstWhere('categorie', 'Bureautique')['montant_ht'], 0.01);
        $this->assertEqualsWithDelta(100000, $lignes->firstWhere('categorie', 'Réseau')['montant_ht'], 0.01);
    }

    /** Un graphique qui saute les mois vides ment sur la continuité. */
    public function test_l_evolution_12_mois_inclut_les_mois_vides_en_ordre(): void
    {
        $this->bonEngage(date: now()->toDateString());
        $this->bonEngage(date: now()->subMonths(3)->toDateString());

        $mois = $this->statistiques()->evolutionDouzeMois();

        $this->assertCount(12, $mois);

        // Ordre chronologique strict.
        $cles = array_column($mois, 'mois');
        $triees = $cles;
        sort($triees);
        $this->assertSame($triees, $cles);

        // Le mois d'il y a 6 mois est présent, à zéro.
        $vide = collect($mois)->firstWhere('mois', now()->subMonths(6)->format('Y-m'));
        $this->assertNotNull($vide);
        $this->assertSame(0, $vide['nombre']);
        $this->assertSame(0.0, $vide['montant_ht']);
    }

    // ═══ Les régularisations, exclues PAR DÉFAUT partout ════════════════════

    public function test_les_regularisations_sont_exclues_par_defaut_et_incluses_sur_demande(): void
    {
        $this->bonEngage(prix: 100000, quantite: 1);                      // 100 000
        $this->bonEngage(prix: 500000, quantite: 1, regularisation: true); // 500 000

        $sans = $this->statistiques()->engageSurPeriode();
        $avec = $this->statistiques()->engageSurPeriode(avecRegularisations: true);

        $this->assertEqualsWithDelta(100000, $sans['ht'], 0.01);
        $this->assertEqualsWithDelta(600000, $avec['ht'], 0.01);
    }

    public function test_l_export_dit_toujours_si_les_regularisations_sont_dedans(): void
    {
        $this->bonEngage();

        $csv = $this->actingAs($this->lecteur)
            ->get(route('achat.rapports.export', ['carte' => 'etat-bons', 'format' => 'csv']))
            ->assertOk()
            ->streamedContent();

        // Même exclues — surtout exclues — le lecteur doit le savoir.
        $this->assertStringContainsString('Régularisations : exclues', $csv);
    }

    // ═══ Les exports : la qualification HT/TTC dans le titre ════════════════

    public function test_le_titre_de_l_export_porte_la_qualification_ht_ttc(): void
    {
        $this->bonEngage();

        $csv = $this->actingAs($this->lecteur)
            ->get(route('achat.rapports.export', ['carte' => 'depenses-fournisseur', 'format' => 'csv']))
            ->assertOk()
            ->streamedContent();

        // Sans la mention, un tableau de montants se lit comme du TTC : 18 %.
        $this->assertStringContainsString('montants HT et TTC', $csv);
    }

    public function test_l_export_exige_la_permission_d_export(): void
    {
        $sansExport = $this->utilisateur(['achat.rapports.view'], 'sans-export@example.com');

        $this->actingAs($sansExport)
            ->get(route('achat.rapports.export', ['carte' => 'etat-bons']))
            ->assertForbidden()
            ->assertSee('achat.rapports.export');
    }

    public function test_l_export_xlsx_se_telecharge(): void
    {
        $this->bonEngage();

        $this->actingAs($this->lecteur)
            ->get(route('achat.rapports.export', ['carte' => 'etat-bons', 'format' => 'xlsx']))
            ->assertOk()
            ->assertDownload();
    }

    public function test_l_export_pdf_se_telecharge_avec_ses_filtres(): void
    {
        $this->bonEngage();

        $reponse = $this->actingAs($this->lecteur)
            ->get(route('achat.rapports.export', [
                'carte' => 'etat-bons',
                'format' => 'pdf',
                'du' => now()->startOfMonth()->toDateString(),
            ]))
            ->assertOk();

        $this->assertStringContainsString('application/pdf', $reponse->headers->get('content-type'));
    }

    public function test_une_carte_inconnue_est_un_404(): void
    {
        $this->actingAs($this->lecteur)
            ->getJson(route('achat.rapports.donnees', 'carte-imaginaire'))
            ->assertNotFound();
    }

    // ═══ Les 8 Signaux (SFD §7.7) ═══════════════════════════════════════════

    public function test_les_neuf_signaux_sont_tous_servis(): void
    {
        $signaux = app(SignauxService::class)->tous();

        // Huit au SFD §7.7, plus les écarts BL par fournisseur (BR-04).
        $this->assertCount(9, $signaux);

        foreach ($signaux as $cle => $signal) {
            $this->assertArrayHasKey('titre', $signal, "Le signal {$cle} doit porter un titre.");
            // L'AIDE n'est pas décorative : un chiffre de contrôle sans sa
            // définition se prête à toutes les interprétations.
            $this->assertNotEmpty($signal['aide'], "Le signal {$cle} doit expliquer ce qu'il mesure.");
            $this->assertIsArray($signal['lignes']);
        }
    }

    public function test_le_signal_des_auto_validations_les_trouve(): void
    {
        $utilisateur = $this->lecteur;

        $bon = $this->bonEngage();
        $bon->forceFill(['created_by' => $utilisateur->id, 'valide_par' => $utilisateur->id])->save();

        $lignes = app(SignauxService::class)->tous()['auto_validations']['lignes'];

        $this->assertCount(1, $lignes);
        $this->assertSame($bon->numero, $lignes[0]['bon']);
    }

    public function test_le_signal_des_bons_rapproches_detecte_le_fractionnement(): void
    {
        $fournisseur = Fournisseur::factory()->create(['raison_sociale' => 'Fournisseur fractionné']);

        $this->bonEngage(date: now()->subDays(10)->toDateString(), fournisseur: $fournisseur);
        $this->bonEngage(date: now()->subDays(5)->toDateString(), fournisseur: $fournisseur);
        // Un troisième, loin dans le temps : pas un rapprochement.
        $this->bonEngage(date: now()->subDays(200)->toDateString(), fournisseur: $fournisseur);

        $lignes = app(SignauxService::class)->tous()['bons_rapproches']['lignes'];

        $this->assertCount(1, $lignes);
        $this->assertSame(5, $lignes[0]['ecart_jours']);
    }

    /** La MÉDIANE, pas la moyenne : un bon oublié ne doit pas tout fausser. */
    public function test_le_delai_de_visa_est_une_mediane(): void
    {
        $validateur = $this->utilisateur(['achat.bons_commande.valider'], 'validateur-signaux@example.com');

        // Trois visas : 2 h, 4 h, et un oublié de 1000 h.
        foreach ([2, 4, 1000] as $heures) {
            $bon = $this->bonEngage();
            $bon->forceFill([
                'valide_par' => $validateur->id,
                'soumis_le' => now()->subHours($heures + 1),
                'valide_le' => now()->subHour(),
            ])->save();
        }

        $lignes = app(SignauxService::class)->tous()['delai_visa']['lignes'];
        $ligne = collect($lignes)->firstWhere('validateur', $validateur->name);

        $this->assertSame(3, $ligne['nombre_visas']);
        // Médiane = 4 h. Une moyenne aurait donné ~335 h et serait illisible.
        $this->assertEqualsWithDelta(4, $ligne['delai_median_heures'], 0.1);
    }

    public function test_le_signal_des_clotures_agrege_par_fournisseur(): void
    {
        $fournisseur = Fournisseur::factory()->create(['raison_sociale' => 'Fournisseur défaillant']);

        $bon = $this->bonEngage(prix: 10000, quantite: 10, fournisseur: $fournisseur);
        // `quantite_livree` n'est pas `fillable` (elle n'appartient qu'aux
        // services d'intégration) : forceFill, comme le fait le service.
        $bon->lignes()->first()->forceFill(['quantite_livree' => 4])->save();
        $bon->forceFill(['statut' => BonCommande::STATUT_CLOTURE])->save();

        $lignes = app(SignauxService::class)->tous()['clotures_non_livres']['lignes'];

        $this->assertCount(1, $lignes);
        // 6 non livrées × 10 000 = 60 000 abandonnés.
        $this->assertEqualsWithDelta(60000, $lignes[0]['montant_abandonne_ht'], 0.01);
    }

    public function test_les_signaux_s_exportent_en_xlsx_et_en_pdf(): void
    {
        $controleur = $this->utilisateur([
            'achat.rapports.view',
            'achat.rapports.signaux',
        ], 'controleur@example.com');

        $this->bonEngage();

        $this->actingAs($controleur)
            ->get(route('achat.rapports.signaux-export'))
            ->assertOk()
            ->assertDownload();

        $pdf = $this->actingAs($controleur)
            ->get(route('achat.rapports.signaux-export', ['format' => 'pdf']))
            ->assertOk();

        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
    }

    // ═══ L'écran ════════════════════════════════════════════════════════════

    public function test_la_carte_imputation_annonce_son_attente(): void
    {
        $contenu = $this->actingAs($this->lecteur)
            ->get(route('achat.rapports.index'))
            ->assertOk()
            ->getContent();

        // UX-18 : l'attente reste visible, elle ne disparaît pas du produit.
        if (! \Illuminate\Support\Facades\Schema::hasColumn('catalogue_articles', 'compte_comptable')) {
            $this->assertStringContainsString('Bientôt disponible', $contenu);
            $this->assertStringContainsString('PRQ-03', $contenu);
        } else {
            $this->markTestSkipped('Le compte comptable existe (P0-B livré) : la carte n\'est plus en attente.');
        }
    }

    public function test_la_page_affiche_les_six_cartes_et_les_filtres(): void
    {
        $contenu = $this->actingAs($this->lecteur)
            ->get(route('achat.rapports.index'))
            ->assertOk()
            ->getContent();

        foreach (['etat-bons', 'depenses-fournisseur', 'depenses-categorie', 'evolution', 'reliquats', 'regularisation'] as $carte) {
            $this->assertStringContainsString('data-carte="'.$carte.'"', $contenu);
        }

        $this->assertStringContainsString('filter-regularisations', $contenu);
    }
}
