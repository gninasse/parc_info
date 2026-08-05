<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\CircuitSoumissionService;
use Modules\Achat\Services\VisaService;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\Activity;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-06 — Le visa (SFD §7.2), cœur transactionnel du module.
 *
 * Les deux invariants du prompt :
 *
 *   IA-3 — concurrence de numérotation : des validations parallèles produisent
 *          des numéros uniques et consécutifs, sans trou ;
 *   IA-5 — idempotence : un double clic (rejeu) n'a qu'un seul effet.
 */
class VisaTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS_ACHETEUR = [
        'achat.dashboard.view',
        'achat.bons_commande.index',
        'achat.bons_commande.store',
        'achat.bons_commande.soumettre',
    ];

    private const PERMISSIONS_VALIDATEUR = [
        'achat.dashboard.view',
        'achat.bons_commande.index',
        'achat.bons_commande.valider',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);
    }

    private function utilisateur(array $permissions, string $email): User
    {
        $existant = User::query()->where('email', $email)->first();

        if ($existant !== null) {
            return $existant;
        }

        $user = User::create([
            'name' => 'Utilisateur '.$email,
            'last_name' => 'Visa',
            'user_name' => 'user_'.md5($email),
            'email' => $email,
            'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    private function validateur(string $email = 'visa-validateur@example.com'): User
    {
        return $this->utilisateur(self::PERMISSIONS_VALIDATEUR, $email);
    }

    private function acheteur(string $email = 'visa-acheteur@example.com'): User
    {
        return $this->utilisateur(self::PERMISSIONS_ACHETEUR, $email);
    }

    /** Un bon SOUMIS, prêt pour le visa. */
    private function bonSoumis(array $attributs = [], ?Article $article = null): BonCommande
    {
        $acheteur = $this->acheteur();

        $bon = BonCommande::factory()->create(array_merge([
            'created_by' => $acheteur->id,
        ], $attributs));

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => ($article ?? Article::factory()->create([
                'nature' => Article::NATURE_CONSOMMABLE,
                'est_actif' => true,
            ]))->id,
            'nature' => Article::NATURE_CONSOMMABLE,
            'quantite' => 10,
            'prix_unitaire_ht' => 100000,
            'taux_tva' => 18,
        ]);

        return app(CircuitSoumissionService::class)->soumettre($bon, $acheteur);
    }

    // ═══ La validation nominale ════════════════════════════════════════════

    public function test_valider_attribue_le_numero_et_verrouille(): void
    {
        $validateur = $this->validateur();
        $bon = $this->bonSoumis();

        $reponse = $this->actingAs($validateur)
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $bon->refresh();

        $this->assertSame(BonCommande::STATUT_VALIDE, $bon->statut);
        $this->assertMatchesRegularExpression('/^BC-\d{4}-\d{4}$/', $bon->numero);
        $this->assertSame($bon->numero, $reponse->json('data.numero'));
        $this->assertSame($validateur->id, $bon->valide_par);
        $this->assertNotNull($bon->valide_le);
    }

    /** La dénormalisation fige ce que le document dira toujours (SFD §7.2). */
    public function test_la_validation_denormalise_le_libelle_fournisseur(): void
    {
        $fournisseur = Fournisseur::factory()->create(['raison_sociale' => 'Sonabel-Info']);
        $bon = $this->bonSoumis(['fournisseur_id' => $fournisseur->id]);

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertOk();

        // Le fournisseur change de raison sociale APRÈS le visa…
        $fournisseur->update(['raison_sociale' => 'Sonabel-Info SA (nouveau nom)']);

        // …le document, lui, dit toujours ce qui a été engagé.
        $this->assertSame('Sonabel-Info', $bon->refresh()->fournisseur_libelle);
    }

    public function test_les_montants_sont_recalcules_au_moment_du_visa(): void
    {
        $bon = $this->bonSoumis();

        // Un montant corrompu entre soumission et visa (écriture directe) est
        // rectifié par le recalcul du visa : les montants font foi une
        // dernière fois avant l'immutabilité.
        DB::table('achat_bons_commande')->where('id', $bon->id)->update(['montant_ttc' => 1]);

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertOk();

        // 10 × 100 000 = 1 000 000 HT ; TTC 1 180 000
        $this->assertEqualsWithDelta(1180000, (float) $bon->refresh()->montant_ttc, 0.01);
    }

    public function test_un_bon_valide_n_est_plus_modifiable(): void
    {
        $bon = $this->bonSoumis();

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertOk();

        $acheteurComplet = $this->utilisateur(
            [...self::PERMISSIONS_ACHETEUR, 'achat.bons_commande.update', 'achat.bons_commande.destroy'],
            'visa-acheteur-complet@example.com'
        );

        $this->actingAs($acheteurComplet)
            ->putJson(route('achat.bons-commande.update', $bon->id), [
                'fournisseur_id' => $bon->fournisseur_id,
                'date_document' => '2026-08-08',
                'lignes' => [],
            ])
            ->assertStatus(409);

        $this->actingAs($acheteurComplet)
            ->deleteJson(route('achat.bons-commande.destroy', $bon->id))
            ->assertStatus(409);
    }

    // ═══ IA-3 : concurrence de numérotation ════════════════════════════════

    /**
     * 20 validations en rafale → 20 numéros uniques, consécutifs, sans trou.
     *
     * SQLite ne permet pas deux transactions concurrentes sur une même base
     * mémoire : la rafale séquentielle vérifie ici l'absence de trou et de
     * collision logique. La concurrence RÉELLE (verrou de ligne PostgreSQL)
     * est vérifiée par le test pgsql ci-dessous.
     */
    public function test_vingt_validations_en_rafale_produisent_des_numeros_consecutifs_sans_trou(): void
    {
        $validateur = $this->validateur();
        $visa = app(VisaService::class);

        $bons = collect(range(1, 20))->map(fn () => $this->bonSoumis());

        $numeros = $bons->map(fn (BonCommande $bon) => $visa->valider($bon, $validateur)->numero);

        // Uniques…
        $this->assertSame(20, $numeros->unique()->count(), 'Collision de numéros.');

        // …et consécutifs sans trou : les suffixes forment exactement 1..20.
        $suffixes = $numeros
            ->map(fn (string $numero) => (int) substr($numero, -4))
            ->sort()
            ->values()
            ->all();

        $this->assertSame(range(1, 20), $suffixes, 'La séquence porte un trou ou un doublon.');
    }

    /**
     * IA-3 sous concurrence réelle : deux CONNEXIONS PostgreSQL distinctes qui
     * consomment la séquence en même temps se sérialisent sur le verrou de la
     * ligne — la seconde attend, puis prend le numéro suivant.
     *
     * On travaille sur une seconde connexion PDO brute plutôt que sur un
     * processus séparé : sous RefreshDatabase, les données du test vivent dans
     * une transaction qu'un autre processus ne verrait pas.
     */
    public function test_le_verrou_de_sequence_serialise_deux_connexions_concurrentes(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped(
                'Concurrence réelle vérifiée sur PostgreSQL (SQLite mémoire est mono-connexion).'
            );
        }

        $config = config('database.connections.pgsql');
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $config['host'],
            $config['port'],
            $config['database']
        );

        // La table de séquence doit être visible des deux connexions : on la
        // peuple hors transaction de test, puis on nettoie.
        $prefixe = 'CCT'; // préfixe dédié, pour ne pas toucher la vraie série
        $annee = (int) now()->year;

        $externe1 = new \PDO($dsn, $config['username'], $config['password']);
        $externe2 = new \PDO($dsn, $config['username'], $config['password']);
        $schema = $config['search_path'] ?? 'public';
        $externe1->exec("SET search_path TO {$schema}");
        $externe2->exec("SET search_path TO {$schema}");

        try {
            $externe1->exec(
                "INSERT INTO achat_sequences (prefixe, annee, last_value, created_at, updated_at)
                 VALUES ('{$prefixe}', {$annee}, 0, now(), now())
                 ON CONFLICT DO NOTHING"
            );

            // Connexion 1 : prend le verrou et incrémente, SANS commit.
            $externe1->beginTransaction();
            $externe1->query(
                "SELECT last_value FROM achat_sequences
                 WHERE prefixe = '{$prefixe}' AND annee = {$annee} FOR UPDATE"
            );
            $externe1->exec(
                "UPDATE achat_sequences SET last_value = last_value + 1
                 WHERE prefixe = '{$prefixe}' AND annee = {$annee}"
            );

            // Connexion 2 : la même prise de verrou doit ATTENDRE — vérifié
            // par un timeout court qui expire tant que le verrou est tenu.
            $externe2->exec('SET lock_timeout = 300');
            $bloquee = false;

            try {
                $externe2->query(
                    "SELECT last_value FROM achat_sequences
                     WHERE prefixe = '{$prefixe}' AND annee = {$annee} FOR UPDATE"
                );
            } catch (\PDOException) {
                $bloquee = true;
            }

            $this->assertTrue(
                $bloquee,
                'La seconde connexion a lu la séquence pendant que la première tenait le verrou.'
            );

            // Après le commit, la connexion 2 passe et lit la valeur À JOUR.
            $externe1->commit();
            $externe2->exec('SET lock_timeout = 0');

            $valeur = (int) $externe2->query(
                "SELECT last_value FROM achat_sequences
                 WHERE prefixe = '{$prefixe}' AND annee = {$annee} FOR UPDATE"
            )->fetchColumn();

            $this->assertSame(1, $valeur, 'La seconde connexion n\'a pas vu l\'incrément de la première.');
        } finally {
            $externe1->exec("DELETE FROM achat_sequences WHERE prefixe = '{$prefixe}'");
        }
    }

    // ═══ IA-5 : idempotence ════════════════════════════════════════════════

    /** Le double clic : deux POST successifs, un seul effet. */
    public function test_un_double_clic_ne_valide_qu_une_fois(): void
    {
        $validateur = $this->validateur();
        $bon = $this->bonSoumis();

        $premiere = $this->actingAs($validateur)
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertOk();

        $seconde = $this->actingAs($validateur)
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertOk();

        // Même numéro, pas de seconde consommation de séquence.
        $this->assertSame($premiere->json('data.numero'), $seconde->json('data.numero'));
        $this->assertSame(
            1,
            (int) DB::table('achat_sequences')->where('annee', now()->year)->value('last_value'),
            'Le rejeu a consommé un numéro supplémentaire.'
        );

        // Et une seule journalisation : le journal ne raconte qu'un visa.
        $this->assertSame(
            1,
            Activity::query()
                ->where('subject_id', $bon->id)
                ->where('description', VisaService::EVENEMENT_VALIDATION)
                ->count()
        );
    }

    /** Un brouillon supprimé n'a jamais consommé de numéro (IA-3). */
    public function test_un_brouillon_supprime_ne_laisse_aucun_trou(): void
    {
        $validateur = $this->validateur();

        // Un brouillon vit puis meurt…
        BonCommande::factory()->create()->delete();

        // …et la séquence n'en sait rien.
        $bon = $this->bonSoumis();
        $numero = app(VisaService::class)->valider($bon, $validateur)->numero;

        $this->assertSame(1, (int) substr($numero, -4));
    }

    // ═══ Refus explicites (SFD §7.2) ═══════════════════════════════════════

    public function test_valider_un_bon_dont_l_article_a_ete_desactive_est_refuse_en_422(): void
    {
        $article = Article::factory()->create([
            'nature' => Article::NATURE_CONSOMMABLE,
            'nom' => 'Toner HP 85A',
            'est_actif' => true,
        ]);
        $bon = $this->bonSoumis([], $article);

        // Le Catalogue désactive l'article APRÈS la soumission.
        $article->update(['est_actif' => false]);

        $reponse = $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertStatus(422);

        // Refus EXPLICITE : l'article est nommé, la sortie est indiquée.
        $this->assertStringContainsString('Toner HP 85A', $reponse->json('message'));
        $this->assertStringContainsString('renvoyez le bon en brouillon', $reponse->json('message'));
        $this->assertSame(BonCommande::STATUT_SOUMIS, $bon->fresh()->statut);
        $this->assertNull($bon->fresh()->numero, 'Un visa refusé ne doit pas avoir consommé de numéro.');
    }

    public function test_valider_un_bon_dont_le_fournisseur_a_ete_desactive_est_refuse_en_422(): void
    {
        $fournisseur = Fournisseur::factory()->create(['raison_sociale' => 'Sonabel-Info', 'est_actif' => true]);
        $bon = $this->bonSoumis(['fournisseur_id' => $fournisseur->id]);

        $fournisseur->update(['est_actif' => false]);

        $reponse = $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertStatus(422);

        $this->assertStringContainsString('Sonabel-Info', $reponse->json('message'));
        $this->assertSame(BonCommande::STATUT_SOUMIS, $bon->fresh()->statut);
    }

    public function test_valider_un_brouillon_est_refuse_en_409(): void
    {
        $bon = BonCommande::factory()->create();

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertStatus(409);
    }

    // ═══ 403 : la séparation du visa, encore ═══════════════════════════════

    public function test_valider_exige_la_permission_de_visa(): void
    {
        $bon = $this->bonSoumis();

        $this->actingAs($this->acheteur())
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertForbidden()
            ->assertSee('achat.bons_commande.valider');

        $this->assertSame(BonCommande::STATUT_SOUMIS, $bon->fresh()->statut);
    }

    public function test_les_signaux_exigent_la_permission_de_visa(): void
    {
        $bon = $this->bonSoumis();

        // Les signaux révèlent le cumul de dépense d'un fournisseur : ils ne
        // s'ouvrent qu'à qui détient le visa.
        $this->actingAs($this->acheteur())
            ->getJson(route('achat.bons-commande.signaux', $bon->id))
            ->assertForbidden();
    }

    // ═══ Les signaux de SW-02 ══════════════════════════════════════════════

    public function test_les_signaux_donnent_le_cumul_du_fournisseur(): void
    {
        $fournisseur = Fournisseur::factory()->create();

        // Deux bons déjà engagés ce mois-ci chez ce fournisseur.
        foreach ([1000000, 2000000] as $ttc) {
            BonCommande::factory()->valide()->create([
                'fournisseur_id' => $fournisseur->id,
                'date_document' => now()->toDateString(),
                'montant_ttc' => $ttc,
            ]);
        }

        $bon = $this->bonSoumis(['fournisseur_id' => $fournisseur->id, 'date_document' => now()->toDateString()]);

        $reponse = $this->actingAs($this->validateur())
            ->getJson(route('achat.bons-commande.signaux', $bon->id))
            ->assertOk();

        $this->assertSame(2, $reponse->json('cumul.nb_bc_mois'));
        $this->assertSame(3, $reponse->json('cumul.rang_du_mois'), 'Le bon en cours de visa doit compter dans le rang.');
        $this->assertEqualsWithDelta(3000000, $reponse->json('cumul.cumul_ttc_mois'), 0.01);
    }

    public function test_le_signal_fournisseur_recent_apparait_puis_s_eteint(): void
    {
        // Fournisseur créé il y a 6 jours : signal présent.
        $recent = Fournisseur::factory()->create(['created_at' => now()->subDays(6)]);
        $bon = $this->bonSoumis(['fournisseur_id' => $recent->id]);

        $reponse = $this->actingAs($this->validateur())
            ->getJson(route('achat.bons-commande.signaux', $bon->id))
            ->assertOk();

        $this->assertNotNull($reponse->json('fournisseur_recent'));
        $this->assertSame(6, $reponse->json('fournisseur_recent.anciennete_jours'));
        $this->assertTrue($reponse->json('fournisseur_recent.premier_bc'));

        // Fournisseur établi : un signal permanent n'est plus un signal.
        $etabli = Fournisseur::factory()->create(['created_at' => now()->subMonths(6)]);
        $bonEtabli = $this->bonSoumis(['fournisseur_id' => $etabli->id]);

        $this->assertNull(
            $this->actingAs($this->validateur())
                ->getJson(route('achat.bons-commande.signaux', $bonEtabli->id))
                ->json('fournisseur_recent')
        );
    }

    public function test_le_signal_d_ecart_de_prix_reprend_la_definition_commune(): void
    {
        $article = Article::factory()->create([
            'nature' => Article::NATURE_CONSOMMABLE,
            'prix_indicatif' => 100000,
            'est_actif' => true,
        ]);

        // Le bon soumis paie 60 % de plus que la référence.
        $bon = $this->bonSoumis([], $article);
        $bon->lignes()->update(['prix_unitaire_ht' => 160000]);

        $reponse = $this->actingAs($this->validateur())
            ->getJson(route('achat.bons-commande.signaux', $bon->id))
            ->assertOk();

        $this->assertCount(1, $reponse->json('ecarts_prix'));
        $this->assertEqualsWithDelta(60, $reponse->json('ecarts_prix.0.ecart_pct'), 0.1);
    }

    /** Les signaux n'empêchent JAMAIS le visa (SPEC_UX A-04). */
    public function test_les_signaux_ne_bloquent_pas_la_validation(): void
    {
        $recent = Fournisseur::factory()->create(['created_at' => now()->subDays(2)]);
        $article = Article::factory()->create([
            'nature' => Article::NATURE_CONSOMMABLE,
            'prix_indicatif' => 1000,
            'est_actif' => true,
        ]);

        $bon = $this->bonSoumis(['fournisseur_id' => $recent->id], $article);
        $bon->lignes()->update(['prix_unitaire_ht' => 999999]); // écart énorme

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertOk();

        $this->assertSame(BonCommande::STATUT_VALIDE, $bon->fresh()->statut);
    }

    // ═══ Auto-validation (UX4-07) : marquée, jamais bloquée ════════════════

    public function test_l_auto_validation_est_signalee_puis_marquee_au_journal(): void
    {
        $cumulard = $this->utilisateur(
            [...self::PERMISSIONS_ACHETEUR, 'achat.bons_commande.valider'],
            'cumulard@example.com'
        );

        $bon = BonCommande::factory()->create(['created_by' => $cumulard->id]);
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => Article::factory()->create([
                'nature' => Article::NATURE_CONSOMMABLE, 'est_actif' => true,
            ])->id,
            'nature' => Article::NATURE_CONSOMMABLE,
            'quantite' => 1,
            'prix_unitaire_ht' => 1000,
            'taux_tva' => 18,
        ]);
        app(CircuitSoumissionService::class)->soumettre($bon, $cumulard);

        // Le signal l'annonce avant le geste…
        $this->assertTrue(
            $this->actingAs($cumulard)
                ->getJson(route('achat.bons-commande.signaux', $bon->id))
                ->json('auto_validation')
        );

        // …le geste passe (jamais bloqué en v1)…
        $this->actingAs($cumulard)
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertOk();

        // …et la marque reste, pour la fiche et le rapport Signaux.
        $activite = Activity::query()
            ->where('subject_id', $bon->id)
            ->where('description', VisaService::EVENEMENT_VALIDATION)
            ->first();

        $this->assertTrue((bool) ($activite->properties['auto_validation'] ?? false));
        $this->assertSame($bon->fresh()->created_by, $bon->fresh()->valide_par);
    }

    public function test_une_validation_normale_n_est_pas_marquee(): void
    {
        $bon = $this->bonSoumis();

        $this->actingAs($this->validateur())
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertOk();

        $activite = Activity::query()
            ->where('subject_id', $bon->id)
            ->where('description', VisaService::EVENEMENT_VALIDATION)
            ->first();

        $this->assertFalse((bool) ($activite->properties['auto_validation'] ?? true));
    }

    // ═══ Journal ═══════════════════════════════════════════════════════════

    public function test_la_validation_est_journalisee_avec_le_numero(): void
    {
        $validateur = $this->validateur();
        $bon = $this->bonSoumis();

        $this->actingAs($validateur)
            ->postJson(route('achat.bons-commande.valider', $bon->id))
            ->assertOk();

        $activite = Activity::query()
            ->where('subject_id', $bon->id)
            ->where('description', VisaService::EVENEMENT_VALIDATION)
            ->first();

        $this->assertNotNull($activite);
        $this->assertSame('achat', $activite->module);
        $this->assertSame($validateur->id, $activite->causer_id);
        $this->assertSame($bon->fresh()->numero, $activite->properties['numero'] ?? null);
    }
}
