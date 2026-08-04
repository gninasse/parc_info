<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Exceptions\TransitionInterditeException;
use Modules\Stock\Exceptions\ValidationSortieException;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\LigneTransfert;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\Transfert;

/**
 * Validation d'un transfert (D17 — I4/I9/I15/I17) : re-contrôles puis UNE
 * transaction pour la paire atomique MouvementService::transfert()
 * (TRANSFERT_SORTIE + TRANSFERT_ENTREE liées au même document — jamais l'une
 * sans l'autre) et le changement de rattachement des unités pointées.
 * Ni bénéficiaire ni affectation : c'est structurellement la sortie sans D8.
 */
class ValiderTransfertService
{
    public function __construct(
        private MouvementService $mouvements,
        private NumerotationService $numerotation,
        private PointageService $pointage,
    ) {}

    public function valider(Transfert $transfert, ?string $jeton = null, ?int $userId = null): array
    {
        if ($transfert->estValide()) {
            if ($jeton !== null && $transfert->jeton_validation === $jeton) {
                return array_merge($this->recapDocument($transfert), ['deja_valide' => true]);
            }

            throw TransitionInterditeException::pour($transfert->numero_affiche, $transfert->statut, Transfert::STATUT_VALIDE);
        }

        if (! $transfert->canValidate()) {
            throw TransitionInterditeException::pour($transfert->numero_affiche, $transfert->statut, Transfert::STATUT_VALIDE);
        }

        $lignes = $transfert->lignes()->with(['article', 'tampons.equipement'])->orderBy('id')->get();

        if ($lignes->isEmpty()) {
            throw ValidationSortieException::sansLigne();
        }

        return DB::transaction(function () use ($transfert, $lignes, $jeton, $userId) {
            $this->verifierPreconditions($transfert, $lignes);

            $unites = 0;

            foreach ($lignes as $ligne) {
                if ($ligne->article->nature === Article::NATURE_EQUIPEMENT) {
                    $unites += $this->transfererUnites($transfert, $ligne, $userId);
                } else {
                    // Paire atomique quantitativement : source décrémentée,
                    // cible incrémentée, ou RIEN (I4 — même transaction)
                    $this->mouvements->transfert([
                        'transfert_id' => $transfert->id,
                        'magasin_source_id' => $transfert->magasin_source_id,
                        'magasin_cible_id' => $transfert->magasin_cible_id,
                        'article_id' => $ligne->article_id,
                        'quantite' => (float) $ligne->quantite,
                        'created_by' => $userId,
                    ]);
                }
            }

            $numero = $this->numerotation->attribuer($transfert);

            $this->pointage->tamponsDuDocument($transfert)->delete();

            $transfert->valider($userId);
            $transfert->forceFill(['jeton_validation' => $jeton])->save();

            activity('stock')
                ->performedOn($transfert)
                ->withProperties(['numero' => $numero, 'unites_transferees' => $unites])
                ->log('validation_transfert');

            return $this->recapDocument($transfert->refresh());
        });
    }

    /** Récapitulatif chiffré (SW-VALIDER-TRF). */
    public function recapDocument(Transfert $transfert): array
    {
        $lignes = $transfert->lignes()->with('article:id,nature')->get();

        $quantitatives = $lignes->filter(fn (LigneTransfert $l) => $l->article?->nature !== Article::NATURE_EQUIPEMENT);
        $modeles = $lignes->filter(fn (LigneTransfert $l) => $l->article?->nature === Article::NATURE_EQUIPEMENT);

        return [
            'numero' => $transfert->numero,
            'magasin_source' => $transfert->magasinSource?->libelle,
            'magasin_cible' => $transfert->magasinCible?->libelle,
            'articles' => $quantitatives->count(),
            'unites_articles' => (float) $quantitatives->sum('quantite'),
            'unites_equipements' => (int) $modeles->sum('quantite'),
            'transporte_par' => $transfert->transporte_par_nom,
        ];
    }

    private function verifierPreconditions(Transfert $transfert, Collection $lignes): void
    {
        // I15 — disponible SOURCE re-contrôlé sous verrou, ligne par ligne
        $erreurs = [];

        foreach ($lignes as $index => $ligne) {
            if ($ligne->article->nature === Article::NATURE_EQUIPEMENT) {
                continue;
            }

            $disponible = (float) (Niveau::query()
                ->where('magasin_id', $transfert->magasin_source_id)
                ->where('article_id', $ligne->article_id)
                ->lockForUpdate()
                ->value('quantite') ?? 0);

            if ($disponible < (float) $ligne->quantite) {
                $erreurs[$index] = 'ligne '.($index + 1)." « {$ligne->article->nom} » : demandé "
                    .rtrim(rtrim(number_format((float) $ligne->quantite, 2, ',', ' '), '0'), ',')
                    .', disponible '.rtrim(rtrim(number_format($disponible, 2, ',', ' '), '0'), ',');
            }
        }

        if ($erreurs !== []) {
            throw ValidationSortieException::disponibleInsuffisant($erreurs);
        }

        // I17 — pointage complet + unités toujours de la source
        if ($lignes->contains(fn (LigneTransfert $l) => $l->article?->nature === Article::NATURE_EQUIPEMENT)) {
            $this->pointage->verifierPointageComplet($transfert);
        }
    }

    /** Paire de mouvements unitaires + changement de magasin de l'unité (§7.7). */
    private function transfererUnites(Transfert $transfert, LigneTransfert $ligne, ?int $userId): int
    {
        $transferees = 0;

        foreach ($ligne->tampons as $tampon) {
            $this->mouvements->transfert([
                'transfert_id' => $transfert->id,
                'magasin_source_id' => $transfert->magasin_source_id,
                'magasin_cible_id' => $transfert->magasin_cible_id,
                'equipement_id' => $tampon->equipement_id,
                'quantite' => 1,
                'created_by' => $userId,
            ]);

            EquipementMagasin::query()
                ->where('equipement_id', $tampon->equipement_id)
                ->update([
                    'magasin_id' => $transfert->magasin_cible_id,
                    'date_rattachement' => now(),
                ]);

            $transferees++;
        }

        return $transferees;
    }
}
