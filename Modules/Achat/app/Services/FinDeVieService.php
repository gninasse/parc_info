<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\BonCommande;
use Modules\Core\Models\User;

/**
 * Fin de vie du bon de commande : annulation (M-07) et clôture du reliquat
 * (M-03) — SFD §7.5.
 *
 * Deux gestes définitifs, donc deux transactions sous verrou : relire le bon
 * `lockForUpdate` avant de trancher ferme la course avec une réception qui
 * s'intégrerait au même instant (l'intégration verrouille le même bon).
 * Le motif est OBLIGATOIRE dans les deux cas : un document officiel ne
 * disparaît pas, et un reliquat ne s'abandonne pas, sans explication écrite.
 */
class FinDeVieService
{
    public const EVENEMENT_ANNULATION = 'annulation';

    public const EVENEMENT_CLOTURE = 'cloture_reliquat';

    /**
     * M-07 — annule un bon VALIDÉ sans aucune réception. Le modèle porte les
     * deux gardes (statut + réceptions) et lève TransitionInterditeException,
     * traduite en 409 par le contrôleur.
     */
    public function annuler(BonCommande $bon, User $acteur, string $motif): BonCommande
    {
        return DB::transaction(function () use ($bon, $acteur, $motif) {
            $bon = BonCommande::query()->lockForUpdate()->findOrFail($bon->id);

            $bon->annuler($motif);

            activity('achat')
                ->performedOn($bon)
                ->causedBy($acteur)
                ->withProperties([
                    'numero' => $bon->numero,
                    'motif' => $motif,
                    'montant_ttc' => (float) $bon->montant_ttc,
                ])
                ->log(self::EVENEMENT_ANNULATION);

            return $bon->refresh();
        });
    }

    /**
     * M-03 — clôt le reliquat d'un bon PARTIEL : l'établissement renonce au
     * reste à livrer. Les quantités livrées restent ce qu'elles sont — la
     * clôture n'invente ni ne retranche aucune réception.
     */
    public function cloturer(BonCommande $bon, User $acteur, string $motif): BonCommande
    {
        return DB::transaction(function () use ($bon, $acteur, $motif) {
            $bon = BonCommande::query()->lockForUpdate()->findOrFail($bon->id);

            // Le reliquat abandonné, photographié AVANT la clôture : c'est la
            // seule trace de ce à quoi on a renoncé (le reste se recalcule,
            // mais le journal doit dire ce qu'il valait à l'instant du geste).
            $reliquat = $bon->lignes()
                ->get()
                ->filter(fn ($ligne) => $ligne->reste > 0)
                ->map(fn ($ligne) => [
                    'designation' => $ligne->designation,
                    'reste' => $ligne->reste,
                ])
                ->values()
                ->all();

            $bon->cloturer($motif);

            activity('achat')
                ->performedOn($bon)
                ->causedBy($acteur)
                ->withProperties([
                    'numero' => $bon->numero,
                    'motif' => $motif,
                    'reliquat_abandonne' => $reliquat,
                ])
                ->log(self::EVENEMENT_CLOTURE);

            return $bon->refresh();
        });
    }
}
