<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Achat\Contracts\StockIntegrationInterface;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Achat\Services\BonCommandeService;
use Modules\Achat\Services\BordereauLivraisonService;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Marque;
use Tests\TestCase;

/**
 * Socle des tests fonctionnels du module Achat.
 *
 * L'intégration vers le module Stock est remplacée par un double : les tests
 * portent sur les règles du module Achat, pas sur la mécanique FIFO de Stock.
 * C'est précisément ce que permet le contrat StockIntegrationInterface.
 */
abstract class AchatTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $utilisateur;

    protected Marque $marque;

    protected CategorieEquipement $categorie;

    protected Fournisseur $fournisseur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->utilisateur = User::factory()->create();
        $this->actingAs($this->utilisateur);

        // Les habilitations sont vérifiées dans HabilitationsTest.
        Gate::before(fn () => true);

        $this->marque = Marque::create(['libelle' => 'HP']);
        $this->categorie = CategorieEquipement::create([
            'code' => 'ordinateur',
            'libelle' => 'Ordinateurs',
        ]);
        $this->fournisseur = Fournisseur::create([
            'code' => 'FRN-HP',
            'nom' => 'HP Burkina',
            'est_actif' => true,
        ]);

        $this->neutraliserIntegrationStock();
    }

    /** Remplace l'intégration Stock par un double neutre. */
    protected function neutraliserIntegrationStock(): void
    {
        $double = new class implements StockIntegrationInterface
        {
            public array $appels = [];

            public function enregistrerEntreesDepuisBordereau(BordereauLivraison $bordereau, int $userId): int
            {
                $this->appels[] = $bordereau->numero_livraison;

                return $bordereau->lignesLivraison
                    ->filter(fn ($ligne) => $ligne->article->alimenteStock())
                    ->count();
            }

            public function estDisponible(): bool
            {
                return true;
            }
        };

        $this->app->instance(StockIntegrationInterface::class, $double);
    }

    // ── Fabriques de données ───────────────────────────────────────────────

    protected function creerArticle(array $attributs = []): Article
    {
        static $sequence = 0;
        $sequence++;

        return Article::create(array_merge([
            'code_article' => 'ART-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'designation' => 'Article de test '.$sequence,
            'type_article' => 'equipement',
            'marque_id' => $this->marque->id,
            'categorie_equipement_id' => $this->categorie->id,
            'prix_indicatif' => 100000,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'actif' => true,
        ], $attributs));
    }

    protected function creerConsommable(array $attributs = []): Article
    {
        return $this->creerArticle(array_merge([
            'type_article' => 'consommable',
            'categorie_equipement_id' => null,
            'seuil_alerte' => 5,
            'prix_indicatif' => 5000,
        ], $attributs));
    }

    /** Bon de commande en brouillon avec une ligne. */
    protected function creerBonCommande(Article $article, int $quantite = 3, array $attributs = []): BonCommande
    {
        return app(BonCommandeService::class)->creer(
            array_merge([
                'fournisseur_id' => $this->fournisseur->id,
                'date_commande' => now()->toDateString(),
            ], $attributs),
            [[
                'article_id' => $article->id,
                'quantite' => $quantite,
                'prix_unitaire' => 100000,
            ]]
        );
    }

    /** Bon de commande validé, prêt à recevoir une livraison. */
    protected function creerBonCommandeValide(Article $article, int $quantite = 3): BonCommande
    {
        $bonCommande = $this->creerBonCommande($article, $quantite);

        return app(BonCommandeService::class)->valider($bonCommande, $this->utilisateur->id);
    }

    protected function creerBordereau(BonCommande $bonCommande, Article $article, int $quantite): BordereauLivraison
    {
        static $sequence = 0;
        $sequence++;

        return app(BordereauLivraisonService::class)->creer(
            [
                'bon_de_commande_id' => $bonCommande->id,
                'date_livraison' => now()->toDateString(),
                'ref_bordereau_physique' => 'BLPHY-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            ],
            [['article_id' => $article->id, 'quantite_livree' => $quantite]]
        );
    }

    /** Saisie d'inventaire complète pour un article équipement. */
    protected function saisirInventaire(BordereauLivraison $bordereau, Article $article, int $quantite, string $prefixe = 'SN'): void
    {
        $unites = [];

        for ($i = 1; $i <= $quantite; $i++) {
            $unites[] = [
                'numero_serie' => "{$prefixe}-{$bordereau->id}-{$i}",
                'code_inventaire' => '',
                'champs_valeurs' => [],
            ];
        }

        app(\Modules\Achat\Services\WizardValidationService::class)
            ->sauvegarderEtape($bordereau, $article, $unites, null, true);
    }
}
