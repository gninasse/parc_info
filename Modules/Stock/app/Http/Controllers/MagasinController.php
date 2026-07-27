<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Stock\Contracts\GrhIntegrationInterface;
use Modules\Stock\Http\Controllers\Concerns\RepondEnJson;
use Modules\Stock\Http\Requests\StoreMagasinRequest;
use Modules\Stock\Http\Requests\StoreResponsableMagasinRequest;
use Modules\Stock\Http\Requests\UpdateMagasinRequest;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\ResponsableMagasin;
use Modules\Stock\Services\MagasinService;

class MagasinController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    public function __construct(
        protected MagasinService $magasinService,
        protected GrhIntegrationInterface $grh,
    ) {}

    /** Liste des magasins (F1) — RG-F1-07 : les inactifs restent visibles. */
    public function index(): View
    {
        $this->authorize('stock.magasins.view');

        return view('stock::magasins.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.magasins.view');

        $query = Magasin::withCount(['stocksArticles as articles_count'])
            ->orderBy('code');

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('search')) {
            $recherche = $request->input('search');
            $query->where(function ($q) use ($recherche) {
                $q->where('code', 'like', "%{$recherche}%")
                    ->orWhere('libelle', 'like', "%{$recherche}%");
            });
        }

        $total = $query->count();

        $rows = $query->limit($request->integer('limit', 25))
            ->offset($request->integer('offset', 0))
            ->get()
            ->map(fn (Magasin $magasin) => [
                'id' => $magasin->id,
                'code' => $magasin->code,
                'libelle' => $magasin->libelle,
                'statut' => $magasin->statut,
                'articles_count' => $magasin->articles_count,
                'responsable_principal' => $magasin->responsablePrincipal()
                    ? $this->grh->libelleEmploye($magasin->responsablePrincipal()->employe_id)
                    : null,
                'created_at' => $magasin->created_at?->toDateTimeString(),
            ]);

        return $this->table($total, $rows);
    }

    /** Pré-remplissage du modal d'édition + fiche (PATTERNS §4). */
    public function show(Magasin $magasin): JsonResponse
    {
        $this->authorize('stock.magasins.view');

        $magasin->load('responsables');

        return $this->donnees([
            'id' => $magasin->id,
            'code' => $magasin->code,
            'libelle' => $magasin->libelle,
            'description' => $magasin->description,
            'statut' => $magasin->statut,
            'responsables' => $magasin->responsables->map(fn (ResponsableMagasin $responsable) => [
                'id' => $responsable->id,
                'employe_id' => $responsable->employe_id,
                'employe' => $this->grh->libelleEmploye($responsable->employe_id),
                'role' => $responsable->role,
                'date_debut' => $responsable->date_debut?->format('d/m/Y'),
                'date_fin' => $responsable->date_fin?->format('d/m/Y'),
                'en_cours' => $responsable->estEnCours(),
            ]),
        ]);
    }

    public function store(StoreMagasinRequest $request): JsonResponse
    {
        return $this->executer(function () use ($request) {
            $magasin = $this->magasinService->creer($request->validated());

            return $this->succes("Le magasin {$magasin->code} a été créé.");
        });
    }

    public function update(UpdateMagasinRequest $request, Magasin $magasin): JsonResponse
    {
        return $this->executer(function () use ($request, $magasin) {
            $this->magasinService->modifier($magasin, $request->validated());

            return $this->succes("Le magasin {$magasin->code} a été modifié.");
        });
    }

    public function destroy(Magasin $magasin): JsonResponse
    {
        $this->authorize('stock.magasins.admin');

        return $this->executer(function () use ($magasin) {
            $this->magasinService->supprimer($magasin);

            return $this->succes("Le magasin {$magasin->code} a été supprimé.");
        });
    }

    public function activer(Magasin $magasin): JsonResponse
    {
        $this->authorize('stock.magasins.admin');

        return $this->executer(function () use ($magasin) {
            $this->magasinService->activer($magasin);

            return $this->succes("Le magasin {$magasin->code} est actif.");
        });
    }

    public function desactiver(Magasin $magasin): JsonResponse
    {
        $this->authorize('stock.magasins.admin');

        return $this->executer(function () use ($magasin) {
            $this->magasinService->desactiver($magasin);

            return $this->succes("Le magasin {$magasin->code} est inactif : plus aucun mouvement n'est autorisé.");
        });
    }

    /** Employés GRH pour le select du modal responsables. */
    public function employes(): JsonResponse
    {
        $this->authorize('stock.magasins.admin');

        return $this->donnees($this->grh->employesActifs());
    }

    public function ajouterResponsable(StoreResponsableMagasinRequest $request, Magasin $magasin): JsonResponse
    {
        return $this->executer(function () use ($request, $magasin) {
            $this->magasinService->ajouterResponsable($magasin, $request->validated());

            return $this->succes('Le responsable a été ajouté.');
        });
    }

    public function retirerResponsable(Magasin $magasin, ResponsableMagasin $responsable): JsonResponse
    {
        $this->authorize('stock.magasins.admin');

        return $this->executer(function () use ($magasin, $responsable) {
            $this->magasinService->retirerResponsable($magasin, $responsable);

            return $this->succes('Le responsable a été retiré.');
        });
    }
}
