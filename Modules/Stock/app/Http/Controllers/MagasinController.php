<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Grh\Models\Employe;
use Modules\Stock\Http\Requests\StoreMagasinRequest;
use Modules\Stock\Http\Requests\UpdateMagasinRequest;
use Modules\Stock\Models\DroitMagasin;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\ResponsableMagasin;
use Modules\Stock\Services\MagasinService;
use Spatie\Permission\Models\Role;

class MagasinController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected MagasinService $service) {}

    public function index(): \Illuminate\View\View
    {
        $this->authorize('stock.magasins.view');

        $employes = Employe::where('est_actif', true)->orderBy('nom')->orderBy('prenom')->get();
        $roles = Role::orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('stock::magasins.index', compact('employes', 'roles', 'users'));
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.magasins.view');

        $query = Magasin::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('nom', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('est_actif')) {
            $query->where('est_actif', $request->input('est_actif') === 'true');
        }

        $sortField = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');
        $allowed = ['code', 'nom', 'type', 'est_actif', 'created_at'];
        if (! in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }
        $query->orderBy($sortField, $sortOrder);

        $total = $query->count();
        $rows = $query
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(fn (Magasin $m) => [
                'id' => $m->id,
                'code' => $m->code,
                'nom' => $m->nom,
                'type' => $m->type,
                'type_label' => $m->type,
                'est_actif' => $m->est_actif,
                'created_at' => $m->created_at?->toDateTimeString(),
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function show(Magasin $magasin): JsonResponse
    {
        $this->authorize('stock.magasins.view');

        $magasin->load(['responsables.employe', 'droits']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $magasin->id,
                'code' => $magasin->code,
                'nom' => $magasin->nom,
                'type' => $magasin->type,
                'description' => $magasin->description,
                'est_actif' => $magasin->est_actif,
                'responsables' => $magasin->responsables->map(fn ($r) => [
                    'id' => $r->id,
                    'employe_nom' => $r->employe ? $r->employe->nom.' '.$r->employe->prenom : '-',
                    'role' => $r->role,
                    'date_debut' => $r->date_debut?->toDateString(),
                    'date_fin' => $r->date_fin?->toDateString(),
                ]),
                'droits' => $magasin->droits->map(fn ($d) => [
                    'id' => $d->id,
                    'type_sujet' => $d->type_sujet,
                    'sujet_id' => $d->sujet_id,
                    'sujet_nom' => $d->type_sujet === 'ROLE'
                        ? (Role::find($d->sujet_id)?->name ?? '-')
                        : (User::find($d->sujet_id)?->name ?? '-'),
                    'peut_lire' => $d->peut_lire,
                    'peut_entrer_stock' => $d->peut_entrer_stock,
                    'peut_sortir_stock' => $d->peut_sortir_stock,
                    'peut_transferer' => $d->peut_transferer,
                    'peut_inventorier' => $d->peut_inventorier,
                    'peut_administrer' => $d->peut_administrer,
                ]),
            ],
        ]);
    }

    public function store(StoreMagasinRequest $request): JsonResponse
    {
        try {
            $magasin = $this->service->creerMagasin($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Le magasin « {$magasin->nom} » a été créé avec succès.",
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateMagasinRequest $request, Magasin $magasin): JsonResponse
    {
        try {
            $this->service->modifierMagasin($magasin, $request->validated());

            return response()->json([
                'success' => true,
                'message' => "Le magasin « {$magasin->nom} » a été modifié avec succès.",
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Magasin $magasin): JsonResponse
    {
        $this->authorize('stock.magasins.edit');

        try {
            $this->service->supprimerMagasin($magasin);

            return response()->json(['success' => true, 'message' => 'Magasin supprimé avec succès.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function storeResponsable(Request $request, Magasin $magasin): JsonResponse
    {
        $this->authorize('stock.magasins.admin');

        $data = $request->validate([
            'employe_id' => 'required|exists:grh_dossiers_employes,id',
            'role' => 'required|in:principal,adjoint',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
        ]);

        try {
            $this->service->ajouterResponsable($magasin, $data);

            return response()->json(['success' => true, 'message' => 'Responsable ajouté avec succès.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroyResponsable(Magasin $magasin, ResponsableMagasin $responsable): JsonResponse
    {
        $this->authorize('stock.magasins.admin');

        try {
            $this->service->supprimerResponsable($responsable);

            return response()->json(['success' => true, 'message' => 'Responsable supprimé avec succès.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function storeDroit(Request $request, Magasin $magasin): JsonResponse
    {
        $this->authorize('stock.magasins.admin');

        $data = $request->validate([
            'type_sujet' => 'required|in:USER,ROLE',
            'sujet_id' => 'required|integer',
            'peut_lire' => 'nullable|boolean',
            'peut_entrer_stock' => 'nullable|boolean',
            'peut_sortir_stock' => 'nullable|boolean',
            'peut_transferer' => 'nullable|boolean',
            'peut_inventorier' => 'nullable|boolean',
            'peut_administrer' => 'nullable|boolean',
        ]);

        try {
            $this->service->enregistrerDroits($magasin, $data);

            return response()->json(['success' => true, 'message' => 'Droits enregistrés avec succès.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroyDroit(Magasin $magasin, DroitMagasin $droit): JsonResponse
    {
        $this->authorize('stock.magasins.admin');

        try {
            $this->service->supprimerDroit($droit);

            return response()->json(['success' => true, 'message' => 'Droit supprimé avec succès.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
