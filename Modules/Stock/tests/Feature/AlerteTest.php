<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Achat\Models\Article;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Marque;
use Modules\Stock\Emails\LowStockAlertMail;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Services\SortieStockService;
use Modules\Stock\Services\StockArticleService;
use Tests\TestCase;

class AlerteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Magasin $magasin;

    protected Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Bypass permission checks
        \Illuminate\Support\Facades\Gate::before(fn () => true);

        // Create store
        $this->magasin = Magasin::factory()->create(['est_actif' => true]);

        // Create article
        $marque = Marque::create(['libelle' => 'Lenovo']);
        $categorie = CategorieEquipement::create(['code' => 'ordinateur', 'libelle' => 'Ordinateurs']);

        $this->article = Article::create([
            'code_article' => 'ART-ALERT-1',
            'designation' => 'Lenovo T490',
            'type_article' => 'equipement',
            'marque_id' => $marque->id,
            'categorie_equipement_id' => $categorie->id,
            'prix_indicatif' => 600.00,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'actif' => true,
            'seuil_alerte' => 5, // Alert threshold at 5
        ]);
    }

    public function test_can_access_dashboard(): void
    {
        // Initialise some stock
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->magasin->id, $this->article->id, 4, 600.00); // 4 units <= 5 threshold (will show in critical stocks)

        $response = $this->actingAs($this->user)->get(route('stock.dashboard.index'));
        $response->assertStatus(200);
        $response->assertSee('Tableau de Bord des Stocks');
        $response->assertSee('Lenovo T490'); // Critical article designation visible
    }

    public function test_sortie_triggers_low_stock_mail_notification(): void
    {
        Mail::fake();

        // Initialise stock at 10 (above threshold 5)
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->magasin->id, $this->article->id, 10, 600.00);

        // Create supplier, consumable type, consumable and target equipment in ParcInfo
        $fournisseur = \Modules\ParcInfo\Models\Fournisseur::create([
            'code' => 'FOUR-DELL-ALERT',
            'nom' => 'Dell France Alert',
            'est_actif' => true,
        ]);

        $typeCons = \Modules\ParcInfo\Models\TypeConsommable::create([
            'code' => 'GEN-CONS-ALERT',
            'nom' => 'Consommables Divers Alert',
            'categorie' => 'Accessoires',
            'unite_stock' => 'Unité',
            'seul_reapprovisionnement' => 5,
        ]);

        $consommable = \Modules\ParcInfo\Models\Consommable::create([
            'code' => $this->article->code_article,
            'nom' => $this->article->designation,
            'type_consommable_id' => $typeCons->id,
            'marque_id' => $this->article->marque_id,
            'cout_unitaire' => 600.00,
            'quantite_stock_actuel' => 50,
            'quantite_stock_min' => 5,
            'est_actif' => true,
            'fournisseur_principal_id' => $fournisseur->id,
        ]);

        $eqTarget = \Modules\ParcInfo\Models\Equipement::create([
            'categorie_id' => $this->article->categorie_equipement_id,
            'code_inventaire' => 'INV-TARGET-ALERT',
            'numero_serie' => 'SN-TARGET-ALERT',
            'marque_id' => $this->article->marque_id,
            'modele' => 'Latitude 5490 Target Alert',
            'statut' => 'en_service',
            'etat' => 'BON',
        ]);

        // Perform a stock release of 6 (reducing stock to 4, which is <= 5)
        app(SortieStockService::class)->creerSortie(
            $this->magasin->id,
            $this->article->id,
            6,
            'CONSOMMABLE',
            'SERVICE',
            1,
            [
                'motif' => 'Ajustement test',
                'equipement_destination_id' => $eqTarget->id,
            ],
            $this->user->id
        );

        // Assert mail was sent
        Mail::assertSent(LowStockAlertMail::class, function ($mail) {
            return $mail->stock->article_id === $this->article->id &&
                   $mail->stock->quantite_actuelle === 4;
        });
    }

    public function test_check_alerts_command_detects_low_stocks(): void
    {
        Mail::fake();

        // Initialise stock at 3 (below threshold 5)
        $stockService = app(StockArticleService::class);
        $stockService->initialiser($this->magasin->id, $this->article->id, 3, 600.00);

        // Run check alerts command
        $this->artisan('stock:check-alerts')
            ->expectsOutput('Vérification des niveaux de stocks...')
            ->expectsOutput('1 alerte(s) détectée(s) et notifiée(s).')
            ->assertExitCode(0);

        // Assert mail sent
        Mail::assertSent(LowStockAlertMail::class);
    }
}
