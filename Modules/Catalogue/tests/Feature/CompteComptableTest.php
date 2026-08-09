<?php

namespace Modules\Catalogue\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Core\Models\User;
use Tests\TestCase;

/**
 * P0-B (PRQ-03) — le compte comptable d'imputation.
 *
 * Il débloque une question que l'établissement ne peut pas poser aujourd'hui :
 * « combien avons-nous dépensé sur tel compte cette année ? ».
 *
 * Deux exigences gouvernent ces tests :
 *
 *   - **nullable partout** : aucun flux existant ne doit s'arrêter parce
 *     qu'un article n'a pas encore son imputation. C'est ce qui permet de
 *     remplir le catalogue progressivement plutôt que d'un bloc ;
 *   - **format libre** : le plan comptable de l'établissement n'est pas
 *     arrêté dans l'application. Imposer un format reviendrait à choisir à
 *     la place du service financier.
 */
class CompteComptableTest extends TestCase
{
    use RefreshDatabase;

    private User $gestionnaire;

    private Categorie $categorie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);

        $this->gestionnaire = User::create([
            'name' => 'Gestionnaire', 'last_name' => 'Catalogue', 'user_name' => 'gest_cat',
            'email' => 'gest-cat@example.com', 'password' => bcrypt('password'),
        ]);

        foreach (['catalogue.articles.index', 'catalogue.articles.store',
            'catalogue.articles.update', 'catalogue.api.view'] as $permission) {
            $this->gestionnaire->givePermissionTo($permission);
        }

        $this->categorie = Categorie::create(['code' => 'CAT-CC', 'libelle' => 'Imputation']);
    }

    private function article(array $surcharges = []): Article
    {
        return Article::create(array_merge([
            'code' => 'CONS-CC-'.substr(md5(uniqid()), 0, 5),
            'nom' => 'Article imputé',
            'nature' => 'consommable',
            'categorie_id' => $this->categorie->id,
            'unite_stock' => 'unité',
            'est_actif' => true,
        ], $surcharges));
    }

    // ── Le champ ───────────────────────────────────────────────────────────

    public function test_le_compte_comptable_se_saisit_et_se_conserve(): void
    {
        $article = $this->article(['compte_comptable' => '6063']);

        $this->assertSame('6063', $article->fresh()->compte_comptable);
    }

    /**
     * Rien ne doit s'arrêter faute d'imputation : le catalogue se remplit
     * article par article, et les flux existants continuent entre-temps.
     */
    public function test_un_article_sans_compte_reste_parfaitement_valide(): void
    {
        $article = $this->article();

        $this->assertNull($article->fresh()->compte_comptable);

        $this->actingAs($this->gestionnaire)
            ->getJson(route('catalogue.api.articles.show', $article->id))
            ->assertOk()
            ->assertJsonPath('data.compte_comptable', null);
    }

    /**
     * Format LIBRE : chiffres, lettres, points, tirets. Le service financier
     * décidera de sa nomenclature, pas l'application.
     */
    public function test_le_format_est_libre(): void
    {
        foreach (['6063', '60.63.1', '6063-INFO', 'CL6 Achats'] as $compte) {
            $article = $this->article(['compte_comptable' => $compte]);
            $this->assertSame($compte, $article->fresh()->compte_comptable);
        }
    }

    public function test_le_compte_est_borne_a_cinquante_caracteres(): void
    {
        $this->actingAs($this->gestionnaire)
            ->postJson(route('catalogue.articles.store'), [
                'nom' => 'Article trop long',
                'nature' => 'consommable',
                'categorie_id' => $this->categorie->id,
                'unite_stock' => 'unité',
                'compte_comptable' => str_repeat('9', 51),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('compte_comptable');
    }

    // ── Le contrat d'API (§2.2) ────────────────────────────────────────────

    public function test_l_api_expose_le_compte_comptable(): void
    {
        $article = $this->article(['compte_comptable' => '6063']);

        $this->actingAs($this->gestionnaire)
            ->getJson(route('catalogue.api.articles.show', $article->id))
            ->assertOk()
            ->assertJsonPath('data.compte_comptable', '6063');

        $this->actingAs($this->gestionnaire)
            ->getJson(route('catalogue.api.articles'))
            ->assertOk()
            ->assertJsonPath('data.0.compte_comptable', '6063');
    }

    // ── L'import CSV ───────────────────────────────────────────────────────

    private function csv(string $contenu): string
    {
        $chemin = sys_get_temp_dir().'/comptes_'.uniqid().'.csv';
        file_put_contents($chemin, $contenu);

        return $chemin;
    }

    public function test_l_import_renseigne_les_comptes(): void
    {
        $a = $this->article(['code' => 'CONS-00001']);
        $b = $this->article(['code' => 'CONS-00002']);

        $chemin = $this->csv("code_article;compte_comptable\nCONS-00001;6063\nCONS-00002;6064\n");

        $this->artisan('catalogue:importer-comptes', ['fichier' => $chemin])
            ->expectsConfirmation('Appliquer 2 imputation(s) ?', 'yes')
            ->assertSuccessful();

        $this->assertSame('6063', $a->fresh()->compte_comptable);
        $this->assertSame('6064', $b->fresh()->compte_comptable);
    }

    /**
     * Un code inconnu ne doit pas interrompre l'import : un fichier de 400
     * lignes dont la 12e comporte une coquille doit passer, sinon personne
     * ne se servira de la commande.
     */
    public function test_un_code_inconnu_n_interrompt_pas_l_import(): void
    {
        $a = $this->article(['code' => 'CONS-00001']);

        $chemin = $this->csv("CONS-00001;6063\nCODE-FANTOME;6099\n");

        $this->artisan('catalogue:importer-comptes', ['fichier' => $chemin])
            ->expectsConfirmation('Appliquer 1 imputation(s) ?', 'yes')
            ->assertSuccessful();

        $this->assertSame('6063', $a->fresh()->compte_comptable);
    }

    /** Le séparateur des tableurs francophones (« ; ») comme l'autre. */
    public function test_l_import_accepte_les_deux_separateurs(): void
    {
        $article = $this->article(['code' => 'CONS-00009']);

        $chemin = $this->csv("CONS-00009,6070\n");

        $this->artisan('catalogue:importer-comptes', ['fichier' => $chemin])
            ->expectsConfirmation('Appliquer 1 imputation(s) ?', 'yes')
            ->assertSuccessful();

        $this->assertSame('6070', $article->fresh()->compte_comptable);
    }

    /** Refuser la confirmation n'écrit rien. */
    public function test_refuser_la_confirmation_n_ecrit_rien(): void
    {
        $article = $this->article(['code' => 'CONS-00003']);

        $chemin = $this->csv("CONS-00003;6063\n");

        $this->artisan('catalogue:importer-comptes', ['fichier' => $chemin])
            ->expectsConfirmation('Appliquer 1 imputation(s) ?', 'no')
            ->assertSuccessful();

        $this->assertNull($article->fresh()->compte_comptable);
    }

    /** Le mode simulation montre le rapport sans rien toucher. */
    public function test_la_simulation_n_ecrit_rien(): void
    {
        $article = $this->article(['code' => 'CONS-00004']);

        $chemin = $this->csv("CONS-00004;6063\n");

        $this->artisan('catalogue:importer-comptes', ['fichier' => $chemin, '--simuler' => true])
            ->assertSuccessful();

        $this->assertNull($article->fresh()->compte_comptable);
    }

    /**
     * Écraser une imputation déjà saisie n'est pas anodin : la commande le
     * compte à part, et le montre avant d'écrire.
     */
    public function test_les_ecrasements_sont_distingues_des_creations(): void
    {
        $vierge = $this->article(['code' => 'CONS-00005']);
        $deja = $this->article(['code' => 'CONS-00006', 'compte_comptable' => '6060']);

        $chemin = $this->csv("CONS-00005;6063\nCONS-00006;6099\n");

        $this->artisan('catalogue:importer-comptes', ['fichier' => $chemin])
            ->expectsOutputToContain('6060 → 6099')
            ->expectsConfirmation('Appliquer 2 imputation(s) ?', 'yes')
            ->assertSuccessful();

        $this->assertSame('6063', $vierge->fresh()->compte_comptable);
        $this->assertSame('6099', $deja->fresh()->compte_comptable);
    }

    public function test_un_fichier_illisible_est_refuse_proprement(): void
    {
        $this->artisan('catalogue:importer-comptes', ['fichier' => '/tmp/aucun-fichier-ici.csv'])
            ->assertFailed();
    }

    /** L'import passe par `update` : le journal enregistre qui a imputé quoi. */
    public function test_l_import_est_journalise(): void
    {
        $article = $this->article(['code' => 'CONS-00007']);

        $chemin = $this->csv("CONS-00007;6063\n");

        $this->artisan('catalogue:importer-comptes', ['fichier' => $chemin])
            ->expectsConfirmation('Appliquer 1 imputation(s) ?', 'yes')
            ->assertSuccessful();

        $this->assertTrue(
            \Modules\Core\Models\Activity::query()
                ->where('subject_type', Article::class)
                ->where('subject_id', $article->id)
                ->where('event', 'updated')
                ->exists(),
            'La modification doit figurer au journal.'
        );
    }
}
