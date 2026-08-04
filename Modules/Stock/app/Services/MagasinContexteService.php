<?php

namespace Modules\Stock\Services;

use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\PreferenceUtilisateur;

/**
 * Magasin de travail d'un utilisateur, résolu en cascade :
 *   1. sa préférence explicite (stock_preferences) ;
 *   2. le magasin dont il est responsable (stock_magasins.responsable_id ↔
 *      users.dossier_employe_id) ;
 *   3. l'unique magasin actif s'il n'y en a qu'un (périmètre unique, UX §3.2).
 *
 * Utilisé pour pré-sélectionner le magasin des entrées et des sorties, et le
 * magasin SOURCE des transferts.
 */
class MagasinContexteService
{
    public function magasinParDefaut(?int $userId = null): ?Magasin
    {
        $userId ??= auth()->id();

        if ($userId === null) {
            return null;
        }

        $prefere = PreferenceUtilisateur::query()
            ->where('user_id', $userId)
            ->with('magasinDefaut')
            ->first()?->magasinDefaut;

        if ($prefere?->est_actif) {
            return $prefere;
        }

        $employeId = \Modules\Core\Models\User::query()->whereKey($userId)->value('dossier_employe_id');

        if ($employeId !== null) {
            $responsableDe = Magasin::query()->actifs()->where('responsable_id', $employeId)->first();

            if ($responsableDe !== null) {
                return $responsableDe;
            }
        }

        $actifs = Magasin::query()->actifs()->limit(2)->get();

        return $actifs->count() === 1 ? $actifs->first() : null;
    }

    public function magasinParDefautId(?int $userId = null): ?int
    {
        return $this->magasinParDefaut($userId)?->id;
    }

    /** Enregistre (ou efface avec null) la préférence explicite. */
    public function definirMagasinParDefaut(int $userId, ?int $magasinId): PreferenceUtilisateur
    {
        return PreferenceUtilisateur::updateOrCreate(
            ['user_id' => $userId],
            ['magasin_defaut_id' => $magasinId]
        );
    }
}
