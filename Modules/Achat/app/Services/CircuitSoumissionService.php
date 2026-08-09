<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Facades\DB;
use Modules\Achat\Exceptions\SoumissionRefuseeException;
use Modules\Achat\Exceptions\TransitionInterditeException;
use Modules\Achat\Models\BonCommande;
use Modules\Core\Models\User;

/**
 * Le circuit BROUILLON ⇄ SOUMIS (SFD §7.1).
 *
 *   BROUILLON ──soumettre──▶ SOUMIS ──renvoyer (validateur, motivé)──▶ BROUILLON
 *                              └────reprendre (auteur)───────────────▶ BROUILLON
 *
 * Toutes les écritures de statut passent par les transitions du modèle, et
 * toutes sont journalisées avec leur motif : le renvoi et la reprise doivent
 * rester lisibles des semaines plus tard, sinon personne ne comprend pourquoi
 * un bon est revenu en arrière.
 *
 * Le motif n'est pas décoratif : c'est ce que l'auteur lira dans l'encart
 * jaune à la réouverture de son brouillon (UX2-07).
 */
class CircuitSoumissionService
{
    /** Événements du journal — repris tels quels par la chronologie A-04. */
    public const EVENEMENT_SOUMISSION = 'soumission';

    public const EVENEMENT_RENVOI = 'renvoi_en_brouillon';

    public const EVENEMENT_REPRISE = 'reprise_par_auteur';

    public function __construct(
        private readonly ControlesSoumissionService $controles,
        private readonly CalculMontantsService $montants,
    ) {}

    /**
     * Soumission au visa. Les contrôles de complétude sont REJOUÉS ici : que
     * l'écran ait grisé son bouton ne prouve rien sur ce qui arrive au
     * serveur.
     *
     * @throws SoumissionRefuseeException si le bon n'est pas complet
     * @throws TransitionInterditeException si le bon n'est pas en brouillon
     */
    public function soumettre(BonCommande $bon, User $auteur): BonCommande
    {
        return DB::transaction(function () use ($bon, $auteur) {
            // Verrou de ligne : deux onglets qui soumettent en même temps ne
            // doivent pas produire deux journalisations pour une transition.
            $bon = BonCommande::query()->lockForUpdate()->findOrFail($bon->id);

            if (! $bon->estSoumettable()) {
                throw TransitionInterditeException::pour(
                    $bon->numero_affiche,
                    $bon->statut,
                    BonCommande::STATUT_SOUMIS
                );
            }

            $diagnostic = $this->controles->diagnostiquer($bon);

            if (! $diagnostic['soumettable']) {
                throw SoumissionRefuseeException::pour($diagnostic['blocages']);
            }

            // Dernier recalcul avant verrouillage : ce qui part au visa doit
            // porter des montants à jour, quoi qu'il se soit passé avant.
            $this->montants->recalculer($bon);

            $bon->soumettre($auteur->id);

            activity('achat')
                ->performedOn($bon)
                ->causedBy($auteur)
                ->withProperties([
                    'montant_ttc' => (float) $bon->montant_ttc,
                    'nb_lignes' => $bon->lignes()->count(),
                ])
                ->log(self::EVENEMENT_SOUMISSION);

            return $bon->refresh();
        });
    }

    /**
     * Renvoi motivé par le validateur (M-06). Le motif est OBLIGATOIRE :
     * renvoyer un bon sans dire pourquoi oblige l'auteur à deviner, et c'est
     * exactement ce que le circuit doit éviter.
     */
    public function renvoyer(BonCommande $bon, User $validateur, string $motif): BonCommande
    {
        return $this->retourEnBrouillon($bon, $validateur, $motif, self::EVENEMENT_RENVOI);
    }

    /**
     * Reprise par l'auteur : il défait sa propre soumission (SFD §7.1). Pas de
     * motif exigé — on ne se justifie pas auprès de soi-même — mais l'acte est
     * tracé comme les autres.
     */
    public function reprendre(BonCommande $bon, User $auteur): BonCommande
    {
        if ($bon->created_by !== $auteur->id) {
            throw SoumissionRefuseeException::repriseReserveeALAuteur();
        }

        return $this->retourEnBrouillon($bon, $auteur, null, self::EVENEMENT_REPRISE);
    }

    /**
     * Retour commun en brouillon. Le motif et son auteur sont écrits SUR le
     * bon (et pas seulement au journal) : l'encart jaune de l'étape ① doit
     * pouvoir les afficher sans relire l'historique.
     */
    private function retourEnBrouillon(
        BonCommande $bon,
        User $acteur,
        ?string $motif,
        string $evenement
    ): BonCommande {
        return DB::transaction(function () use ($bon, $acteur, $motif, $evenement) {
            $bon = BonCommande::query()->lockForUpdate()->findOrFail($bon->id);

            if ($bon->statut !== BonCommande::STATUT_SOUMIS) {
                throw TransitionInterditeException::pour(
                    $bon->numero_affiche,
                    $bon->statut,
                    BonCommande::STATUT_BROUILLON
                );
            }

            $bon->renvoyerEnBrouillon();

            $bon->forceFill([
                'renvoi_motif' => $motif,
                'renvoi_par' => $acteur->id,
                'renvoi_le' => now(),
            ])->save();

            activity('achat')
                ->performedOn($bon)
                // Comme au visa : l'acteur est posé explicitement, pour que
                // la trace survive à un appel hors requête HTTP.
                ->causedBy($acteur)
                ->withProperties(array_filter([
                    'motif' => $motif,
                    'auteur_bon' => $bon->created_by,
                ]))
                ->log($evenement);

            return $bon->refresh();
        });
    }
}
