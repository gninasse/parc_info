<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Achat\Services\AchatParametres;
use Modules\Achat\Services\RegularisationService;
use Modules\Achat\Services\SignauxService;
use Modules\Achat\Services\StatistiquesAchatService;
use Modules\Catalogue\Models\Fournisseur;
use Rap2hpoutre\FastExcel\FastExcel;

/**
 * A-07 — la page Rapports (D-16).
 *
 * Deux exigences structurantes, tenues ici :
 *
 *   - COHÉRENCE CONTRACTUELLE : toutes les cartes lisent le MÊME service que
 *     le tableau de bord (StatistiquesAchatService). Un chiffre affiché deux
 *     fois est calculé une seule fois ;
 *   - QUALIFICATION HT/TTC DANS LE TITRE de chaque export : un tableau de
 *     montants sans mention se lit comme du TTC par défaut, et c'est ainsi
 *     qu'on se trompe de 18 %.
 */
class RapportController extends Controller implements HasMiddleware
{
    /** Les cartes, et ce qu'il faut savoir pour les servir. */
    private const CARTES = [
        'etat-bons' => 'État des bons de commande',
        'depenses-fournisseur' => 'Dépenses par fournisseur',
        'depenses-categorie' => 'Dépenses par catégorie',
        'evolution' => 'Évolution sur 12 mois',
        'reliquats' => 'Reliquats',
        'regularisation' => 'Régularisation de l\'intérim',
        'signaux' => 'Signaux',
    ];

    public function __construct(
        private readonly StatistiquesAchatService $statistiques,
        private readonly SignauxService $signaux,
        private readonly RegularisationService $regularisation,
        private readonly AchatParametres $parametres,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:achat.rapports.view', only: ['index', 'donnees']),
            new Middleware('permission:achat.rapports.export', only: ['export']),
            // La carte Signaux a sa PROPRE permission (UX4-09) : lire des
            // rapports d'activité n'est pas lire des indicateurs de contrôle.
            new Middleware('permission:achat.rapports.signaux', only: ['signaux', 'exportSignaux']),
        ];
    }

    public function index()
    {
        return view('achat::rapports.index', [
            'fournisseurs' => Fournisseur::query()->orderBy('raison_sociale')->get(['id', 'raison_sociale']),
            'cartes' => self::CARTES,
            // UX-18 : l'attente reste VISIBLE — la carte grisée dit ce qui
            // manque plutôt que de laisser croire que rien n'est prévu.
            'imputationDisponible' => \Illuminate\Support\Facades\Schema::hasColumn('catalogue_articles', 'compte_comptable'),
            'detteInterim' => $this->regularisation->detteRestante(),
        ]);
    }

    /** Aperçu d'une carte, à l'écran. */
    public function donnees(Request $request, string $carte): JsonResponse
    {
        abort_unless(array_key_exists($carte, self::CARTES), 404, 'Rapport inconnu.');

        return response()->json([
            'carte' => $carte,
            'titre' => self::CARTES[$carte],
            'lignes' => $this->lignes($carte, $request),
            'filtres' => $this->libellesFiltres($request),
        ]);
    }

    /** Carte Signaux : les 9 indicateurs (SFD §7.7 + BR-04), sous permission dédiée. */
    public function signaux(Request $request): JsonResponse
    {
        return response()->json([
            'signaux' => $this->signaux->tous($request->input('du'), $request->input('au')),
            'filtres' => $this->libellesFiltres($request),
        ]);
    }

    /**
     * Export d'une carte : CSV, XLSX ou PDF.
     *
     * Le titre porte la période, les filtres ET la qualification HT/TTC —
     * un export circule sans son écran, il doit se relire seul.
     */
    public function export(Request $request, string $carte)
    {
        abort_unless(array_key_exists($carte, self::CARTES), 404, 'Rapport inconnu.');

        $lignes = $this->lignes($carte, $request);
        $titre = $this->titreQualifie($carte);
        $filtres = $this->libellesFiltres($request);
        $horodatage = now()->format('Ymd-His');
        $fichier = 'achat-'.$carte.'-'.$horodatage;

        return match ($request->input('format', 'csv')) {
            'xlsx' => (new FastExcel(collect($lignes)))->download($fichier.'.xlsx'),
            'pdf' => Pdf::loadView('achat::pdf.rapport', [
                'titre' => $titre,
                'filtres' => $filtres,
                'lignes' => $lignes,
                'colonnes' => $lignes === [] ? [] : array_keys($lignes[0]),
                'genereLe' => now(),
                'generePar' => auth()->user()?->name,
            ])->setPaper('a4', 'landscape')->download($fichier.'.pdf'),
            default => $this->exportCsv($lignes, $titre, $filtres, $fichier),
        };
    }

    /** Export des signaux : une feuille par indicateur en XLSX, sinon PDF. */
    public function exportSignaux(Request $request)
    {
        $signaux = $this->signaux->tous($request->input('du'), $request->input('au'));
        $horodatage = now()->format('Ymd-His');

        if ($request->input('format') === 'pdf') {
            return Pdf::loadView('achat::pdf.signaux', [
                'signaux' => $signaux,
                'filtres' => $this->libellesFiltres($request),
                'genereLe' => now(),
                'generePar' => auth()->user()?->name,
            ])->setPaper('a4', 'landscape')->download("achat-signaux-{$horodatage}.pdf");
        }

        // Les indicateurs n'ont pas les mêmes colonnes : un onglet chacun.
        $feuilles = collect($signaux)
            ->mapWithKeys(fn (array $signal, string $cle) => [
                // 31 caractères : limite d'Excel pour un nom d'onglet.
                mb_substr($signal['titre'], 0, 31) => collect($signal['lignes']),
            ]);

        return (new FastExcel($feuilles))->download("achat-signaux-{$horodatage}.xlsx");
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    /** Les lignes d'une carte — TOUJOURS via le service partagé. */
    private function lignes(string $carte, Request $request): array
    {
        $avecRegularisations = $request->boolean('avec_regularisations');
        $du = $request->input('du');
        $au = $request->input('au');

        return match ($carte) {
            'etat-bons' => $this->statistiques->etatDesBons(
                $avecRegularisations,
                $du,
                $au,
                $request->filled('fournisseur_id') ? (int) $request->input('fournisseur_id') : null
            ),
            'depenses-fournisseur' => $this->statistiques->depensesParFournisseur($avecRegularisations, $du, $au),
            'depenses-categorie' => $this->statistiques->depensesParCategorie($avecRegularisations, $du, $au),
            'evolution' => $this->statistiques->evolutionDouzeMois($avecRegularisations),
            'reliquats' => $this->lignesReliquats($request),
            'regularisation' => $this->lignesRegularisation(),
            default => [],
        };
    }

    /** L'état A-06, imprimable — mêmes chiffres que l'écran (une source). */
    private function lignesReliquats(Request $request): array
    {
        $service = app(\Modules\Achat\Services\ReliquatsService::class);
        $seuil = $this->parametres->delaiAlerteReliquatJours();

        return $service->requete($request)
            ->orderBy('achat_bons_commande.valide_le')
            ->get()
            ->map(fn ($ligne) => collect($service->presenter($ligne, $seuil))
                ->only(['numero', 'fournisseur', 'article', 'quantite', 'quantite_livree', 'reste', 'montant_reste_ht', 'age_jours'])
                ->all())
            ->all();
    }

    /** Bons de régularisation + le compteur de dette qui reste à documenter. */
    private function lignesRegularisation(): array
    {
        $bons = \Modules\Achat\Models\BonCommande::query()
            ->regularisations()
            ->with(['fournisseur:id,raison_sociale', 'createur:id,name'])
            ->withCount('rattachements')
            ->orderByDesc('date_document')
            ->get();

        return $bons->map(fn ($bon) => [
            'numero' => $bon->numero_affiche,
            'statut' => $bon->statut_label,
            'date' => $bon->date_document?->format('d/m/Y'),
            'fournisseur' => $bon->fournisseur_libelle ?? $bon->fournisseur?->raison_sociale ?? '—',
            'montant_ttc' => round((float) $bon->montant_ttc, 2),
            'equipements_rattaches' => (int) $bon->rattachements_count,
            'saisi_par' => $bon->createur?->name ?? '—',
        ])->all();
    }

    /**
     * Le titre PORTE la qualification HT/TTC : sans elle, un tableau de
     * montants se lit comme du TTC, et l'écart fait 18 %.
     */
    private function titreQualifie(string $carte): string
    {
        return match ($carte) {
            'etat-bons' => 'État des bons de commande (montants HT et TTC)',
            'depenses-fournisseur' => 'Dépenses par fournisseur (montants HT et TTC)',
            'depenses-categorie' => 'Dépenses par catégorie (montants HT)',
            'evolution' => 'Évolution sur 12 mois (montants HT et TTC)',
            'reliquats' => 'Reliquats — reste à livrer (montants HT)',
            'regularisation' => 'Régularisation de l\'intérim (montants TTC)',
            default => self::CARTES[$carte] ?? 'Rapport',
        };
    }

    /** @return array<string, string> */
    private function libellesFiltres(Request $request): array
    {
        $filtres = [];

        if ($request->filled('du')) {
            $filtres['Du'] = \Illuminate\Support\Carbon::parse($request->input('du'))->format('d/m/Y');
        }

        if ($request->filled('au')) {
            $filtres['Au'] = \Illuminate\Support\Carbon::parse($request->input('au'))->format('d/m/Y');
        }

        if ($request->filled('fournisseur_id')) {
            $filtres['Fournisseur'] = Fournisseur::query()
                ->whereKey((int) $request->input('fournisseur_id'))
                ->value('raison_sociale') ?? '—';
        }

        // Toujours affiché : le lecteur doit savoir si les régularisations
        // sont dedans, même — surtout — quand elles ne le sont pas.
        $filtres['Régularisations'] = $request->boolean('avec_regularisations') ? 'incluses' : 'exclues';

        return $filtres;
    }

    private function exportCsv(array $lignes, string $titre, array $filtres, string $fichier)
    {
        return response()->streamDownload(function () use ($lignes, $titre, $filtres) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\u{FEFF}"); // BOM UTF-8 pour Excel

            fputcsv($sortie, [$titre.' — exporté le '.now()->format('d/m/Y H:i')], ';');

            foreach ($filtres as $libelle => $valeur) {
                fputcsv($sortie, [$libelle.' : '.$valeur], ';');
            }

            fputcsv($sortie, [], ';');

            if ($lignes !== []) {
                fputcsv($sortie, array_keys($lignes[0]), ';');

                foreach ($lignes as $ligne) {
                    fputcsv($sortie, array_values($ligne), ';');
                }
            }

            fclose($sortie);
        }, $fichier.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
