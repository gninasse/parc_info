<?php

namespace Modules\Stock\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\LigneSortie;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Services\MouvementService;
use Modules\Stock\Services\NumerotationService;

/**
 * Jeu de démonstration (HORS production) : deux réceptions validées, une
 * sortie validée et un ajustement correctif — tout passe par
 * MouvementService, donc niveaux = Σ mouvements par construction
 * (stock:controle-coherence doit renvoyer 0 écart).
 */
class StockDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('StockDemoSeeder ignoré en production.');

            return;
        }

        $mouvements = app(MouvementService::class);
        $numerotation = app(NumerotationService::class);

        $magasins = Magasin::query()->actifs()->orderBy('id')->take(2)->get();

        if ($magasins->isEmpty()) {
            $this->command?->error('Aucun magasin actif : exécutez StockMagasinsSeeder d\'abord.');

            return;
        }

        $magasin = $magasins->first();

        $categorie = Categorie::query()->firstOrCreate(
            ['code' => 'CAT-DEMO-STK'],
            ['libelle' => 'Démo Stock', 'est_actif' => true]
        );

        $articles = collect([
            ['nom' => 'Cartouche encre noire (démo)', 'seuil_defaut' => 5, 'prix_indicatif' => 38000],
            ['nom' => 'Câble RJ45 3 m (démo)', 'seuil_defaut' => 10, 'prix_indicatif' => 2500],
            ['nom' => 'Ramette A4 80 g (démo)', 'seuil_defaut' => 20, 'prix_indicatif' => 3500],
        ])->map(fn (array $attributs) => Article::query()->firstOrCreate(
            ['nom' => $attributs['nom']],
            array_merge($attributs, [
                'nature' => Article::NATURE_CONSOMMABLE,
                'categorie_id' => $categorie->id,
                'unite_stock' => 'unité',
                'est_actif' => true,
            ])
        ));

        // Idempotence simple : ne rejoue pas la démo si elle existe déjà.
        if (Entree::query()->where('reference_externe', 'BL-DEMO-0001')->exists()) {
            $this->command?->info('Jeu de démonstration déjà présent — rien à faire.');

            return;
        }

        // ── Réception validée ──────────────────────────────────────────────
        $entree = Entree::query()->create([
            'date_document' => now()->subDays(7)->toDateString(),
            'magasin_id' => $magasin->id,
            'nature' => Entree::NATURE_LIVRAISON,
            'reference_externe' => 'BL-DEMO-0001',
            'observation_type' => 'livraison_conforme',
        ]);

        foreach ($articles as $article) {
            LigneEntree::query()->create([
                'entree_id' => $entree->id,
                'article_id' => $article->id,
                'quantite' => 30,
                'cout_unitaire' => $article->prix_indicatif,
            ]);

            $mouvements->entree([
                'entree_id' => $entree->id,
                'magasin_id' => $magasin->id,
                'article_id' => $article->id,
                'quantite' => 30,
                'cout_unitaire' => $article->prix_indicatif,
            ]);
        }

        $entree->valider();
        $numerotation->attribuer($entree);

        // ── Sortie validée (dotation) ──────────────────────────────────────
        $sortie = Sortie::query()->create([
            'date_document' => now()->subDays(2)->toDateString(),
            'magasin_id' => $magasin->id,
            'motif_type' => 'dotation_periodique',
            'beneficiaire_type' => 'service',
            'beneficiaire_libelle' => 'Service informatique (démo)',
        ]);

        LigneSortie::query()->create([
            'sortie_id' => $sortie->id,
            'article_id' => $articles[0]->id,
            'quantite' => 8,
        ]);

        $mouvements->sortie([
            'sortie_id' => $sortie->id,
            'magasin_id' => $magasin->id,
            'article_id' => $articles[0]->id,
            'quantite' => 8,
        ]);

        $sortie->valider();
        $numerotation->attribuer($sortie);

        // ── Contre-mouvement correctif (le bon entier est annulé) ──────────
        $mouvements->contreMouvement(
            $sortie->mouvements()->first(),
            'Correction démo : bon saisi en double, corrigé par contre-mouvement'
        );

        $this->command?->info('Jeu de démonstration Stock créé (1 entrée, 1 sortie, 1 contre-mouvement).');
    }
}
