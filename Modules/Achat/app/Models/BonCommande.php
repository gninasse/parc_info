<?php

namespace Modules\Achat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Achat\Traits\HasAuditFields;
use Modules\ParcInfo\Models\Fournisseur;

class BonCommande extends Model
{
    use HasAuditFields, HasFactory, SoftDeletes;

    protected $table = 'achat_bons_commande';

    protected $fillable = [
        'numero_commande',
        'fournisseur_id',
        'date_commande',
        'statut',
        'montant_total',
        'commentaire',
        'valide_par',
        'date_validation',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'date_commande' => 'date',
            'date_validation' => 'datetime',
            'montant_total' => 'decimal:2',
        ];
    }

    /**
     * Get the supplier.
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    /**
     * Get the user who validated this order.
     */
    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    /**
     * Get the lines of this order.
     */
    public function lignesCommande(): HasMany
    {
        return $this->hasMany(LigneCommande::class, 'bon_de_commande_id');
    }

    /**
     * Get the associated delivery slips.
     */
    public function bordereauxLivraison(): HasMany
    {
        return $this->hasMany(BordereauLivraison::class, 'bon_de_commande_id');
    }

    /**
     * Recalculate and update the total amount of the purchase order.
     */
    public function recalculerMontantTotal(): void
    {
        $total = $this->lignesCommande()->sum(\DB::raw('quantite * prix_unitaire'));
        $this->update(['montant_total' => $total]);
    }

    /**
     * Check if the purchase order can be modified (only in 'brouillon' status).
     */
    public function estModifiable(): bool
    {
        return $this->statut === 'brouillon';
    }

    /**
     * Check if the purchase order can be validated.
     */
    public function estValidable(): bool
    {
        return $this->statut === 'brouillon' && $this->lignesCommande()->exists();
    }

    /**
     * Check if the purchase order can be cancelled.
     */
    public function estAnnulable(): bool
    {
        return in_array($this->statut, ['brouillon', 'valide']);
    }
}
