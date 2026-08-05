<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Services\RapportService;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Les « états » du module Stock : une page d'accueil qui présente le catalogue
 * des états disponibles, puis un écran unique de restitution paramétré par le
 * code de l'état. L'export (CSV / XLSX / PDF) rejoue exactement la même requête
 * que l'écran, filtres compris, ce qui garantit l'égalité affiché/exporté (S9).
 */
class RapportController extends Controller implements HasMiddleware
{
    public function __construct(private readonly RapportService $rapports) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:stock.rapports.view', only: ['index', 'show', 'getData']),
            new Middleware('permission:stock.rapports.export', only: ['export']),
        ];
    }

    /** Accueil : catalogue des états, groupés par famille. */
    public function index()
    {
        return view('stock::rapports.index', [
            'catalogue' => collect(RapportService::catalogue())
                ->map(fn (array $etat, string $code) => $etat + ['code' => $code])
                ->groupBy('famille'),
        ]);
    }

    /** Écran d'un état : filtres + tableau + totaux. */
    public function show(Request $request, string $code)
    {
        abort_unless(RapportService::existe($code), 404);

        $definition = RapportService::catalogue()[$code];

        return view('stock::rapports.show', [
            'code' => $code,
            'definition' => $definition,
            'filtres' => $this->filtres($request),
            'magasins' => Magasin::query()->orderBy('libelle')->get(['id', 'code', 'libelle']),
            'categories' => Categorie::query()->orderBy('libelle')->get(['id', 'libelle']),
            'natures' => Article::NATURE_LABELS,
            'typesMouvement' => Mouvement::TYPE_LABELS,
            'statutsDocument' => $this->statutsDocument($code),
            'motifsSortie' => config('stock.motifs_sortie', []),
            'autres' => collect(RapportService::catalogue())
                ->map(fn (array $etat, string $autreCode) => $etat + ['code' => $autreCode])
                ->groupBy('famille'),
        ]);
    }

    /** Données paginées côté serveur (bootstrap-table). */
    public function getData(Request $request, string $code): JsonResponse
    {
        abort_unless(RapportService::existe($code), 404);

        $etat = $this->rapports->construire($code, $this->filtres($request));

        $lignes = collect($etat['lignes']);
        $offset = (int) $request->input('offset', 0);
        $limite = (int) $request->input('limit', 25);

        return response()->json([
            'total' => $lignes->count(),
            'rows' => $lignes->slice($offset, $limite > 0 ? $limite : null)->values(),
            'colonnes' => $etat['colonnes'],
            'totaux' => $etat['totaux'],
            'resume' => $etat['resume'],
            'titre' => $etat['titre'],
            'filtres_actifs' => $this->rapports->libellesFiltres($code, $this->filtres($request)),
        ]);
    }

    /** Export CSV / XLSX / PDF de l'état filtré, filtres imprimés en en-tête. */
    public function export(Request $request, string $code)
    {
        abort_unless(RapportService::existe($code), 404);

        $filtresBruts = $this->filtres($request);
        $etat = $this->rapports->construire($code, $filtresBruts);
        $filtres = $this->rapports->libellesFiltres($code, $filtresBruts);
        $lignes = $this->rapports->lignesExport($etat);
        $horodatage = now()->format('Ymd-His');
        $fichier = "stock-{$code}-{$horodatage}";

        return match ($request->input('format', 'csv')) {
            'xlsx' => (new FastExcel($this->avecEnTete($lignes, $etat, $filtres)))->download("{$fichier}.xlsx"),
            'pdf' => Pdf::loadView('stock::pdf.rapport', [
                'etat' => $etat,
                'filtres' => $filtres,
                'genereLe' => now(),
                'generePar' => auth()->user()?->name,
            ])->setPaper('a4', 'landscape')->download("{$fichier}.pdf"),
            default => $this->exportCsv($lignes, $etat, $filtres, $fichier),
        };
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function filtres(Request $request): array
    {
        return array_filter([
            'magasin_id' => $request->input('magasin_id'),
            'nature' => $request->input('nature'),
            'categorie_id' => $request->input('categorie_id'),
            'type' => $request->input('type'),
            'statut_document' => $request->input('statut_document'),
            'motif_type' => $request->input('motif_type'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
        ], fn ($valeur) => $valeur !== null && $valeur !== '');
    }

    /** Statuts proposés au filtre, selon le document de l'état. */
    private function statutsDocument(string $code): array
    {
        return match ($code) {
            'entrees' => Entree::STATUT_LABELS,
            'sorties', 'transferts' => Sortie::STATUT_LABELS,
            default => [],
        };
    }

    /** Bandeau d'en-tête commun aux exports tabulaires (amendement UX n°18). */
    private function avecEnTete(Collection $lignes, array $etat, array $filtres): Collection
    {
        $colonnes = collect($etat['colonnes'])->pluck('libelle');
        $premiere = $colonnes->first();
        $vide = array_fill_keys($colonnes->slice(1)->all(), '');

        $entete = collect($filtres)
            ->map(fn ($valeur, $libelle) => array_merge([$premiere => "{$libelle} : {$valeur}"], $vide))
            ->values()
            ->prepend(array_merge([$premiere => $etat['titre'].' — exporté le '.now()->format('d/m/Y H:i')], $vide))
            ->push(array_merge([$premiere => ''], $vide));

        return $entete->concat($lignes)->concat($this->ligneTotaux($etat, $colonnes));
    }

    /** Ligne de totaux, alignée sur les libellés de colonnes. */
    private function ligneTotaux(array $etat, Collection $colonnes): Collection
    {
        if (empty($etat['totaux'])) {
            return collect();
        }

        $ligne = [];
        foreach ($etat['colonnes'] as $colonne) {
            $ligne[$colonne['libelle']] = $etat['totaux'][$colonne['cle']] ?? '';
        }

        return collect([$ligne]);
    }

    private function exportCsv(Collection $lignes, array $etat, array $filtres, string $fichier): StreamedResponse
    {
        $colonnes = collect($etat['colonnes'])->pluck('libelle');

        return response()->streamDownload(function () use ($lignes, $etat, $filtres, $colonnes) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\u{FEFF}"); // BOM UTF-8 pour Excel

            fputcsv($sortie, [$etat['titre'].' — exporté le '.now()->format('d/m/Y H:i')], ';');
            foreach ($filtres as $libelle => $valeur) {
                fputcsv($sortie, [$libelle.' : '.$valeur], ';');
            }
            fputcsv($sortie, [], ';');

            fputcsv($sortie, $colonnes->all(), ';');
            foreach ($lignes as $ligne) {
                fputcsv($sortie, array_values($ligne), ';');
            }

            foreach ($this->ligneTotaux($etat, $colonnes) as $totaux) {
                fputcsv($sortie, array_values($totaux), ';');
            }

            fclose($sortie);
        }, "{$fichier}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
