<?php

namespace Modules\Stock\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Exceptions\MouvementImmuableException;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Models\Transfert;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ModelesTest extends TestCase
{
    use RefreshDatabase;

    public function test_numero_affiche_brouillon_puis_numero_definitif(): void
    {
        $brouillon = Entree::factory()->create();
        $this->assertSame('Brouillon #'.$brouillon->id, $brouillon->numero_affiche);

        $validee = Entree::factory()->validee()->create();
        $this->assertSame($validee->numero, $validee->numero_affiche);
        $this->assertStringStartsWith('ENT-', $validee->numero_affiche);
    }

    public function test_statut_label_oriente_geste(): void
    {
        // Amendement UX n°2 : l'étiquette parle le geste, la base garde le nom technique
        $this->assertSame('Saisie des n° de série', Entree::factory()->enReferencement()->create()->statut_label);
        $this->assertSame('Pointage en cours', Sortie::factory()->enPointage()->create()->statut_label);
        $this->assertSame('Pointage en cours', Transfert::factory()->enPointage()->create()->statut_label);
        $this->assertSame('Brouillon', Entree::factory()->create()->statut_label);
        $this->assertSame('Validé', Sortie::factory()->validee()->create()->statut_label);
    }

    public function test_scopes_valides_non_valides_du_magasin(): void
    {
        $magasin = Magasin::factory()->create();
        Entree::factory()->create(['magasin_id' => $magasin->id]);
        Entree::factory()->validee()->create(['magasin_id' => $magasin->id]);
        Entree::factory()->validee()->create(); // autre magasin

        $this->assertSame(2, Entree::valides()->count());
        $this->assertSame(1, Entree::nonValides()->count());
        $this->assertSame(2, Entree::duMagasin($magasin->id)->count());
        $this->assertSame(1, Entree::valides()->duMagasin($magasin->id)->count());
    }

    public function test_scope_du_magasin_transfert_source_ou_cible(): void
    {
        $magasin = Magasin::factory()->create();
        Transfert::factory()->create(['magasin_source_id' => $magasin->id]);
        Transfert::factory()->create(['magasin_cible_id' => $magasin->id]);
        Transfert::factory()->create();

        $this->assertSame(2, Transfert::duMagasin($magasin->id)->count());
    }

    public function test_seuil_effectif_cascade_a_trois_cas(): void
    {
        // 1. seuil local prioritaire
        $local = Niveau::factory()->create(['seuil' => 7]);
        $local->article->update(['seuil_defaut' => 3]);
        $local->refresh();
        $this->assertSame(7.0, $local->seuil_effectif);
        $this->assertSame(Niveau::ORIGINE_SEUIL_LOCAL, $local->seuil_origine);

        // 2. repli sur le seuil par défaut de l'article
        $article = Article::factory()->consommable()->create(['seuil_defaut' => 4]);
        $herite = Niveau::factory()->create(['article_id' => $article->id, 'seuil' => null]);
        $this->assertSame(4.0, $herite->seuil_effectif);
        $this->assertSame(Niveau::ORIGINE_SEUIL_ARTICLE, $herite->seuil_origine);

        // 3. aucun seuil
        $articleSans = Article::factory()->consommable()->create(['seuil_defaut' => null]);
        $sans = Niveau::factory()->create(['article_id' => $articleSans->id, 'seuil' => null]);
        $this->assertNull($sans->seuil_effectif);
        $this->assertNull($sans->seuil_origine);
    }

    public function test_statut_alerte_ok_sous_seuil_rupture(): void
    {
        $article = Article::factory()->consommable()->create(['seuil_defaut' => 5]);

        $this->assertSame(Niveau::STATUT_OK, Niveau::factory()->create(['article_id' => $article->id, 'quantite' => 12])->statut_alerte);
        $this->assertSame(Niveau::STATUT_SOUS_SEUIL, Niveau::factory()->create(['article_id' => $article->id, 'quantite' => 5])->statut_alerte);
        $this->assertSame(Niveau::STATUT_RUPTURE, Niveau::factory()->create(['article_id' => $article->id, 'quantite' => 0])->statut_alerte);
    }

    public function test_journal_immuable_update_refuse(): void
    {
        $mouvement = Mouvement::factory()->create();
        $quantiteInitiale = (float) $mouvement->quantite;

        try {
            $mouvement->update(['quantite' => 99]);
            $this->fail('L\'update aurait dû être refusé (S1).');
        } catch (MouvementImmuableException) {
        }

        $this->assertSame($quantiteInitiale, (float) $mouvement->fresh()->quantite);
    }

    public function test_journal_immuable_delete_refuse(): void
    {
        $mouvement = Mouvement::factory()->create();

        try {
            $mouvement->delete();
            $this->fail('Le delete aurait dû être refusé (S1).');
        } catch (MouvementImmuableException) {
        }

        $this->assertDatabaseHas('stock_mouvements', ['id' => $mouvement->id]);
    }

    public function test_quantite_signee_en_toutes_lettres(): void
    {
        $entree = Mouvement::factory()->create(['quantite' => 2]);
        $this->assertSame('+2', $entree->quantite_signee);

        $sortie = Mouvement::factory()->create(['sens' => -1, 'type' => Mouvement::TYPE_SORTIE, 'quantite' => 2]);
        $this->assertSame('−2', $sortie->quantite_signee);
    }

    public function test_tous_les_modeles_journalisent_avec_module_stock(): void
    {
        Magasin::factory()->create();

        $activite = Activity::query()->latest('id')->first();

        $this->assertNotNull($activite);
        $this->assertSame('stock', $activite->log_name);
        $this->assertSame('stock', $activite->module);
    }

    /**
     * I6 (partiel — sera complété avec les contrôleurs) : aucune route
     * applicative d'UPDATE/DELETE sur le journal des mouvements.
     */
    public function test_invariant_I6_aucune_route_de_modification_des_mouvements(): void
    {
        $routesStock = collect(app('router')->getRoutes()->getRoutesByName())
            ->filter(fn ($route, string $nom) => str_starts_with($nom, 'stock.'));

        $this->assertTrue($routesStock->isNotEmpty());

        foreach ($routesStock as $nom => $route) {
            if (str_contains($nom, 'mouvements')) {
                $this->assertEmpty(
                    array_intersect(['PUT', 'PATCH', 'DELETE'], $route->methods()),
                    "La route {$nom} expose une modification du journal (S1)."
                );
            }
        }
    }
}
