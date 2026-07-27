<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Achat\Models\Article;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\Marque;
use Modules\Stock\Models\Magasin;
use Tests\TestCase;

/**
 * Socle des tests fonctionnels du module Stock.
 *
 * Les intégrations Achat, GRH et Organisation sont réelles : en monolithe,
 * toutes les tables existent dans la base de test. Seule l'intégration
 * ParcInfo (sorties, P4) est doublée au besoin par les tests concernés.
 */
abstract class StockTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $utilisateur;

    protected Marque $marque;

    protected function setUp(): void
    {
        parent::setUp();

        $this->utilisateur = User::factory()->create();
        $this->actingAs($this->utilisateur);

        // Les habilitations sont vérifiées dans HabilitationsTest.
        Gate::before(fn () => true);

        $this->marque = Marque::create(['libelle' => 'HP']);
    }

    // ── Fabriques de données ───────────────────────────────────────────────

    protected function creerMagasin(array $attributs = []): Magasin
    {
        return Magasin::factory()->create($attributs);
    }

    protected function creerConsommable(array $attributs = []): Article
    {
        return Article::factory()->consommable()->create(array_merge([
            'marque_id' => $this->marque->id,
        ], $attributs));
    }

    protected function creerEquipement(array $attributs = []): Article
    {
        return Article::factory()->create(array_merge([
            'marque_id' => $this->marque->id,
        ], $attributs));
    }
}
