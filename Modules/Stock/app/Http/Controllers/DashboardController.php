<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Models\Transfert;

/**
 * Tableau de bord du module (UX §1) : 6 KPI, pastilles « en attente de
 * validation » par magasin, alertes de seuil actionnables, actions rapides,
 * répartition par magasin, derniers mouvements.
 */
class DashboardController extends Controller implements HasMiddleware
{
    /** Nombre de lignes des deux tables du tableau de bord (UX §1). */
    private const MAX_ALERTES = 6;

    private const MAX_MOUVEMENTS = 10;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:stock.dashboard.view', only: ['index']),
        ];
    }

    public function index()
    {
        return view('stock::dashboard.index', [
            'kpis' => $this->kpis(),
            'enAttente' => $this->enAttenteParMagasin(),
            'nonValidesAnciens' => $this->nonValidesAnciens(),
            'joursAlerte' => (int) config('stock.jours_alerte_non_valides', 30),
            'alertes' => $this->alertes(),
            'repartition' => $this->repartitionParMagasin(),
            'derniersMouvements' => $this->derniersMouvements(),
        ]);
    }

    // ── Zone 1 : les 6 KPI ─────────────────────────────────────────────────

    private function kpis(): array
    {
        $agregats = $this->requeteNiveaux()
            ->selectRaw('COUNT(*) AS references_en_stock')
            ->selectRaw('COALESCE(SUM('.Niveau::sqlValeur().'), 0) AS valeur_estimee')
            ->where('stock_niveaux.quantite', '>', 0)
            ->first();

        return [
            'magasins_actifs' => Magasin::query()->actifs()->count(),
            'references_en_stock' => (int) ($agregats->references_en_stock ?? 0),
            'sous_seuil' => $this->compterParStatut(Niveau::STATUT_SOUS_SEUIL),
            'ruptures' => $this->compterParStatut(Niveau::STATUT_RUPTURE),
            'equipements_en_stock' => EquipementMagasin::query()->count(),
            'valeur_estimee' => (float) ($agregats->valeur_estimee ?? 0),
        ];
    }

    private function compterParStatut(string $statut): int
    {
        return $this->requeteNiveaux()
            ->whereRaw(Niveau::sqlStatutAlerte().' = ?', [$statut])
            ->count();
    }

    /** Base commune : niveaux joints au catalogue (nécessaire au calcul du seuil). */
    private function requeteNiveaux()
    {
        return DB::table('stock_niveaux')
            ->join('catalogue_articles', 'catalogue_articles.id', '=', 'stock_niveaux.article_id');
    }

    // ── Zone 1 bis : pastilles « en attente de validation » ────────────────

    /**
     * Par magasin : ce qui est enregistré mais pas encore dans les niveaux
     * (bons non validés). Les entrées sont détaillées articles/équipements,
     * les sorties et transferts comptés en nombre de bons.
     */
    private function enAttenteParMagasin(): Collection
    {
        $entrees = DB::table('stock_lignes_entrees')
            ->join('stock_entrees', 'stock_entrees.id', '=', 'stock_lignes_entrees.entree_id')
            ->leftJoin('catalogue_articles', 'catalogue_articles.id', '=', 'stock_lignes_entrees.article_id')
            ->whereIn('stock_entrees.statut', [Entree::STATUT_BROUILLON, Entree::STATUT_REFERENCEMENT])
            ->groupBy('stock_entrees.magasin_id')
            ->selectRaw('stock_entrees.magasin_id AS magasin_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN catalogue_articles.nature = 'equipement' OR stock_lignes_entrees.equipement_id IS NOT NULL THEN stock_lignes_entrees.quantite ELSE 0 END), 0) AS equipements")
            ->selectRaw("COALESCE(SUM(CASE WHEN catalogue_articles.nature <> 'equipement' THEN stock_lignes_entrees.quantite ELSE 0 END), 0) AS articles")
            ->get()
            ->keyBy('magasin_id');

        $sorties = Sortie::query()->nonValides()
            ->where('statut', '<>', Sortie::STATUT_ANNULE)
            ->groupBy('magasin_id')
            ->selectRaw('magasin_id, COUNT(*) AS total')
            ->pluck('total', 'magasin_id');

        $transferts = Transfert::query()->nonValides()
            ->where('statut', '<>', Transfert::STATUT_ANNULE)
            ->groupBy('magasin_source_id')
            ->selectRaw('magasin_source_id, COUNT(*) AS total')
            ->pluck('total', 'magasin_source_id');

        return Magasin::query()->actifs()->orderBy('libelle')->get(['id', 'code', 'libelle'])
            ->map(fn (Magasin $magasin) => [
                'magasin' => $magasin,
                'equipements' => (float) ($entrees[$magasin->id]->equipements ?? 0),
                'articles' => (float) ($entrees[$magasin->id]->articles ?? 0),
                'sorties' => (int) ($sorties[$magasin->id] ?? 0),
                'transferts' => (int) ($transferts[$magasin->id] ?? 0),
            ])
            ->filter(fn (array $ligne) => $ligne['equipements'] > 0 || $ligne['articles'] > 0
                || $ligne['sorties'] > 0 || $ligne['transferts'] > 0)
            ->values();
    }

    /** Bons non validés depuis plus de N jours (config) — pastille superviseur. */
    private function nonValidesAnciens(): int
    {
        $limite = now()->subDays((int) config('stock.jours_alerte_non_valides', 30));

        return Entree::query()->nonValides()->where('statut', '<>', Entree::STATUT_ANNULE)->where('created_at', '<', $limite)->count()
            + Sortie::query()->nonValides()->where('statut', '<>', Sortie::STATUT_ANNULE)->where('created_at', '<', $limite)->count()
            + Transfert::query()->nonValides()->where('statut', '<>', Transfert::STATUT_ANNULE)->where('created_at', '<', $limite)->count();
    }

    // ── Zone 2 : alertes de seuil ──────────────────────────────────────────

    /** Ruptures d'abord, puis sous seuil ; 6 lignes maximum (UX §1). */
    private function alertes(): Collection
    {
        return Niveau::query()
            ->join('catalogue_articles', 'catalogue_articles.id', '=', 'stock_niveaux.article_id')
            ->join('stock_magasins', 'stock_magasins.id', '=', 'stock_niveaux.magasin_id')
            ->with(['article:id,code,nom,nature,unite_stock,seuil_defaut', 'magasin:id,code,libelle'])
            ->select('stock_niveaux.*')
            ->whereRaw(Niveau::sqlStatutAlerte().' <> ?', [Niveau::STATUT_OK])
            ->where('stock_magasins.est_actif', true)
            ->orderByRaw("CASE WHEN stock_niveaux.quantite <= 0 THEN 0 ELSE 1 END")
            ->orderBy('stock_niveaux.quantite')
            ->limit(self::MAX_ALERTES)
            ->get();
    }

    // ── Zone 2 bis : répartition par magasin ───────────────────────────────

    private function repartitionParMagasin(): Collection
    {
        $agregats = $this->requeteNiveaux()
            ->where('stock_niveaux.quantite', '>', 0)
            ->groupBy('stock_niveaux.magasin_id')
            ->selectRaw('stock_niveaux.magasin_id AS magasin_id, COUNT(*) AS nb_references')
            ->selectRaw('COALESCE(SUM('.Niveau::sqlValeur().'), 0) AS valeur')
            ->get()
            ->keyBy('magasin_id');

        $equipements = EquipementMagasin::query()
            ->groupBy('magasin_id')
            ->selectRaw('magasin_id, COUNT(*) AS total')
            ->pluck('total', 'magasin_id');

        $lignes = Magasin::query()->actifs()->orderBy('libelle')->get(['id', 'code', 'libelle'])
            ->map(fn (Magasin $magasin) => [
                'magasin' => $magasin,
                'nb_references' => (int) ($agregats[$magasin->id]->nb_references ?? 0),
                'valeur' => (float) ($agregats[$magasin->id]->valeur ?? 0),
                'equipements' => (int) ($equipements[$magasin->id] ?? 0),
            ]);

        // Part relative pour les barres de progression
        $valeurMax = (float) $lignes->max('valeur') ?: 1.0;
        $referencesMax = (int) $lignes->max('nb_references') ?: 1;

        return $lignes->map(fn (array $ligne) => array_merge($ligne, [
            'part_valeur' => (int) round($ligne['valeur'] * 100 / $valeurMax),
            'part_references' => (int) round($ligne['nb_references'] * 100 / $referencesMax),
        ]));
    }

    // ── Zone 3 : derniers mouvements ───────────────────────────────────────

    private function derniersMouvements(): Collection
    {
        return Mouvement::query()
            ->with([
                'magasin:id,code,libelle',
                'article:id,code,nom',
                'equipement:id,code_inventaire,modele',
                'createur:id,name',
                'entree:id,numero', 'sortie:id,numero,beneficiaire_libelle', 'transfert:id,numero', 'inventaire:id,numero',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::MAX_MOUVEMENTS)
            ->get()
            ->map(fn (Mouvement $mouvement) => [
                'date' => $mouvement->created_at,
                'type' => $mouvement->type,
                'libelle_document' => $this->libelleDocument($mouvement),
                'url_document' => $this->urlDocument($mouvement),
                'article' => $mouvement->article?->nom ?? $mouvement->equipement?->modele,
                'code_article' => $mouvement->article?->code ?? $mouvement->equipement?->code_inventaire,
                'magasin' => $mouvement->magasin?->libelle,
                'quantite_signee' => $mouvement->quantite_signee,
                'beneficiaire' => $mouvement->sortie?->beneficiaire_libelle,
                'par' => $mouvement->createur?->name,
            ]);
    }

    private function libelleDocument(Mouvement $mouvement): string
    {
        return match (true) {
            $mouvement->entree_id !== null => $mouvement->entree->numero ?? 'Entrée',
            $mouvement->sortie_id !== null => $mouvement->sortie->numero ?? 'Sortie',
            $mouvement->transfert_id !== null => $mouvement->transfert->numero ?? 'Transfert',
            $mouvement->inventaire_id !== null => $mouvement->inventaire->numero ?? 'Inventaire',
            default => 'Contre-mouvement',
        };
    }

    private function urlDocument(Mouvement $mouvement): ?string
    {
        return match (true) {
            $mouvement->entree_id !== null => route('stock.entrees.show', $mouvement->entree_id),
            $mouvement->sortie_id !== null => route('stock.sorties.show', $mouvement->sortie_id),
            $mouvement->transfert_id !== null => route('stock.transferts.show', $mouvement->transfert_id),
            default => null,
        };
    }
}
