<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Models\Parametre;
use Modules\Achat\Services\AchatParametres;
use Tests\TestCase;

/**
 * Scopes du modèle et service de paramètres (SFD §6).
 *
 * Les scopes sont la grammaire de lecture du module : une erreur y est
 * invisible mais se propage à tous les écrans. Le service de paramètres est le
 * seul endroit où un texte en base devient une valeur typée.
 */
class ModeleEtParametresTest extends TestCase
{
    use RefreshDatabase;

    private AchatParametres $parametres;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parametres = app(AchatParametres::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function test_le_scope_par_statut_accepte_un_statut_ou_une_liste(): void
    {
        BonCommande::factory()->count(2)->create();            // BROUILLON
        BonCommande::factory()->soumis()->create();
        BonCommande::factory()->valide()->create();

        $this->assertSame(2, BonCommande::query()->parStatut(BonCommande::STATUT_BROUILLON)->count());
        $this->assertSame(
            3,
            BonCommande::query()
                ->parStatut([BonCommande::STATUT_BROUILLON, BonCommande::STATUT_SOUMIS])
                ->count()
        );
    }

    /** Une valeur vide ne filtre rien : on branche un paramètre sans condition. */
    public function test_le_scope_par_statut_ignore_une_valeur_vide(): void
    {
        BonCommande::factory()->count(3)->create();

        foreach ([null, '', []] as $vide) {
            $this->assertSame(3, BonCommande::query()->parStatut($vide)->count());
        }
    }

    public function test_le_scope_regularisations_isole_les_bons_d_interim(): void
    {
        BonCommande::factory()->count(2)->create();
        BonCommande::factory()->regularisation()->create();

        $this->assertSame(1, BonCommande::query()->regularisations()->count());
        $this->assertSame(2, BonCommande::query()->horsRegularisation()->count());
    }

    /**
     * `aLivrer` = engagé, ouvert, ET avec au moins une ligne non soldée.
     * Un bon dont tout est livré n'attend plus rien, même s'il est PARTIEL.
     */
    public function test_le_scope_a_livrer_ne_retient_que_ce_qui_attend_vraiment(): void
    {
        // Attendu : validé avec du reste
        $avecReste = BonCommande::factory()->valide()->create();
        LigneCommande::factory()->create([
            'bon_commande_id' => $avecReste->id, 'quantite' => 10, 'quantite_livree' => 6,
        ]);

        // Exclu : toutes ses lignes sont soldées
        $solde = BonCommande::factory()->partiel()->create();
        LigneCommande::factory()->create([
            'bon_commande_id' => $solde->id, 'quantite' => 5, 'quantite_livree' => 5,
        ]);

        // Exclu : pas encore engagé
        $brouillon = BonCommande::factory()->create();
        LigneCommande::factory()->create(['bon_commande_id' => $brouillon->id, 'quantite' => 4]);

        // Exclu : reliquat abandonné volontairement
        $cloture = BonCommande::factory()->cloture()->create();
        LigneCommande::factory()->create([
            'bon_commande_id' => $cloture->id, 'quantite' => 8, 'quantite_livree' => 2,
        ]);

        $resultat = BonCommande::query()->aLivrer()->pluck('id');

        $this->assertSame([$avecReste->id], $resultat->all());
    }

    public function test_les_scopes_se_combinent(): void
    {
        $ordinaire = BonCommande::factory()->valide()->create();
        LigneCommande::factory()->create(['bon_commande_id' => $ordinaire->id, 'quantite' => 5]);

        $regularisation = BonCommande::factory()->valide()->regularisation()->create();
        LigneCommande::factory()->create(['bon_commande_id' => $regularisation->id, 'quantite' => 5]);

        $this->assertSame(2, BonCommande::query()->aLivrer()->count());
        $this->assertSame(1, BonCommande::query()->aLivrer()->horsRegularisation()->count());
    }

    // ── Service de paramètres : typage ─────────────────────────────────────

    public function test_les_valeurs_sont_relues_dans_leur_type(): void
    {
        $this->seed(AchatParametresSeeder::class);

        $this->assertIsString($this->parametres->prefixeNumerotation());
        $this->assertIsInt($this->parametres->delaiAlerteReliquatJours());
        $this->assertIsInt($this->parametres->seuilEcartPrixPct());
        $this->assertIsInt($this->parametres->tailleMaxPieceMo());
        $this->assertIsBool($this->parametres->regularisationActive());
        $this->assertIsArray($this->parametres->motifsObservation());
        $this->assertInstanceOf(Carbon::class, $this->parametres->intermedeDebut());
    }

    public function test_les_valeurs_v1_sont_celles_du_sfd(): void
    {
        $this->seed(AchatParametresSeeder::class);

        $this->assertSame('BC', $this->parametres->prefixeNumerotation());
        $this->assertSame(30, $this->parametres->delaiAlerteReliquatJours());
        $this->assertSame(20, $this->parametres->seuilEcartPrixPct());
        $this->assertSame(10, $this->parametres->tailleMaxPieceMo());
        $this->assertSame(10240, $this->parametres->tailleMaxPieceKo());
        $this->assertTrue($this->parametres->regularisationActive());
    }

    public function test_les_motifs_d_observation_sont_une_liste_exploitable(): void
    {
        $this->seed(AchatParametresSeeder::class);

        $motifs = $this->parametres->motifsObservation();

        $this->assertArrayHasKey('urgent', $motifs);
        $this->assertArrayHasKey('autre', $motifs);
        $this->assertSame('Renouvellement périodique', $motifs['renouvellement']);
    }

    /** Une borne vide signifie « pas de borne », jamais une date par défaut. */
    public function test_une_borne_d_interim_vide_vaut_null(): void
    {
        $this->seed(AchatParametresSeeder::class);

        $this->assertNull($this->parametres->intermedeFin());
    }

    // ── Service de paramètres : écriture typée ─────────────────────────────

    public function test_l_ecriture_normalise_selon_le_type_declare(): void
    {
        $this->parametres->set(Parametre::REGULARISATION_ACTIVE, false);
        $this->assertFalse($this->parametres->regularisationActive());
        $this->assertSame('0', Parametre::where('cle', Parametre::REGULARISATION_ACTIVE)->value('valeur'));

        $this->parametres->set(Parametre::REGULARISATION_ACTIVE, true);
        $this->assertTrue($this->parametres->regularisationActive());

        $this->parametres->set(Parametre::DELAI_ALERTE_RELIQUAT_JOURS, '45');
        $this->assertSame(45, $this->parametres->delaiAlerteReliquatJours());

        $this->parametres->set(Parametre::INTERMEDE_FIN, '2026-09-30');
        $this->assertSame('2026-09-30', $this->parametres->intermedeFin()->toDateString());

        $this->parametres->set(Parametre::MOTIFS_OBSERVATION, ['a' => 'A', 'b' => 'B']);
        $this->assertSame(['a' => 'A', 'b' => 'B'], $this->parametres->motifsObservation());
    }

    /** Le cache ne doit jamais servir une valeur périmée après écriture. */
    public function test_l_ecriture_invalide_le_cache(): void
    {
        $this->seed(AchatParametresSeeder::class);

        $this->assertSame(30, $this->parametres->delaiAlerteReliquatJours()); // met en cache
        $this->parametres->set(Parametre::DELAI_ALERTE_RELIQUAT_JOURS, 60);

        $this->assertSame(60, $this->parametres->delaiAlerteReliquatJours());
    }

    public function test_la_lecture_est_mise_en_cache(): void
    {
        $this->seed(AchatParametresSeeder::class);
        Cache::flush();

        $this->parametres->delaiAlerteReliquatJours();

        $this->assertTrue(Cache::has('achat.parametre.'.Parametre::DELAI_ALERTE_RELIQUAT_JOURS));
    }

    /** Sans ligne en base, la lecture retombe sur le défaut de config. */
    public function test_une_cle_absente_retombe_sur_le_defaut(): void
    {
        $this->assertSame(0, Parametre::count());

        $this->assertSame('BC', $this->parametres->prefixeNumerotation());
        $this->assertSame(10, $this->parametres->tailleMaxPieceMo());
    }

    // ── Bornes de l'intérim (garde applicative — écart n°3) ────────────────

    public function test_les_bornes_d_interim_encadrent_la_regularisation(): void
    {
        $this->parametres->set(Parametre::INTERMEDE_DEBUT, '2026-07-27');
        $this->parametres->set(Parametre::INTERMEDE_FIN, '2026-08-31');

        $this->assertTrue($this->parametres->dateDansIntermede('2026-08-15'));
        $this->assertTrue($this->parametres->dateDansIntermede('2026-07-27'));  // borne incluse
        $this->assertTrue($this->parametres->dateDansIntermede('2026-08-31'));  // borne incluse
        $this->assertFalse($this->parametres->dateDansIntermede('2026-07-26')); // avant
        $this->assertFalse($this->parametres->dateDansIntermede('2026-09-01')); // après
        $this->assertFalse($this->parametres->dateDansIntermede(null));
    }

    /** Une fin ouverte n'interdit rien après le début. */
    public function test_une_borne_de_fin_ouverte_n_est_pas_une_contrainte(): void
    {
        $this->parametres->set(Parametre::INTERMEDE_DEBUT, '2026-07-27');
        $this->parametres->set(Parametre::INTERMEDE_FIN, '');

        $this->assertTrue($this->parametres->dateDansIntermede(now()->addYear()));
        $this->assertFalse($this->parametres->dateDansIntermede('2026-01-01'));
    }

    // ── Cohérence config / service ─────────────────────────────────────────

    /**
     * Toute clé déclarée dans le service doit avoir un défaut en config, et
     * réciproquement : une clé orpheline serait un paramètre fantôme, présent
     * dans l'écran A-08 mais sans valeur, ou l'inverse.
     */
    public function test_les_cles_du_service_et_de_la_config_coincident(): void
    {
        $service = AchatParametres::cles();
        $config = array_keys(config('achat.parametres_defaut'));

        sort($service);
        sort($config);

        $this->assertSame($config, $service);
    }
}
