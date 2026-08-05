<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Models\Parametre;
use Modules\Achat\Services\CalculMontantsService;
use Modules\Achat\Services\NumerotationService;
use Tests\TestCase;

/**
 * Services fondateurs : calcul des montants (IA-1) et numérotation (IA-3).
 * Ce sont les deux endroits où une erreur serait à la fois invisible et
 * contractuellement grave.
 */
class ServicesFondateursTest extends TestCase
{
    use RefreshDatabase;

    private CalculMontantsService $montants;

    private NumerotationService $numerotation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->montants = app(CalculMontantsService::class);
        $this->numerotation = app(NumerotationService::class);
    }

    // ── IA-1 : les montants, calculés une seule fois côté serveur ──────────

    public function test_les_montants_du_bon_sont_calcules_depuis_les_lignes(): void
    {
        $bon = BonCommande::factory()->create();

        // 3 × 650 000 HT à 18 % + 2 × 8 500 HT à 18 %
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id, 'quantite' => 3,
            'prix_unitaire_ht' => 650000, 'taux_tva' => 18,
        ]);
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id, 'quantite' => 2,
            'prix_unitaire_ht' => 8500, 'taux_tva' => 18,
        ]);

        $totaux = $this->montants->recalculer($bon);

        $this->assertEqualsWithDelta(1967000, $totaux['montant_ht'], 0.001);
        $this->assertEqualsWithDelta(354060, $totaux['montant_tva'], 0.001);
        $this->assertEqualsWithDelta(2321060, $totaux['montant_ttc'], 0.001);

        // Dénormalisation : l'écran, le PDF et l'export liront cette valeur
        $bon->refresh();
        $this->assertEqualsWithDelta(2321060, (float) $bon->montant_ttc, 0.001);
    }

    public function test_des_taux_de_tva_differents_sont_traites_ligne_a_ligne(): void
    {
        $bon = BonCommande::factory()->create();

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id, 'quantite' => 1,
            'prix_unitaire_ht' => 100000, 'taux_tva' => 18,
        ]);
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id, 'quantite' => 1,
            'prix_unitaire_ht' => 100000, 'taux_tva' => 0,
        ]);

        $totaux = $this->montants->recalculer($bon);

        $this->assertEqualsWithDelta(200000, $totaux['montant_ht'], 0.001);
        $this->assertEqualsWithDelta(18000, $totaux['montant_tva'], 0.001);
        $this->assertEqualsWithDelta(218000, $totaux['montant_ttc'], 0.001);
    }

    /** L'arrondi est fait par ligne : HT + TVA doit toujours égaler TTC. */
    public function test_le_ttc_est_toujours_la_somme_exacte_du_ht_et_de_la_tva(): void
    {
        $bon = BonCommande::factory()->create();

        foreach ([[3, 333.33], [7, 1666.67], [11, 99.99]] as [$quantite, $prix]) {
            LigneCommande::factory()->create([
                'bon_commande_id' => $bon->id,
                'quantite' => $quantite,
                'prix_unitaire_ht' => $prix,
                'taux_tva' => 18,
            ]);
        }

        $totaux = $this->montants->recalculer($bon);

        $this->assertEqualsWithDelta(
            $totaux['montant_ttc'],
            $totaux['montant_ht'] + $totaux['montant_tva'],
            0.001,
            'Le TTC doit être exactement HT + TVA : sinon écran et PDF divergent.'
        );
    }

    public function test_un_bon_sans_ligne_a_des_montants_nuls(): void
    {
        $bon = BonCommande::factory()->create();

        $totaux = $this->montants->recalculer($bon);

        $this->assertSame(0.0, $totaux['montant_ht']);
        $this->assertSame(0.0, $totaux['montant_ttc']);
    }

    /** PO-02 — la décomposition par taux doit se recoller au total. */
    public function test_la_decomposition_par_taux_recolle_au_total(): void
    {
        $bon = BonCommande::factory()->create();

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id, 'quantite' => 2,
            'prix_unitaire_ht' => 50000, 'taux_tva' => 18,
        ]);
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id, 'quantite' => 1,
            'prix_unitaire_ht' => 30000, 'taux_tva' => 18,
        ]);
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id, 'quantite' => 1,
            'prix_unitaire_ht' => 20000, 'taux_tva' => 0,
        ]);

        $lignes = $bon->lignes()->get();
        $decomposition = $this->montants->decompositionParTaux($lignes);
        $totaux = $this->montants->totaux($lignes);

        $this->assertCount(2, $decomposition);
        // Trié par taux croissant : 0 % puis 18 %
        $this->assertEqualsWithDelta(0.0, $decomposition[0]['taux'], 0.001);
        $this->assertEqualsWithDelta(20000, $decomposition[0]['base_ht'], 0.001);
        $this->assertEqualsWithDelta(130000, $decomposition[1]['base_ht'], 0.001);

        $this->assertEqualsWithDelta(
            $totaux['montant_ttc'],
            array_sum(array_column($decomposition, 'ttc')),
            0.001
        );
    }

    // ── IA-3 : numérotation sans trou ni collision ─────────────────────────

    public function test_le_numero_suit_le_format_prefixe_annee_sequence(): void
    {
        $bon = BonCommande::factory()->soumis()->create();

        $numero = $this->numerotation->attribuer($bon);

        $this->assertSame('BC-'.now()->year.'-0001', $numero);
        $this->assertSame($numero, $bon->fresh()->numero);
    }

    /**
     * Numéroter, c'est engager : il n'existe aucun instant où un bon
     * porterait un numéro sans être validé (CHECK chk_bc_numero_si_engage).
     */
    public function test_numeroter_engage_le_bon_dans_le_meme_mouvement(): void
    {
        $bon = BonCommande::factory()->soumis()->create();
        $validateur = \Modules\Core\Models\User::create([
            'name' => 'Valideur', 'last_name' => 'Test', 'user_name' => 'valideur',
            'email' => 'valideur@example.com', 'password' => bcrypt('password'),
        ]);

        $this->numerotation->attribuer($bon, $validateur->id);

        $bon->refresh();
        $this->assertSame(BonCommande::STATUT_VALIDE, $bon->statut);
        $this->assertSame($validateur->id, $bon->valide_par);
        $this->assertNotNull($bon->valide_le);
        $this->assertNotNull($bon->numero);
    }

    public function test_la_sequence_s_incremente_sans_trou(): void
    {
        $numeros = [];

        for ($i = 0; $i < 5; $i++) {
            $numeros[] = $this->numerotation->attribuer(BonCommande::factory()->soumis()->create());
        }

        $annee = now()->year;
        $this->assertSame([
            "BC-{$annee}-0001", "BC-{$annee}-0002", "BC-{$annee}-0003",
            "BC-{$annee}-0004", "BC-{$annee}-0005",
        ], $numeros);
    }

    /** Un brouillon supprimé ne consomme pas de numéro (SFD §6.1). */
    public function test_un_brouillon_supprime_ne_laisse_aucun_trou(): void
    {
        $premier = $this->numerotation->attribuer(BonCommande::factory()->soumis()->create());

        // Un brouillon vit et meurt sans jamais être numéroté
        BonCommande::factory()->create()->delete();

        $second = $this->numerotation->attribuer(BonCommande::factory()->soumis()->create());

        $annee = now()->year;
        $this->assertSame("BC-{$annee}-0001", $premier);
        $this->assertSame("BC-{$annee}-0002", $second);
    }

    /** IA-5 : rejouer une attribution ne consomme pas un second numéro. */
    public function test_l_attribution_est_idempotente(): void
    {
        $bon = BonCommande::factory()->soumis()->create();

        $premier = $this->numerotation->attribuer($bon);
        $second = $this->numerotation->attribuer($bon->fresh());

        $this->assertSame($premier, $second);
        $this->assertSame(1, (int) DB::table('achat_sequences')->value('last_value'));
    }

    public function test_le_prefixe_est_administrable(): void
    {
        Parametre::definir(Parametre::PREFIXE_NUMEROTATION, 'CMD');

        $numero = $this->numerotation->attribuer(BonCommande::factory()->soumis()->create());

        $this->assertSame('CMD-'.now()->year.'-0001', $numero);
    }

    /** Deux séries d'années distinctes ne se marchent pas dessus. */
    public function test_la_sequence_repart_a_un_par_annee(): void
    {
        $annee = now()->year;

        $this->numerotation->attribuer(BonCommande::factory()->soumis()->create());

        // Simule l'existence d'une séquence de l'année précédente
        DB::table('achat_sequences')->insert([
            'prefixe' => 'BC', 'annee' => $annee - 1, 'last_value' => 348,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $numero = $this->numerotation->attribuer(BonCommande::factory()->soumis()->create());

        $this->assertSame("BC-{$annee}-0002", $numero);
    }
}
