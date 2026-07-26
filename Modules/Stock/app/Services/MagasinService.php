<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Stock\Models\DroitMagasin;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\ResponsableMagasin;

class MagasinService
{
    public function creerMagasin(array $data): Magasin
    {
        return DB::transaction(function () use ($data) {
            $magasin = Magasin::create($data);
            activity()->performedOn($magasin)->log('created');

            return $magasin;
        });
    }

    public function modifierMagasin(Magasin $magasin, array $data): Magasin
    {
        return DB::transaction(function () use ($magasin, $data) {
            $magasin->update($data);
            activity()->performedOn($magasin)->log('updated');

            return $magasin;
        });
    }

    public function supprimerMagasin(Magasin $magasin): void
    {
        DB::transaction(function () use ($magasin) {
            if (! $magasin->estSupprimable()) {
                throw new \Exception('Ce magasin ne peut pas être supprimé car il contient des données ou a des mouvements associés.');
            }
            $magasin->delete();
            activity()->performedOn($magasin)->log('deleted');
        });
    }

    public function ajouterResponsable(Magasin $magasin, array $data): ResponsableMagasin
    {
        return DB::transaction(function () use ($magasin, $data) {
            $role = $data['role'] ?? 'principal';
            $dateDebut = $data['date_debut'] ?? now()->toDateString();

            if ($role === 'principal') {
                // RG-F1-06: Un seul responsable principal actif à la fois
                // On clôture le responsable principal actif actuel
                ResponsableMagasin::where('magasin_id', $magasin->id)
                    ->where('role', 'principal')
                    ->whereNull('date_fin')
                    ->update(['date_fin' => $dateDebut]);
            }

            $responsable = ResponsableMagasin::create([
                'magasin_id' => $magasin->id,
                'employe_id' => $data['employe_id'],
                'role' => $role,
                'date_debut' => $dateDebut,
                'date_fin' => $data['date_fin'] ?? null,
            ]);

            activity()
                ->performedOn($magasin)
                ->withProperty('responsable', $responsable->id)
                ->log('responsable_added');

            return $responsable;
        });
    }

    public function supprimerResponsable(ResponsableMagasin $responsable): void
    {
        DB::transaction(function () use ($responsable) {
            $magasin = $responsable->magasin;
            $responsable->delete();

            activity()
                ->performedOn($magasin)
                ->withProperty('responsable_id', $responsable->id)
                ->log('responsable_deleted');
        });
    }

    public function enregistrerDroits(Magasin $magasin, array $data): DroitMagasin
    {
        return DB::transaction(function () use ($magasin, $data) {
            $droit = DroitMagasin::updateOrCreate(
                [
                    'magasin_id' => $magasin->id,
                    'type_sujet' => $data['type_sujet'],
                    'sujet_id' => $data['sujet_id'],
                ],
                [
                    'peut_lire' => (bool) ($data['peut_lire'] ?? false),
                    'peut_entrer_stock' => (bool) ($data['peut_entrer_stock'] ?? false),
                    'peut_sortir_stock' => (bool) ($data['peut_sortir_stock'] ?? false),
                    'peut_transferer' => (bool) ($data['peut_transferer'] ?? false),
                    'peut_inventorier' => (bool) ($data['peut_inventorier'] ?? false),
                    'peut_administrer' => (bool) ($data['peut_administrer'] ?? false),
                ]
            );

            activity()
                ->performedOn($magasin)
                ->withProperty('droit', $droit->id)
                ->log('rights_updated');

            return $droit;
        });
    }

    public function supprimerDroit(DroitMagasin $droit): void
    {
        DB::transaction(function () use ($droit) {
            $magasin = $droit->magasin;
            $droit->delete();

            activity()
                ->performedOn($magasin)
                ->withProperty('droit_id', $droit->id)
                ->log('rights_deleted');
        });
    }
}
