<?php

namespace Modules\Catalogue\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Catalogue\Http\Requests\StoreFournisseurRequest;
use Modules\Catalogue\Http\Requests\UpdateFournisseurRequest;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\Activity;

class FournisseurController extends Controller implements HasMiddleware
{
    /**
     * Tables des modules Stock/Achat (absents à ce jour) susceptibles de
     * référencer un fournisseur : consultées via Schema::hasTable pour que
     * la garde reste fonctionnelle avec ou sans ces modules.
     */
    private const TABLES_LIEES = [
        'stock_receptions' => 'réception(s) de stock',
        'achat_commandes' => "commande(s) d'achat",
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:catalogue.fournisseurs.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:catalogue.fournisseurs.store', only: ['store']),
            new Middleware('permission:catalogue.fournisseurs.update', only: ['update']),
            new Middleware('permission:catalogue.fournisseurs.destroy', only: ['destroy']),
            new Middleware('permission:catalogue.fournisseurs.toggle-status', only: ['toggleStatus']),
        ];
    }

    public function index()
    {
        $kpis = [
            'actifs' => Fournisseur::actif()->count(),
            'avec_articles' => Fournisseur::has('articles')->count(),
            'ajoutes_annee' => Fournisseur::where('created_at', '>=', now()->startOfYear())->count(),
        ];

        return view('catalogue::fournisseurs.index', compact('kpis'));
    }

    public function getData(Request $request): JsonResponse
    {
        $query = Fournisseur::query()->withCount('articles as nb_articles');

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(raison_sociale) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(contact) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('statut')) {
            $query->where('est_actif', $request->input('statut') === 'actif');
        }

        $sort = $request->input('sort', 'id');
        $allowed = ['id', 'code', 'raison_sociale', 'contact', 'telephone', 'email', 'nb_articles', 'est_actif', 'created_at'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'id';
        }
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $order);

        $total = $query->count();
        $rows = $query
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(fn (Fournisseur $f) => [
                'id' => $f->id,
                'code' => $f->code,
                'raison_sociale' => $f->raison_sociale,
                'contact' => $f->contact,
                'telephone' => $f->telephone,
                'email' => $f->email,
                'nb_articles' => $f->nb_articles,
                'est_actif' => $f->est_actif,
                'created_at' => $f->created_at?->format('d/m/Y'),
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    /**
     * JSON de pré-remplissage de la modale si la requête attend du JSON,
     * sinon la fiche de consultation (pattern de la fiche utilisateur Core).
     */
    public function show(Request $request, $id)
    {
        $fournisseur = Fournisseur::withCount('articles as nb_articles')->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $fournisseur,
            ]);
        }

        $motifsBlocage = $this->motifsBlocageSuppression($fournisseur);

        $activites = Activity::where('subject_type', Fournisseur::class)
            ->where('subject_id', $fournisseur->id)
            ->latest()
            ->limit(10)
            ->get();

        return view('catalogue::fournisseurs.show', compact('fournisseur', 'motifsBlocage', 'activites'));
    }

    public function store(StoreFournisseurRequest $request): JsonResponse
    {
        try {
            $fournisseur = Fournisseur::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Le fournisseur « {$fournisseur->raison_sociale} » a été créé avec succès.",
                'data' => ['id' => $fournisseur->id, 'code' => $fournisseur->code],
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la création du fournisseur', ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function update(UpdateFournisseurRequest $request, $id): JsonResponse
    {
        $fournisseur = Fournisseur::findOrFail($id);

        try {
            $fournisseur->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Le fournisseur « {$fournisseur->raison_sociale} » a été modifié avec succès.",
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la modification du fournisseur', ['exception' => $e, 'fournisseur_id' => $fournisseur->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $fournisseur = Fournisseur::findOrFail($id);

        $motifs = $this->motifsBlocageSuppression($fournisseur);

        if ($motifs !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Suppression impossible : '.implode(' ; ', $motifs).'.',
            ], 422);
        }

        try {
            $fournisseur->delete();

            return response()->json([
                'success' => true,
                'message' => "Le fournisseur « {$fournisseur->raison_sociale} » a été supprimé.",
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la suppression du fournisseur', ['exception' => $e, 'fournisseur_id' => $fournisseur->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function toggleStatus($id): JsonResponse
    {
        $fournisseur = Fournisseur::findOrFail($id);

        try {
            $fournisseur->update(['est_actif' => ! $fournisseur->est_actif]);

            $etat = $fournisseur->est_actif ? 'activé' : 'désactivé';

            return response()->json([
                'success' => true,
                'message' => "Le fournisseur « {$fournisseur->raison_sociale} » a été {$etat}.",
                'data' => ['est_actif' => $fournisseur->est_actif],
            ]);
        } catch (Exception $e) {
            Log::error('Erreur au changement de statut du fournisseur', ['exception' => $e, 'fournisseur_id' => $fournisseur->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    /**
     * Motifs bloquant la suppression : articles au catalogue, et données des
     * modules Stock/Achat si leurs tables existent.
     */
    private function motifsBlocageSuppression(Fournisseur $fournisseur): array
    {
        $motifs = [];

        $nbArticles = $fournisseur->articles()->count();
        if ($nbArticles > 0) {
            $motifs[] = "{$nbArticles} article(s) du catalogue le référencent comme fournisseur principal";
        }

        foreach (self::TABLES_LIEES as $table => $libelle) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $nb = DB::table($table)->where('fournisseur_id', $fournisseur->id)->count();
            if ($nb > 0) {
                $motifs[] = "{$nb} {$libelle}";
            }
        }

        return $motifs;
    }
}
