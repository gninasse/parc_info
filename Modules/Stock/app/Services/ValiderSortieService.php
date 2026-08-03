<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Exceptions\TransitionInterditeException;
use Modules\Stock\Exceptions\ValidationSortieException;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\LigneSortie;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\Sortie;

/**
 * Validation d'un bon de sortie (S3/D8/D14 — I9/I15/I17) : re-contrôles sous
 * verrou (disponible ligne à ligne, pointage complet, « Remis à » si
 * équipements) puis une transaction : mouvements SORTIE, décréments,
 * détachements, affectations ParcInfo (cible = bénéficiaire), statuts
 * « en service », dénormalisations, numéro, purge tampon → VALIDE.
 */
class ValiderSortieService
{
    public function __construct(
        private MouvementService $mouvements,
        private NumerotationService $numerotation,
        private PointageService $pointage,
        private AffectationService $affectations,
    ) {}

    public function valider(Sortie $sortie, ?string $jeton = null, ?int $userId = null): array
    {
        if ($sortie->estValide()) {
            if ($jeton !== null && $sortie->jeton_validation === $jeton) {
                return array_merge($this->recapDocument($sortie), ['deja_valide' => true]);
            }

            throw TransitionInterditeException::pour($sortie->numero_affiche, $sortie->statut, Sortie::STATUT_VALIDE);
        }

        if (! $sortie->canValidate()) {
            throw TransitionInterditeException::pour($sortie->numero_affiche, $sortie->statut, Sortie::STATUT_VALIDE);
        }

        $lignes = $sortie->lignes()->with(['article', 'tampons.equipement'])->orderBy('id')->get();

        if ($lignes->isEmpty()) {
            throw ValidationSortieException::sansLigne();
        }

        return DB::transaction(function () use ($sortie, $lignes, $jeton, $userId) {
            $this->verifierPreconditions($sortie, $lignes);

            $unites = 0;

            foreach ($lignes as $ligne) {
                if ($ligne->article->nature === Article::NATURE_EQUIPEMENT) {
                    $unites += $this->sortirUnites($sortie, $ligne, $userId);
                } else {
                    $this->mouvements->sortie([
                        'sortie_id' => $sortie->id,
                        'magasin_id' => $sortie->magasin_id,
                        'article_id' => $ligne->article_id,
                        'quantite' => (float) $ligne->quantite,
                        'created_by' => $userId,
                    ]);
                }
            }

            $this->denormaliserBeneficiaire($sortie);

            $numero = $this->numerotation->attribuer($sortie);

            $this->pointage->tamponsDuDocument($sortie)->delete();

            $sortie->valider($userId);
            $sortie->forceFill(['jeton_validation' => $jeton])->save();

            activity('stock')
                ->performedOn($sortie)
                ->withProperties(['numero' => $numero, 'unites_sorties' => $unites])
                ->log('validation_sortie');

            return $this->recapDocument($sortie->refresh());
        });
    }

    /** Récapitulatif chiffré (SW-VALIDER-SOR) + avertissement de seuil non bloquant. */
    public function recapDocument(Sortie $sortie): array
    {
        $lignes = $sortie->lignes()->with('article:id,nature,prix_indicatif,seuil_defaut,nom')->get();

        $quantitatives = $lignes->filter(fn (LigneSortie $l) => $l->article?->nature !== Article::NATURE_EQUIPEMENT);
        $modeles = $lignes->filter(fn (LigneSortie $l) => $l->article?->nature === Article::NATURE_EQUIPEMENT);

        return [
            'numero' => $sortie->numero,
            'magasin' => $sortie->magasin?->libelle,
            'beneficiaire' => $sortie->beneficiaire_libelle ?? $sortie->beneficiaire_type,
            'remis_a' => $sortie->remis_a_nom,
            'articles' => $quantitatives->count(),
            'unites_articles' => (float) $quantitatives->sum('quantite'),
            'unites_equipements' => (int) $modeles->sum('quantite'),
            'alertes_seuil' => $this->alertesSeuil($sortie, $quantitatives),
        ];
    }

    // ── Préconditions (I15/I17) ────────────────────────────────────────────

    private function verifierPreconditions(Sortie $sortie, Collection $lignes): void
    {
        // « Remis à » requis si des équipements sortent (D8)
        $aDesEquipements = $lignes->contains(fn (LigneSortie $l) => $l->article?->nature === Article::NATURE_EQUIPEMENT);

        if ($aDesEquipements && blank($sortie->remis_a_nom)) {
            throw ValidationSortieException::remisAManquant();
        }

        // I15 — disponible re-contrôlé sous verrou, 422 LIGNE PAR LIGNE
        $erreurs = [];

        foreach ($lignes as $index => $ligne) {
            if ($ligne->article->nature === Article::NATURE_EQUIPEMENT) {
                continue;
            }

            $disponible = (float) (Niveau::query()
                ->where('magasin_id', $sortie->magasin_id)
                ->where('article_id', $ligne->article_id)
                ->lockForUpdate()
                ->value('quantite') ?? 0);

            if ($disponible < (float) $ligne->quantite) {
                $erreurs[$index] = "ligne ".($index + 1)." « {$ligne->article->nom} » : demandé "
                    .rtrim(rtrim(number_format((float) $ligne->quantite, 2, ',', ' '), '0'), ',')
                    .', disponible '.rtrim(rtrim(number_format($disponible, 2, ',', ' '), '0'), ',');
            }
        }

        if ($erreurs !== []) {
            throw ValidationSortieException::disponibleInsuffisant($erreurs);
        }

        // I17 — pointage complet + unités toujours pointables
        if ($lignes->contains(fn (LigneSortie $l) => $l->article?->nature === Article::NATURE_EQUIPEMENT)) {
            $this->pointage->verifierPointageComplet($sortie);
        }
    }

    /** Mouvement unitaire + détachement + affectation D8 + « en service ». */
    private function sortirUnites(Sortie $sortie, LigneSortie $ligne, ?int $userId): int
    {
        $sorties = 0;

        foreach ($ligne->tampons as $tampon) {
            $equipement = $tampon->equipement;

            $affectation = $this->affectations->affecter(
                $equipement,
                $sortie->beneficiaire_type,
                $this->beneficiaireId($sortie),
                $ligne->emplacement_local_id
            );

            $this->mouvements->sortie([
                'sortie_id' => $sortie->id,
                'magasin_id' => $sortie->magasin_id,
                'equipement_id' => $equipement->id,
                'quantite' => 1,
                'affectation_equipement_id' => $affectation->id,
                'created_by' => $userId,
            ]);

            // Détachement : l'unité quitte le magasin (l'historique est au journal)
            EquipementMagasin::query()->where('equipement_id', $equipement->id)->delete();

            $sorties++;
        }

        return $sorties;
    }

    private function beneficiaireId(Sortie $sortie): ?int
    {
        return match ($sortie->beneficiaire_type) {
            'direction' => $sortie->beneficiaire_direction_id,
            'service' => $sortie->beneficiaire_service_id,
            'unite' => $sortie->beneficiaire_unite_id,
            'poste' => $sortie->beneficiaire_poste_id,
            'local' => $sortie->beneficiaire_local_id,
            'employe' => $sortie->beneficiaire_employe_id,
            default => null,
        };
    }

    private function denormaliserBeneficiaire(Sortie $sortie): void
    {
        $libelle = match ($sortie->beneficiaire_type) {
            'direction' => $sortie->beneficiaireDirection?->libelle,
            'service' => $sortie->beneficiaireService?->libelle,
            'unite' => $sortie->beneficiaireUnite?->libelle,
            'poste' => $sortie->beneficiairePoste?->libelle,
            'local' => $sortie->beneficiaireLocal?->libelle,
            'employe' => $sortie->beneficiaireEmploye ? trim($sortie->beneficiaireEmploye->prenom.' '.$sortie->beneficiaireEmploye->nom) : null,
            default => null,
        };

        if ($libelle !== null) {
            $sortie->forceFill(['beneficiaire_libelle' => $libelle])->save();
        }
    }

    /** Franchissement de seuil → avertissement non bloquant (§7.4). */
    private function alertesSeuil(Sortie $sortie, Collection $quantitatives): array
    {
        $alertes = [];

        foreach ($quantitatives as $ligne) {
            $niveau = Niveau::query()
                ->where('magasin_id', $sortie->magasin_id)
                ->where('article_id', $ligne->article_id)
                ->with('article:id,nom,seuil_defaut')
                ->first();

            if ($niveau !== null && in_array($niveau->statut_alerte, [Niveau::STATUT_SOUS_SEUIL, Niveau::STATUT_RUPTURE], true)) {
                $alertes[] = [
                    'article' => $niveau->article->nom,
                    'statut' => $niveau->statut_alerte,
                    'quantite' => (float) $niveau->quantite,
                    'seuil' => $niveau->seuil_effectif,
                ];
            }
        }

        return $alertes;
    }
}
