<?php

namespace Modules\Stock\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Stock\Exceptions\TransitionInterditeException;

/**
 * Socle des documents à triptyque (entrée, sortie, transfert) — S3/D12/D16.
 *
 * La classe hôte définit :
 *  - const STATUT_BROUILLON / STATUT_PHASE (RÉFÉRENCEMENT ou POINTAGE) /
 *    STATUT_VALIDE / STATUT_ANNULE ;
 *  - const STATUT_LABELS : libellés orientés geste (amendement UX n°2) ;
 *  - ses transitions nommées (passerEnReferencement()…) via transitionner().
 */
trait EstDocumentStock
{
    public function scopeValides(Builder $query): Builder
    {
        return $query->where('statut', static::STATUT_VALIDE);
    }

    public function scopeNonValides(Builder $query): Builder
    {
        return $query->where('statut', '<>', static::STATUT_VALIDE);
    }

    public function getNumeroAfficheAttribute(): string
    {
        return $this->numero ?? 'Brouillon #'.$this->id;
    }

    public function getStatutLabelAttribute(): string
    {
        return static::STATUT_LABELS[$this->statut] ?? $this->statut;
    }

    public function estValide(): bool
    {
        return $this->statut === static::STATUT_VALIDE;
    }

    /** L'en-tête et les lignes sont librement modifiables (D12). */
    public function canEdit(): bool
    {
        return $this->statut === static::STATUT_BROUILLON;
    }

    /** Supprimable tant que non validé et non annulé (cascade lignes + tampon). */
    public function canDelete(): bool
    {
        return in_array($this->statut, [static::STATUT_BROUILLON, static::STATUT_PHASE], true);
    }

    public function canValidate(): bool
    {
        return in_array($this->statut, [static::STATUT_BROUILLON, static::STATUT_PHASE], true);
    }

    /** I16 — quantités verrouillées pendant la phase de références. */
    public function verrouille(): bool
    {
        return $this->statut === static::STATUT_PHASE;
    }

    public function retourBrouillon(): void
    {
        $this->transitionner([static::STATUT_PHASE], static::STATUT_BROUILLON);
    }

    public function valider(?int $validePar = null): void
    {
        $this->verifierTransition([static::STATUT_BROUILLON, static::STATUT_PHASE], static::STATUT_VALIDE);

        $this->forceFill([
            'statut' => static::STATUT_VALIDE,
            'valide_par' => $validePar,
            'valide_le' => now(),
        ])->save();
    }

    public function annuler(): void
    {
        $this->transitionner([static::STATUT_BROUILLON, static::STATUT_PHASE], static::STATUT_ANNULE);
    }

    protected function transitionner(array $depuis, string $vers): void
    {
        $this->verifierTransition($depuis, $vers);

        $this->forceFill(['statut' => $vers])->save();
    }

    protected function verifierTransition(array $depuis, string $vers): void
    {
        if (! in_array($this->statut, $depuis, true)) {
            throw TransitionInterditeException::pour(
                $this->numero_affiche,
                $this->statut,
                $vers
            );
        }
    }
}
