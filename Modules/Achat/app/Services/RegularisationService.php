<?php

namespace Modules\Achat\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Achat\Exceptions\RegularisationException;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\Parametre;
use Modules\Achat\Models\RegularisationRattachement;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\Equipement;

/**
 * D-15 — la régularisation de l'intérim (A15, M-09).
 *
 * Pendant la période d'intérim, du matériel est entré sans bon de commande.
 * Le module ne prétend pas réécrire l'histoire : il DOCUMENTE la dette —
 * des bons marqués « régularisation », auxquels on rattache les équipements
 * dont l'origine était inconnue.
 *
 * La porte est encadrée (A15) : elle s'ouvre par un paramètre, se borne à
 * des dates, et surtout S'ÉTEINT TOUTE SEULE quand la dette tombe à zéro.
 * Un dispositif exceptionnel qui reste ouvert devient une pratique
 * ordinaire — l'extinction automatique est ce qui l'empêche.
 */
class RegularisationService
{
    public const EVENEMENT_RATTACHEMENT = 'rattachement_regularisation';

    public const EVENEMENT_DETACHEMENT = 'detachement_regularisation';

    public const EVENEMENT_EXTINCTION = 'extinction_regularisation';

    public const EVENEMENT_REACTIVATION = 'reactivation_regularisation';

    public function __construct(private readonly AchatParametres $parametres) {}

    /**
     * Les équipements SANS commande d'origine — la dette qui reste à
     * documenter (M-09).
     *
     * « Sans origine » signifie : ni rattachement de régularisation, ni
     * chaîne mouvement → entrée → bon de commande. Un équipement déjà
     * traçable n'a pas à être régularisé.
     */
    public function equipementsCandidats(?string $recherche = null, int $limite = 50): \Illuminate\Support\Collection
    {
        $query = Equipement::query()
            ->whereNotIn('id', RegularisationRattachement::query()->select('equipement_id'))
            ->whereNotIn('id', $this->equipementsAvecOrigineStock());

        if ($recherche !== null && trim($recherche) !== '') {
            $terme = mb_strtolower(trim($recherche));

            $query->where(function ($sous) use ($terme) {
                $sous->whereRaw('LOWER(code_inventaire) LIKE ?', ["%{$terme}%"])
                    ->orWhereRaw('LOWER(numero_serie) LIKE ?', ["%{$terme}%"])
                    ->orWhereRaw('LOWER(modele) LIKE ?', ["%{$terme}%"]);
            });
        }

        return $query
            ->orderBy('code_inventaire')
            ->limit($limite)
            ->get(['id', 'code_inventaire', 'numero_serie', 'modele', 'date_acquisition', 'statut']);
    }

    /** Combien d'équipements restent sans origine : LA dette. */
    public function detteRestante(): int
    {
        return Equipement::query()
            ->whereNotIn('id', RegularisationRattachement::query()->select('equipement_id'))
            ->whereNotIn('id', $this->equipementsAvecOrigineStock())
            ->count();
    }

    /**
     * Équipements dont l'origine est déjà traçable par la chaîne Stock :
     * mouvement → bon d'entrée → bon de commande (PRQ-05).
     *
     * Lecture directe des tables Stock, sans charger ses modèles : le module
     * ne doit pas dépendre du chargement d'un autre pour compter sa dette.
     * Si Stock est absent, la sous-requête est vide — tous les équipements
     * sont candidats, ce qui est le comportement prudent.
     */
    private function equipementsAvecOrigineStock(): \Illuminate\Database\Query\Builder
    {
        if (! Schema::hasTable('stock_mouvements') || ! Schema::hasTable('stock_entrees')) {
            return DB::table('parc_info_equipements')->select('id')->whereRaw('1 = 0');
        }

        return DB::table('stock_mouvements')
            ->join('stock_entrees', 'stock_entrees.id', '=', 'stock_mouvements.entree_id')
            ->whereNotNull('stock_mouvements.equipement_id')
            ->whereNotNull('stock_entrees.bon_commande_id')
            ->select('stock_mouvements.equipement_id');
    }

    /**
     * M-09 — rattache des équipements à un bon de régularisation.
     *
     * IA-11 : le rattachement est UNIQUE (un équipement n'a qu'une commande
     * d'origine, contrainte en base) et le bon doit être une régularisation
     * VALIDÉE — on ne documente pas une acquisition avec un brouillon.
     *
     * @param  list<int>  $equipementIds
     * @return array{rattaches: int, dette_restante: int, eteinte: bool}
     *
     * @throws RegularisationException
     */
    public function rattacher(BonCommande $bon, array $equipementIds, User $auteur): array
    {
        if (! $bon->est_regularisation) {
            throw RegularisationException::pasUneRegularisation($bon->numero_affiche);
        }

        if (! $bon->estEngage()) {
            throw RegularisationException::bonNonValide($bon->numero_affiche, $bon->statut_label);
        }

        $equipementIds = array_values(array_unique(array_map('intval', $equipementIds)));

        if ($equipementIds === []) {
            throw RegularisationException::aucunEquipement();
        }

        return DB::transaction(function () use ($bon, $equipementIds, $auteur) {
            // Déjà rattachés (ici ou ailleurs) : refus explicite, pas un
            // silence — l'utilisateur doit savoir que sa liste était périmée.
            $dejaRattaches = RegularisationRattachement::query()
                ->whereIn('equipement_id', $equipementIds)
                ->with('bonCommande:id,numero')
                ->get();

            if ($dejaRattaches->isNotEmpty()) {
                $premier = Equipement::query()->find($dejaRattaches->first()->equipement_id);

                throw RegularisationException::dejaRattache(
                    $premier?->code_inventaire ?? '#'.$dejaRattaches->first()->equipement_id,
                    $dejaRattaches->first()->bonCommande?->numero ?? 'un autre bon'
                );
            }

            $equipements = Equipement::query()
                ->whereIn('id', $equipementIds)
                ->get(['id', 'code_inventaire']);

            if ($equipements->count() !== count($equipementIds)) {
                throw RegularisationException::equipementIntrouvable();
            }

            foreach ($equipements as $equipement) {
                RegularisationRattachement::create([
                    'bon_commande_id' => $bon->id,
                    'equipement_id' => $equipement->id,
                    'created_by' => $auteur->id,
                ]);
            }

            activity('achat')
                ->performedOn($bon)
                ->causedBy($auteur)
                ->withProperties([
                    'equipements' => $equipements->pluck('code_inventaire')->all(),
                    'nombre' => $equipements->count(),
                ])
                ->log(self::EVENEMENT_RATTACHEMENT);

            // L'extinction se vérifie DANS la transaction : la dette et le
            // paramètre ne peuvent pas diverger.
            $dette = $this->detteRestante();
            $eteinte = $this->eteindreSiDetteSoldee($dette, $auteur);

            return [
                'rattaches' => $equipements->count(),
                'dette_restante' => $dette,
                'eteinte' => $eteinte,
            ];
        });
    }

    /** Détache un équipement (erreur de saisie) — tracé, dette re-augmentée. */
    public function detacher(BonCommande $bon, int $equipementId, User $auteur): int
    {
        return DB::transaction(function () use ($bon, $equipementId, $auteur) {
            $rattachement = RegularisationRattachement::query()
                ->where('bon_commande_id', $bon->id)
                ->where('equipement_id', $equipementId)
                ->firstOrFail();

            $equipement = Equipement::query()->find($equipementId);
            $rattachement->delete();

            activity('achat')
                ->performedOn($bon)
                ->causedBy($auteur)
                ->withProperties(['equipement' => $equipement?->code_inventaire])
                ->log(self::EVENEMENT_DETACHEMENT);

            return $this->detteRestante();
        });
    }

    /**
     * EXTINCTION AUTOMATIQUE (A15) : la dette est soldée, la porte se ferme.
     *
     * C'est le cœur du dispositif. Sans cela, le mode « régularisation »
     * resterait ouvert indéfiniment et deviendrait une voie de contournement
     * du circuit normal : saisir après coup ce qu'on aurait dû engager avant.
     */
    public function eteindreSiDetteSoldee(?int $dette = null, ?User $auteur = null): bool
    {
        $dette ??= $this->detteRestante();

        if ($dette > 0 || ! $this->parametres->regularisationActive()) {
            return false;
        }

        $this->parametres->set(Parametre::REGULARISATION_ACTIVE, false, $auteur?->id);

        // Sans sujet, le module ne serait pas renseigné (il l'est par le trait
        // des modèles journalisés) : on le pose explicitement, sinon la trace
        // échapperait à toute lecture par module.
        activity('achat')
            ->causedBy($auteur)
            ->withProperties(['dette_restante' => 0])
            ->tap(fn ($activite) => $activite->module = 'achat')
            ->log(self::EVENEMENT_EXTINCTION);

        return true;
    }

    /**
     * SW-06 — réactivation par l'administrateur : un oubli est découvert
     * après l'extinction. Le geste est délibéré, motivé et tracé — jamais
     * automatique.
     */
    public function reactiver(User $auteur, string $motif): void
    {
        $this->parametres->set(Parametre::REGULARISATION_ACTIVE, true, $auteur->id);

        activity('achat')
            ->causedBy($auteur)
            ->withProperties([
                'motif' => $motif,
                'dette_restante' => $this->detteRestante(),
            ])
            ->tap(fn ($activite) => $activite->module = 'achat')
            ->log(self::EVENEMENT_REACTIVATION);
    }

    /**
     * Les bons de régularisation sont EXCLUS des statistiques par défaut :
     * ce sont des écritures de rattrapage, pas de l'activité d'achat. Les
     * mélanger fausserait toute lecture de tendance.
     */
    public function scopeStatistiques(Builder $query): Builder
    {
        return $query->horsRegularisation();
    }
}
