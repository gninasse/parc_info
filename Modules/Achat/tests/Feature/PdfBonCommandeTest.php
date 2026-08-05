<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\MontantEnLettres;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

/**
 * D-07 — Le PDF du bon de commande (SPEC_UX §17).
 *
 * Le PDF est l'objet juridique signé : les tests vérifient son CONTENU réel
 * (texte extrait du binaire), pas seulement un code 200 — un PDF qui s'ouvre
 * mais imprime un montant faux serait pire qu'une erreur.
 */
class PdfBonCommandeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);
    }

    private function lecteur(array $permissions = ['achat.dashboard.view', 'achat.bons_commande.index'], string $email = 'pdf@example.com'): User
    {
        $existant = User::query()->where('email', $email)->first();

        if ($existant !== null) {
            return $existant;
        }

        $user = User::create([
            'name' => 'Lecteur PDF',
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

    private function bonValide(array $attributs = []): BonCommande
    {
        $bon = BonCommande::factory()->valide()->create($attributs);

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'designation' => 'Ordinateur portable Dell Latitude 3540',
            'quantite' => 10,
            'prix_unitaire_ht' => 830000,
            'taux_tva' => 18,
        ]);

        app(\Modules\Achat\Services\CalculMontantsService::class)->recalculer($bon);

        return $bon->refresh();
    }

    /**
     * Texte réellement imprimé, extrait du binaire PDF.
     *
     * Le parseur insère des retours à la ligne au gré de la mise en page : on
     * normalise les blancs pour que les assertions portent sur le TEXTE et
     * non sur le découpage en lignes, qui n'a aucune valeur contractuelle.
     */
    private function texteDuPdf(User $utilisateur, BonCommande $bon): string
    {
        $reponse = $this->actingAs($utilisateur)
            ->get(route('achat.bons-commande.pdf', $bon->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $texte = (new Parser)->parseContent($reponse->getContent())->getText();

        return preg_replace('/\s+/u', ' ', $texte);
    }

    // ═══ Contenus (SPEC_UX §17) ════════════════════════════════════════════

    public function test_le_pdf_porte_le_numero_les_lignes_et_les_totaux(): void
    {
        $bon = $this->bonValide();
        $texte = $this->texteDuPdf($this->lecteur(), $bon);

        $this->assertStringContainsString($bon->numero, $texte);
        $this->assertStringContainsString('Bon de commande', $texte);
        $this->assertStringContainsString('Ordinateur portable Dell Latitude 3540', $texte);
        // 10 × 830 000 = 8 300 000 HT ; TVA 1 494 000 ; TTC 9 794 000
        $this->assertStringContainsString('8 300 000', $texte);
        $this->assertStringContainsString('9 794 000', $texte);
        $this->assertStringContainsString('Total TTC', $texte);
    }

    /**
     * IA-1 sur papier : le PDF imprime les montants DE LA BASE. Si une écriture
     * directe les corrompt, le PDF reflète la base — il ne recalcule pas.
     */
    public function test_le_pdf_imprime_les_montants_de_la_base(): void
    {
        $bon = $this->bonValide();

        \Illuminate\Support\Facades\DB::table('achat_bons_commande')
            ->where('id', $bon->id)
            ->update(['montant_ttc' => 1234567]);

        $texte = $this->texteDuPdf($this->lecteur(), $bon);

        $this->assertStringContainsString(
            '1 234 567',
            $texte,
            'Le PDF a recalculé au lieu d\'imprimer la base : deux vérités possibles.'
        );
    }

    public function test_le_montant_ttc_figure_en_toutes_lettres(): void
    {
        $bon = $this->bonValide();
        $texte = $this->texteDuPdf($this->lecteur(), $bon);

        // 9 794 000 → « neuf millions sept cent quatre-vingt-quatorze mille francs CFA »
        $this->assertStringContainsString('neuf millions sept cent quatre-vingt-quatorze mille francs CFA', $texte);
        $this->assertStringContainsString('Arrêté le présent bon de commande à la somme de', $texte);
    }

    public function test_le_pdf_porte_le_libelle_fournisseur_fige(): void
    {
        $bon = $this->bonValide(['fournisseur_libelle' => 'Sonabel-Info (au moment de la commande)']);

        $this->assertStringContainsString(
            'Sonabel-Info (au moment de la commande)',
            $this->texteDuPdf($this->lecteur(), $bon)
        );
    }

    public function test_les_cadres_de_signature_sont_presents(): void
    {
        // Les titres de cadre passent en capitales à l'impression
        // (text-transform) : on compare hors casse et hors apostrophe typée.
        $texte = mb_strtoupper(str_replace('’', "'", $this->texteDuPdf($this->lecteur(), $this->bonValide())));

        $this->assertStringContainsString("L'ACHETEUR", $texte);
        $this->assertStringContainsString('LE VALIDATEUR', $texte);
    }

    public function test_le_qr_du_numero_est_embarque_en_svg(): void
    {
        $bon = $this->bonValide();

        // Le QR est vectoriel dans le HTML source du PDF ; sa présence se
        // vérifie sur le binaire : dompdf y inscrit l'image tramée.
        $reponse = $this->actingAs($this->lecteur())
            ->get(route('achat.bons-commande.pdf', $bon->id))
            ->assertOk();

        $this->assertGreaterThan(
            5000,
            strlen($reponse->getContent()),
            'PDF anormalement petit : le QR ou le gabarit manquent.'
        );
    }

    // ═══ Filigranes selon le statut (SPEC_UX §17) ══════════════════════════

    public function test_un_brouillon_est_filigrane_sans_valeur(): void
    {
        $bon = BonCommande::factory()->create();
        LigneCommande::factory()->create(['bon_commande_id' => $bon->id]);

        $texte = $this->texteDuPdf($this->lecteur(), $bon);

        $this->assertStringContainsString('BROUILLON — SANS VALEUR', $texte);
        $this->assertStringContainsString('Brouillon #'.$bon->id, $texte);
    }

    public function test_une_regularisation_est_filigranee(): void
    {
        $bon = BonCommande::factory()->valide()->regularisation()->create();
        LigneCommande::factory()->create(['bon_commande_id' => $bon->id]);

        $this->assertStringContainsString(
            'RÉGULARISATION',
            $this->texteDuPdf($this->lecteur(), $bon)
        );
    }

    public function test_un_bon_annule_est_filigrane(): void
    {
        // Un bon annulé APRÈS validation garde son numéro (écart n°2) : le
        // filigrane ANNULÉ lève l'ambiguïté si le papier a déjà circulé.
        $bon = BonCommande::factory()->annule()->create(['numero' => 'BC-2026-9001']);
        LigneCommande::factory()->create(['bon_commande_id' => $bon->id]);

        $this->assertStringContainsString(
            'ANNULÉ',
            $this->texteDuPdf($this->lecteur(), $bon)
        );
    }

    public function test_un_bon_valide_n_a_aucun_filigrane(): void
    {
        $texte = $this->texteDuPdf($this->lecteur(), $this->bonValide());

        $this->assertStringNotContainsString('SANS VALEUR', $texte);
        $this->assertStringNotContainsString('RÉGULARISATION', $texte);
        $this->assertStringNotContainsString('ANNULÉ', $texte);
    }

    // ═══ 403 ═══════════════════════════════════════════════════════════════

    public function test_le_pdf_exige_la_permission_index(): void
    {
        $sansDroit = $this->lecteur(['achat.dashboard.view'], 'sanspdf@example.com');
        $bon = $this->bonValide();

        $this->actingAs($sansDroit)
            ->get(route('achat.bons-commande.pdf', $bon->id))
            ->assertForbidden()
            ->assertSee('achat.bons_commande.index');
    }

    // ═══ MontantEnLettres : l'orthographe des grands nombres ═══════════════

    #[DataProvider('montants')]
    public function test_les_montants_en_lettres_sont_orthographies(float $montant, string $attendu): void
    {
        $this->assertSame($attendu, app(MontantEnLettres::class)->enFcfa($montant));
    }

    public static function montants(): array
    {
        return [
            'zéro' => [0, 'zéro franc CFA'],
            'un' => [1, 'un franc CFA'],
            'seize' => [16, 'seize francs CFA'],
            'vingt et un' => [21, 'vingt et un francs CFA'],
            'soixante et onze' => [71, 'soixante et onze francs CFA'],
            'quatre-vingts (s final)' => [80, 'quatre-vingts francs CFA'],
            'quatre-vingt-onze (sans s)' => [91, 'quatre-vingt-onze francs CFA'],
            'cent' => [100, 'cent francs CFA'],
            'deux cents (s final)' => [200, 'deux cents francs CFA'],
            'deux cent trois (sans s)' => [203, 'deux cent trois francs CFA'],
            'mille (invariable)' => [1000, 'mille francs CFA'],
            'deux mille' => [2000, 'deux mille francs CFA'],
            'exemple de la spec' => [5026800, 'cinq millions vingt-six mille huit cents francs CFA'],
            'un million' => [1000000, 'un million francs CFA'],
            'milliard' => [1000000000, 'un milliard francs CFA'],
            'les centimes sont ignorés' => [100.99, 'cent francs CFA'],
        ];
    }
}
