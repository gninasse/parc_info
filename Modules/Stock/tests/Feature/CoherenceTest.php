<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Database\Factories\ParcInfoDeTest;
use Modules\Stock\Database\Seeders\StockDemoSeeder;
use Modules\Stock\Database\Seeders\StockMagasinsSeeder;
use Modules\Stock\Exceptions\StockException;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\Inventaire;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Models\Transfert;
use Modules\Stock\Services\MouvementService;
use Tests\TestCase;

class CoherenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * I3 (property-based simplifié, D9) : une séquence aléatoire SEEDÉE
     * d'entrées/sorties/transferts/ajustements/contre-mouvements — dont une
     * partie échoue légitimement sur les gardes — laisse toujours
     * niveau = Σ mouvements, et la commande de contrôle renvoie 0 écart.
     */
    public function test_invariant_I3_niveau_egale_somme_des_mouvements(): void
    {
        mt_srand(20260803); // séquence reproductible

        $service = app(MouvementService::class);
        $magasins = Magasin::factory()->count(2)->create();
        $articles = Article::factory()->consommable()->count(3)->create(['seuil_defaut' => null]);

        $documents = [
            'entree' => Entree::factory()->validee()->create(['magasin_id' => $magasins[0]->id]),
            'sortie' => Sortie::factory()->validee()->create(['magasin_id' => $magasins[0]->id]),
            'transfert' => Transfert::factory()->validee()->create([
                'magasin_source_id' => $magasins[0]->id,
                'magasin_cible_id' => $magasins[1]->id,
            ]),
            'inventaire' => Inventaire::factory()->valide()->create(['magasin_id' => $magasins[0]->id]),
        ];

        $refus = 0;

        foreach (range(1, 80) as $i) {
            $magasin = $magasins[mt_rand(0, 1)];
            $article = $articles[mt_rand(0, 2)];
            $quantite = mt_rand(1, 9);

            try {
                match (mt_rand(1, 5)) {
                    1 => $service->entree([
                        'entree_id' => $documents['entree']->id,
                        'magasin_id' => $magasin->id,
                        'article_id' => $article->id,
                        'quantite' => $quantite,
                    ]),
                    2 => $service->sortie([
                        'sortie_id' => $documents['sortie']->id,
                        'magasin_id' => $magasin->id,
                        'article_id' => $article->id,
                        'quantite' => $quantite,
                    ]),
                    3 => $service->transfert([
                        'transfert_id' => $documents['transfert']->id,
                        'magasin_source_id' => $magasin->id,
                        'magasin_cible_id' => $magasins->first(fn (Magasin $m) => $m->id !== $magasin->id)->id,
                        'article_id' => $article->id,
                        'quantite' => $quantite,
                    ]),
                    4 => $service->ajustement([
                        'inventaire_id' => $documents['inventaire']->id,
                        'magasin_id' => $magasin->id,
                        'article_id' => $article->id,
                        'quantite' => $quantite,
                        'sens' => mt_rand(0, 1) === 1 ? 1 : -1,
                        'motif' => 'Ajustement aléatoire seedé',
                    ]),
                    5 => (function () use ($service) {
                        $origine = Mouvement::query()
                            ->whereNull('mouvement_origine_id')
                            ->whereNotNull('article_id')
                            ->inRandomOrder()
                            ->first();

                        if ($origine !== null) {
                            $service->contreMouvement($origine, 'Contre aléatoire seedé');
                        }
                    })(),
                };
            } catch (StockException) {
                $refus++; // refus légitime (stock insuffisant, niveau négatif…)
            }
        }

        $this->assertGreaterThan(0, Mouvement::query()->count(), 'La séquence doit produire des mouvements.');

        // Recalcul indépendant : chaque niveau = Σ sens × quantite
        foreach (Niveau::query()->get() as $niveau) {
            $somme = (float) DB::table('stock_mouvements')
                ->where('magasin_id', $niveau->magasin_id)
                ->where('article_id', $niveau->article_id)
                ->selectRaw('COALESCE(SUM(sens * quantite), 0) AS somme')
                ->value('somme');

            $this->assertEqualsWithDelta(
                $somme,
                (float) $niveau->quantite,
                0.001,
                "Dérive sur le niveau #{$niveau->id} (magasin {$niveau->magasin_id}, article {$niveau->article_id})."
            );
        }

        $this->artisan('stock:controle-coherence')->assertExitCode(0);
    }

    public function test_la_commande_detecte_une_derive_de_niveau(): void
    {
        $service = app(MouvementService::class);
        $magasin = Magasin::factory()->create();
        $article = Article::factory()->consommable()->create();
        $entree = Entree::factory()->validee()->create(['magasin_id' => $magasin->id]);

        $service->entree([
            'entree_id' => $entree->id,
            'magasin_id' => $magasin->id,
            'article_id' => $article->id,
            'quantite' => 10,
        ]);

        // Corruption volontaire hors service (le service, lui, ne peut pas dériver)
        DB::table('stock_niveaux')->where('magasin_id', $magasin->id)->update(['quantite' => 15]);

        $this->artisan('stock:controle-coherence')->assertExitCode(1);
    }

    public function test_la_commande_detecte_une_incoherence_equipement(): void
    {
        $rattachement = EquipementMagasin::factory()->create();
        $this->artisan('stock:controle-coherence')->assertExitCode(0);

        // Affectation faite directement côté ParcInfo (§7.3) : statut hors stock
        $rattachement->equipement->update(['statut' => 'en_service']);

        $this->artisan('stock:controle-coherence')->assertExitCode(1);
    }

    public function test_le_seeder_de_demo_laisse_zero_ecart(): void
    {
        $this->seed(StockMagasinsSeeder::class);
        $this->seed(StockDemoSeeder::class);

        $this->assertGreaterThan(0, Mouvement::query()->count());
        $this->assertSame(1, Entree::query()->valides()->count());
        $this->assertSame(1, Sortie::query()->valides()->count());
        $this->assertSame(1, Mouvement::query()->whereNotNull('mouvement_origine_id')->count());

        $this->artisan('stock:controle-coherence')->assertExitCode(0);

        // Idempotent : rejouer ne duplique rien
        $this->seed(StockDemoSeeder::class);
        $this->assertSame(1, Entree::query()->valides()->count());
    }
}
