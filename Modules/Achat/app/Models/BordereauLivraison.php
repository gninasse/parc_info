<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Achat\Traits\HasAuditFields;

class BordereauLivraison extends Model
{
    use HasAuditFields, HasFactory, SoftDeletes;

    protected $table = 'achat_bordereaux_livraison';

    protected $fillable = [
        'numero_livraison',
        'bon_de_commande_id',
        'date_livraison',
        'ref_bordereau_physique',
        'statut',
        'commentaire',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'date_livraison' => 'date',
        ];
    }

    /**
     * Get the referenced purchase order.
     */
    public function bonCommande(): BelongsTo
    {
        return $this->belongsTo(BonCommande::class, 'bon_de_commande_id');
    }

    /**
     * Get the delivery lines.
     */
    public function lignesLivraison(): HasMany
    {
        return $this->hasMany(LigneLivraison::class, 'bordereau_livraison_id');
    }

    /**
     * Get associated wizard data.
     */
    public function wizardData(): HasMany
    {
        return $this->hasMany(WizardData::class, 'bordereau_livraison_id');
    }

    /**
     * Check if the delivery slip can be modified.
     */
    public function estModifiable(): bool
    {
        return $this->statut === 'brouillon';
    }

    /**
     * Check if the wizard can be launched or continued.
     */
    public function peutLancerWizard(): bool
    {
        return in_array($this->statut, ['brouillon', 'wizard']);
    }

    /**
     * Get all of the delivery slip's documents.
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
