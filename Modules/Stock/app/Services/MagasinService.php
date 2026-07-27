<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Stock\Contracts\GrhIntegrationInterface;
use Modules\Stock\Exceptions\RegleMetierException;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\ResponsableMagasin;

/**
 * Gestion des magasins (F1) — cycle de vie et responsables.
 */
class MagasinService
{
    public function __construct(protected GrhIntegrationInterface $grh) {}

    public function creer(array $donnees): Magasin
    {
        return DB::transaction(function () use ($donnees) {
            $magasin = Magasin::create([
                'code' => mb_strtoupper(trim($donnees['code'])),
                'libelle' => trim($donnees['libelle']),
                'description' => $donnees['description'] ?? null,
                'statut' => $donnees['statut'] ?? 'actif',
            ]);

            activity()->performedOn($magasin)->log("Magasin {$magasin->code} créé");

            return $magasin;
        });
    }

    public function modifier(Magasin $magasin, array $donnees): Magasin
    {
        // RG-F1-01 — le code est immuable : il n'est jamais repris des données.
        $magasin->update([
            'libelle' => trim($donnees['libelle']),
            'description' => $donnees['description'] ?? $magasin->description,
        ]);

        activity()->performedOn($magasin)->log("Magasin {$magasin->code} modifié");

        return $magasin;
    }

    public function activer(Magasin $magasin): Magasin
    {
        $magasin->update(['statut' => 'actif']);
        activity()->performedOn($magasin)->log("Magasin {$magasin->code} activé");

        return $magasin;
    }

    public function desactiver(Magasin $magasin): Magasin
    {
        // RG-F1-03 — l'inactivité bloque les mouvements (contrôlée par les
        // services de mouvement) ; la désactivation elle-même est toujours possible.
        $magasin->update(['statut' => 'inactif']);
        activity()->performedOn($magasin)->log("Magasin {$magasin->code} désactivé");

        return $magasin;
    }

    public function supprimer(Magasin $magasin): void
    {
        if (! $magasin->estSupprimable()) {
            throw new RegleMetierException(
                'Ce magasin porte des mouvements ou du stock : désactivez-le au lieu de le supprimer.'
            );
        }

        $magasin->delete();
        activity()->performedOn($magasin)->log("Magasin {$magasin->code} supprimé");
    }

    /** EF-F1-05/06 — Affecte un responsable issu de GRH. */
    public function ajouterResponsable(Magasin $magasin, array $donnees): ResponsableMagasin
    {
        if (! $this->grh->employeExiste((int) $donnees['employe_id'])) {
            throw new RegleMetierException("L'employé sélectionné est introuvable dans GRH.");
        }

        // RG-F1-06 — un seul responsable principal en cours par magasin.
        if (($donnees['role'] ?? 'adjoint') === 'principal') {
            $principalActuel = $magasin->responsables()
                ->where('role', 'principal')
                ->where(fn ($q) => $q->whereNull('date_fin')->orWhere('date_fin', '>', now()))
                ->exists();

            if ($principalActuel) {
                throw new RegleMetierException('Ce magasin a déjà un responsable principal en cours.');
            }
        }

        $responsable = $magasin->responsables()->create([
            'employe_id' => $donnees['employe_id'],
            'role' => $donnees['role'] ?? 'adjoint',
            'date_debut' => $donnees['date_debut'] ?? now()->toDateString(),
            'date_fin' => $donnees['date_fin'] ?? null,
        ]);

        activity()->performedOn($magasin)->log("Responsable ajouté au magasin {$magasin->code}");

        return $responsable;
    }

    public function retirerResponsable(Magasin $magasin, ResponsableMagasin $responsable): void
    {
        if ($responsable->magasin_id !== $magasin->id) {
            throw new RegleMetierException('Ce responsable n\'appartient pas à ce magasin.');
        }

        $responsable->delete();
        activity()->performedOn($magasin)->log("Responsable retiré du magasin {$magasin->code}");
    }
}
