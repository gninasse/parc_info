<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Stock\Models\Inventaire;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Niveau;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NiveauController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:stock.niveaux.index', only: ['index', 'getData', 'export']),
            new Middleware('permission:stock.niveaux.seuil', only: ['seuil', 'seuilArticle']),
        ];
    }

    public function index(Request $request)
    {
        $magasins = Magasin::query()->orderBy('libelle')->get(['id', 'code', 'libelle']);

        return view('stock::niveaux.index', [
            'magasins' => $magasins,
            'magasinPreselectionne' => (int) $request->input('magasin_id') ?: null,
            'statutPreselectionne' => $request->input('statut') ?: null,
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $query = $this->requeteFiltre($request);

        $sort = $request->input('sort', 'article');
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        match ($sort) {
            'quantite' => $query->orderBy('stock_niveaux.quantite', $order),
            'magasin' => $query->orderBy('stock_magasins.libelle', $order),
            'valeur' => $query->orderByRaw("stock_niveaux.quantite * COALESCE(catalogue_articles.prix_indicatif, 0) {$order}"),
            'seuil_effectif' => $query->orderByRaw("COALESCE(stock_niveaux.seuil, catalogue_articles.seuil_defaut) {$order}"),
            default => $query->orderBy('catalogue_articles.nom', $order),
        };

        $total = $query->count();

        $sousInventaire = $this->articlesSousInventaire();

        $rows = $query
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(fn (Niveau $niveau) => $this->presenterNiveau($niveau, $sousInventaire));

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    /** PATCH /niveaux/{id}/seuil — modale MD-SEUIL. */
    public function seuil(Request $request, $id): JsonResponse
    {
        $valide = $request->validate(
            ['seuil' => ['nullable', 'numeric', 'min:0']],
            ['seuil.min' => 'Le seuil ne peut pas être négatif.']
        );

        $niveau = Niveau::query()->with('article:id,nom,seuil_defaut')->findOrFail($id);
        $niveau->update(['seuil' => $valide['seuil'] ?? null]);
        $niveau->refresh();

        return response()->json([
            'success' => true,
            'message' => $niveau->seuil === null
                ? "Seuil local retiré : « {$niveau->article->nom} » hérite du seuil de l'article."
                : "Seuil local de « {$niveau->article->nom} » enregistré.",
            'data' => [
                'seuil_effectif' => $niveau->seuil_effectif,
                'seuil_origine' => $niveau->seuil_origine,
            ],
        ]);
    }

    /**
     * POST /niveaux/seuil-article — pose d'un seuil sur un article jamais
     * reçu : crée sa ligne de niveau à 0 (décision SFD §8/UX MD-SEUIL,
     * assumée : la ligne apparaît dès lors dans l'état des stocks, en
     * RUPTURE, ce qui est exactement l'intention du seuil d'alerte).
     */
    public function seuilArticle(Request $request): JsonResponse
    {
        $valide = $request->validate([
            'magasin_id' => ['required', 'integer', 'exists:stock_magasins,id'],
            'article_id' => ['required', 'integer', 'exists:catalogue_articles,id'],
            'seuil' => ['required', 'numeric', 'min:0'],
        ]);

        $article = Article::query()->findOrFail((int) $valide['article_id']);

        if (! $article->est_stockable) {
            return response()->json([
                'success' => false,
                'message' => "L'article « {$article->nom} » n'est pas stockable : aucun seuil de stock possible.",
            ], 422);
        }

        $niveau = Niveau::query()->firstOrCreate(
            ['magasin_id' => (int) $valide['magasin_id'], 'article_id' => $article->id],
            ['quantite' => 0]
        );

        $niveau->update(['seuil' => $valide['seuil']]);

        return response()->json([
            'success' => true,
            'message' => $niveau->wasRecentlyCreated
                ? "Ligne créée à 0 pour « {$article->nom} » avec son seuil local."
                : "Seuil local de « {$article->nom} » enregistré.",
            'data' => ['id' => $niveau->id],
        ]);
    }

    /**
     * Export CSV / Excel / PDF de l'état des stocks filtré. Les filtres
     * actifs sont imprimés en en-tête de chaque export (amendement UX n°18) ;
     * le PDF porte le cartouche d'audit S9.
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'csv');

        $sousInventaire = $this->articlesSousInventaire();
        $lignes = $this->requeteFiltre($request)
            ->orderBy('stock_magasins.libelle')
            ->orderBy('catalogue_articles.nom')
            ->get()
            ->map(fn (Niveau $niveau) => $this->presenterNiveau($niveau, $sousInventaire));

        $filtres = $this->libellesFiltres($request);
        $horodatage = now()->format('Ymd-His');

        return match ($format) {
            'xlsx' => (new FastExcel($this->lignesExportAvecFiltres($lignes, $filtres)))->download("etat-stocks-{$horodatage}.xlsx"),
            'pdf' => Pdf::loadView('stock::pdf.niveaux', [
                'lignes' => $lignes,
                'filtres' => $filtres,
                'genereLe' => now(),
                'generePar' => auth()->user()?->name,
            ])->setPaper('a4', 'landscape')->download("etat-stocks-{$horodatage}.pdf"),
            default => $this->exportCsv($lignes, $filtres, $horodatage),
        };
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    /** Jointure articles/magasins + filtres UX §2, calculs portables (COALESCE/CASE). */
    private function requeteFiltre(Request $request): Builder
    {
        $query = Niveau::query()
            ->join('catalogue_articles', 'catalogue_articles.id', '=', 'stock_niveaux.article_id')
            ->join('stock_magasins', 'stock_magasins.id', '=', 'stock_niveaux.magasin_id')
            ->with(['article:id,code,nom,nature,unite_stock,prix_indicatif,seuil_defaut', 'magasin:id,code,libelle'])
            ->select('stock_niveaux.*');

        if ($request->filled('magasin_id')) {
            $query->where('stock_niveaux.magasin_id', (int) $request->input('magasin_id'));
        }

        if ($request->filled('nature')) {
            $query->where('catalogue_articles.nature', $request->input('nature'));
        }

        if ($request->filled('categorie_id')) {
            $categorieId = (int) $request->input('categorie_id');
            $ids = Categorie::query()->where('parent_id', $categorieId)->pluck('id')->push($categorieId);
            $query->whereIn('catalogue_articles.categorie_id', $ids);
        }

        if ($request->filled('statut')) {
            $query->whereRaw(Niveau::sqlStatutAlerte().' = ?', [$request->input('statut')]);
        }

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(catalogue_articles.code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(catalogue_articles.nom) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query;
    }

    /**
     * Articles sous inventaire EN_COURS, par magasin (I10 — affichage grisé
     * + cadenas) : périmètre « magasin » = tous ses articles, « selection »
     * = les articles de la feuille.
     */
    private function articlesSousInventaire(): array
    {
        $resultat = [];

        $inventaires = Inventaire::query()->enCours()->with('lignes:id,inventaire_id,article_id')->get();

        foreach ($inventaires as $inventaire) {
            if ($inventaire->perimetre === Inventaire::PERIMETRE_MAGASIN) {
                $resultat[$inventaire->magasin_id]['*'] = $inventaire->numero_affiche;

                continue;
            }

            foreach ($inventaire->lignes as $ligne) {
                if ($ligne->article_id !== null) {
                    $resultat[$inventaire->magasin_id][$ligne->article_id] = $inventaire->numero_affiche;
                }
            }
        }

        return $resultat;
    }

    private function presenterNiveau(Niveau $niveau, array $sousInventaire): array
    {
        $reference = $sousInventaire[$niveau->magasin_id]['*']
            ?? $sousInventaire[$niveau->magasin_id][$niveau->article_id]
            ?? null;

        return [
            'id' => $niveau->id,
            // D-21 : l'identifiant de l'ARTICLE (et non celui du niveau) est
            // ce que l'API d'Achat attend pour ouvrir un brouillon.
            'article_id' => $niveau->article_id,
            'article_code' => $niveau->article->code,
            'article_nom' => $niveau->article->nom,
            'nature' => $niveau->article->nature,
            'magasin' => $niveau->magasin->libelle,
            'quantite' => (float) $niveau->quantite,
            'unite' => $niveau->article->unite_stock,
            'seuil_effectif' => $niveau->seuil_effectif,
            'seuil_origine' => $niveau->seuil_origine,
            'statut' => $niveau->statut_alerte,
            'valeur' => (float) $niveau->quantite * (float) ($niveau->article->prix_indicatif ?? 0),
            'sous_inventaire' => $reference !== null,
            'inventaire_reference' => $reference,
        ];
    }

    /** Filtres actifs en clair — imprimés en en-tête d'export (amendement n°18). */
    private function libellesFiltres(Request $request): array
    {
        $filtres = [];

        if ($request->filled('magasin_id')) {
            $filtres['Magasin'] = Magasin::query()->find((int) $request->input('magasin_id'))?->libelle ?? '?';
        }

        if ($request->filled('nature')) {
            $filtres['Nature'] = Article::NATURE_LABELS[$request->input('nature')] ?? $request->input('nature');
        }

        if ($request->filled('categorie_id')) {
            $filtres['Catégorie'] = Categorie::query()->find((int) $request->input('categorie_id'))?->libelle ?? '?';
        }

        if ($request->filled('statut')) {
            $filtres['Statut d\'alerte'] = match ($request->input('statut')) {
                'OK' => '✓ OK', 'SOUS_SEUIL' => '⚠ Sous seuil', 'RUPTURE' => '⛔ Rupture', default => $request->input('statut'),
            };
        }

        if ($request->filled('search')) {
            $filtres['Recherche'] = $request->input('search');
        }

        return $filtres === [] ? ['Filtres' => 'aucun (état complet)'] : $filtres;
    }

    private function lignesExport(Collection $lignes): Collection
    {
        return $lignes->map(fn (array $ligne) => [
            'Article' => $ligne['article_code'].' — '.$ligne['article_nom'],
            'Nature' => Article::NATURE_LABELS[$ligne['nature']] ?? $ligne['nature'],
            'Magasin' => $ligne['magasin'],
            'Quantité' => $ligne['quantite'],
            'Unité' => $ligne['unite'],
            'Seuil effectif' => $ligne['seuil_effectif'] ?? '—',
            'Origine du seuil' => $ligne['seuil_origine'] ?? '—',
            'Statut' => $ligne['statut'],
            'Valeur (FCFA)' => $ligne['valeur'],
        ]);
    }

    /** XLSX : les filtres actifs occupent les premières lignes (amendement n°18). */
    private function lignesExportAvecFiltres(Collection $lignes, array $filtres): Collection
    {
        $vide = array_fill_keys(['Nature', 'Magasin', 'Quantité', 'Unité', 'Seuil effectif', 'Origine du seuil', 'Statut', 'Valeur (FCFA)'], '');

        $entete = collect($filtres)
            ->map(fn ($valeur, $libelle) => array_merge(['Article' => "{$libelle} : {$valeur}"], $vide))
            ->values()
            ->prepend(array_merge(['Article' => 'État des stocks — exporté le '.now()->format('d/m/Y H:i')], $vide))
            ->push(array_merge(['Article' => ''], $vide));

        return $entete->concat($this->lignesExport($lignes));
    }

    private function exportCsv(Collection $lignes, array $filtres, string $horodatage): StreamedResponse
    {
        return response()->streamDownload(function () use ($lignes, $filtres) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\u{FEFF}"); // BOM UTF-8 pour Excel

            // Filtres imprimés en en-tête (amendement UX n°18)
            fputcsv($sortie, ['État des stocks — exporté le '.now()->format('d/m/Y H:i')], ';');
            foreach ($filtres as $libelle => $valeur) {
                fputcsv($sortie, [$libelle.' : '.$valeur], ';');
            }
            fputcsv($sortie, [], ';');

            fputcsv($sortie, ['Article', 'Nature', 'Magasin', 'Quantité', 'Unité', 'Seuil effectif', 'Origine du seuil', 'Statut', 'Valeur (FCFA)'], ';');

            foreach ($this->lignesExport($lignes) as $ligne) {
                fputcsv($sortie, array_values($ligne), ';');
            }

            fclose($sortie);
        }, "etat-stocks-{$horodatage}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
