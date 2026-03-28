<?php

namespace Modules\Organisation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Http\Requests\PosteTravailRequest;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\PosteTravail;
use Modules\Organisation\Models\Site;

class PosteTravailController extends Controller
{
    public function index()
    {
        $directions = Direction::actif()->get();
        $sites = Site::actif()->get();

        return view('organisation::organisation.postes.index', compact('directions', 'sites'));
    }

    public function getData(Request $request)
    {
        $query = PosteTravail::query()->with(['direction', 'service', 'unite', 'local.etage.batiment.site', 'agent']);

        if ($request->filled('direction_id')) {
            $query->where('direction_id', $request->direction_id);
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->filled('unite_id')) {
            $query->where('unite_id', $request->unite_id);
        }

        if ($request->filled('niveau_rattachement')) {
            $query->where('niveau_rattachement', $request->niveau_rattachement);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('libelle', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('agent', function ($q) use ($search) {
                        $q->where('nom', 'like', "%{$search}%")
                            ->orWhere('prenom', 'like', "%{$search}%")
                            ->orWhere('matricule', 'like', "%{$search}%");
                    });
            });
        }

        $sortBy = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 0);

        $total = $query->count();
        $postes = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $postes->map(function ($poste) {
                // Emplacement formaté: Site > Batiment > Etage > Local
                $emplacement = 'N/A';
                if ($poste->local) {
                    $loc = $poste->local;
                    $etage = $loc->etage;
                    $bat = $etage?->batiment;
                    $site = $bat?->site;

                    $parts = [];
                    if ($site) {
                        $parts[] = $site->code;
                    }
                    if ($bat) {
                        $parts[] = $bat->libelle;
                    }
                    if ($etage) {
                        $parts[] = 'Etage '.$etage->numero;
                    }
                    $parts[] = $loc->libelle;

                    $emplacement = implode(' > ', $parts);
                }

                return [
                    'id' => $poste->id,
                    'code' => $poste->code,
                    'libelle' => $poste->libelle,
                    'niveau' => ucfirst($poste->niveau_rattachement),
                    'direction' => $poste->direction?->libelle,
                    'service' => $poste->service?->libelle,
                    'unite' => $poste->unite?->libelle,
                    'emplacement' => $emplacement,
                    'occupant' => $poste->agent ? $poste->agent->full_name : '<span class="badge bg-warning">Vacant</span>',
                    'statut' => $poste->statut,
                    'actif' => $poste->actif,
                ];
            }),
        ]);
    }

    public function store(PosteTravailRequest $request)
    {
        try {
            $data = $request->validated();

            // Génération du code basée sur le niveau de rattachement
            $parentId = match ($data['niveau_rattachement']) {
                'direction' => $data['direction_id'],
                'service' => $data['service_id'],
                'unite' => $data['unite_id'],
                default => $data['direction_id']
            };

            $data['code'] = PosteTravail::generateCode($data['niveau_rattachement'], $parentId);

            $poste = PosteTravail::create($data);

            return response()->json(['success' => true, 'message' => 'Poste de travail créé avec succès', 'data' => $poste]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function searchEmployes(Request $request)
    {
        $search = $request->get('q');
        $employes = Employe::where('est_actif', true)
            ->where(function ($query) use ($search) {
                $query->where('nom', 'like', "%$search%")
                    ->orWhere('prenom', 'like', "%$search%")
                    ->orWhere('matricule', 'like', "%$search%");
            })
            ->limit(20)
            ->get();

        return response()->json($employes->map(function ($emp) {
            return [
                'id' => $emp->id,
                'text' => $emp->full_name." ({$emp->matricule})",
            ];
        }));
    }

    public function show($id)
    {
        try {
            $poste = PosteTravail::with(['direction', 'service', 'unite', 'local.etage.batiment.site', 'agent'])->findOrFail($id);

            return response()->json(['success' => true, 'data' => $poste]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Poste de travail non trouvé'], 404);
        }
    }

    public function update(PosteTravailRequest $request, $id)
    {
        try {
            $poste = PosteTravail::findOrFail($id);
            $data = $request->validated();

            // Régénération du code basée sur le niveau de rattachement
            $parentId = match ($data['niveau_rattachement']) {
                'direction' => $data['direction_id'],
                'service' => $data['service_id'],
                'unite' => $data['unite_id'],
                default => $data['direction_id']
            };

            $data['code'] = PosteTravail::generateCode($data['niveau_rattachement'], $parentId);

            $poste->update($data);

            return response()->json(['success' => true, 'message' => 'Poste de travail modifié avec succès', 'data' => $poste]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $poste = PosteTravail::findOrFail($id);
            $poste->update(['actif' => false]);

            return response()->json(['success' => true, 'message' => 'Poste de travail désactivé avec succès']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $item = PosteTravail::findOrFail($id);
            $item->actif = ! $item->actif;
            $item->save();

            return response()->json([
                'success' => true,
                'message' => $item->actif ? 'Élément activé avec succès' : 'Élément désactivé avec succès',
                'actif' => $item->actif,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut',
            ], 500);
        }
    }
}
