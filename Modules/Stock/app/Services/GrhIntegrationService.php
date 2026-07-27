<?php

namespace Modules\Stock\Services;

use Modules\Grh\Models\Employe;
use Modules\Stock\Contracts\GrhIntegrationInterface;

/**
 * Seul point de contact du module Stock avec les modèles GRH.
 */
class GrhIntegrationService implements GrhIntegrationInterface
{
    public function employesActifs(): array
    {
        return Employe::where('est_actif', true)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get(['id', 'matricule', 'nom', 'prenom'])
            ->map(fn (Employe $employe) => [
                'id' => $employe->id,
                'matricule' => $employe->matricule,
                'nom_complet' => trim("{$employe->nom} {$employe->prenom}"),
            ])
            ->all();
    }

    public function employeExiste(int $employeId): bool
    {
        return Employe::where('id', $employeId)->exists();
    }

    public function libelleEmploye(int $employeId): ?string
    {
        $employe = Employe::find($employeId, ['matricule', 'nom', 'prenom']);

        return $employe ? trim("{$employe->matricule} — {$employe->nom} {$employe->prenom}") : null;
    }

    public function utilisateursDesEmployes(array $employeIds): array
    {
        if ($employeIds === []) {
            return [];
        }

        return Employe::whereIn('id', $employeIds)
            ->with('users:id,dossier_employe_id')
            ->get()
            ->flatMap(fn (Employe $employe) => $employe->users->pluck('id'))
            ->unique()
            ->values()
            ->all();
    }
}
