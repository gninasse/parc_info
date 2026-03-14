<?php

namespace Modules\Organisation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\PosteTravail;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Unite;

class PosteTravailController extends Controller
{
    public function index()
    {
        $directions = Direction::actif()->get();
        $services = Service::actif()->get();
        $unites = Unite::actif()->get();
        $locaux = Local::actif()->get();
        $users = User::all();

        return view('organisation::organisation.postes.index', compact('directions', 'services', 'unites', 'locaux', 'users'));
    }

    public function getData(Request $request)
    {
        $query = PosteTravail::query()->with(['direction', 'service', 'unite', 'local', 'agent']);

        if ($request->has('service_id') && ! empty($request->service_id)) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->has('statut') && ! empty($request->statut)) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('libelle', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
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
            'rows' => $postes,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'libelle' => 'required|max:255',
            'description' => 'nullable',
            'direction_id' => 'required|exists:organisation_directions,id',
            'service_id' => 'required|exists:organisation_services,id',
            'unite_id' => 'nullable|exists:organisation_unites,id',
            'local_id' => 'nullable|exists:organisation_locaux,id',
            'agent_id' => 'nullable|exists:users,id',
            'statut' => 'required|in:actif,inactif,en_renovation,supprime',
        ]);

        try {
            $data = $request->all();
            $data['code'] = PosteTravail::generateCode($request->service_id);

            $poste = PosteTravail::create($data);

            return response()->json(['success' => true, 'message' => 'Poste de travail créé avec succès', 'data' => $poste]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $poste = PosteTravail::with(['direction', 'service', 'unite', 'local', 'agent'])->findOrFail($id);

            return response()->json(['success' => true, 'data' => $poste]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Poste de travail non trouvé'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'libelle' => 'required|max:255',
            'description' => 'nullable',
            'direction_id' => 'required|exists:organisation_directions,id',
            'service_id' => 'required|exists:organisation_services,id',
            'unite_id' => 'nullable|exists:organisation_unites,id',
            'local_id' => 'nullable|exists:organisation_locaux,id',
            'agent_id' => 'nullable|exists:users,id',
            'statut' => 'required|in:actif,inactif,en_renovation,supprime',
        ]);

        try {
            $poste = PosteTravail::findOrFail($id);
            $poste->update($request->all());

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
