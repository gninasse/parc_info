<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneCommande extends Model
{
    protected $table = 'achat_lignes_commande';

    protected $fillable = [
        'bon_de_commande_id',
        'article_id',
        'quantite',
        'prix_unitaire',
        'taux_tva',
        'quantite_livree',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'prix_unitaire' => 'decimal:2',
            'taux_tva' => 'decimal:2',
            'quantite_livree' => 'integer',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────

    public function bonCommande(): BelongsTo
    {
        return $this->belongsTo(BonCommande::class, 'bon_de_commande_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    // ── Accesseurs calculés ────────────────────────────────────────────────

    /** RG-BL-03 : plafond de saisie d'une réception. */
    public function getResteALivrerAttribute(): int
    {
        return max(0, $this->quantite - $this->quantite_livree);
    }

    /**
     * ENF-FIA-04 — Source unique de vérité du montant d'une ligne.
     * Tout écran et tout export doivent passer par ces accesseurs.
     */
    public function getMontantHtAttribute(): float
    {
        return round((float) $this->quantite * (float) $this->prix_unitaire, 2);
    }

    public function getMontantTvaAttribute(): float
    {
        return round($this->montant_ht * ((float) $this->taux_tva / 100), 2);
    }

    public function getMontantTtcAttribute(): float
    {
        return round($this->montant_ht + $this->montant_tva, 2);
    }

    public function getEstEntierementLivreeAttribute(): bool
    {
        return $this->quantite_livree >= $this->quantite;
    }

    /** État de livraison de la ligne : attente | partiel | livre */
    public function getEtatLivraisonAttribute(): string
    {
        if ($this->quantite_livree <= 0) {
            return 'attente';
        }

        return $this->est_entierement_livree ? 'livre' : 'partiel';
    }
}
