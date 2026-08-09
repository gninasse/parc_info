<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Achat\Models\Concerns\JournaliseActiviteAchat;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;

/**
 * Ligne de bon de commande — porte la PHOTOGRAPHIE CONTRACTUELLE (SFD §6.2).
 *
 * designation, nature, prix_unitaire_ht et taux_tva sont copiés du Catalogue
 * à l'ajout puis figés (IA-2) : le document dit ce qui a été engagé, et une
 * évolution ultérieure du Catalogue ne réécrit jamais le passé.
 */
class LigneCommande extends Model
{
    use HasFactory, JournaliseActiviteAchat;

    protected $table = 'achat_lignes_commande';

    protected $fillable = [
        'bon_commande_id',
        'article_id',
        'designation',
        'nature',
        'prix_unitaire_ht',
        'taux_tva',
        'compte_comptable',
        'quantite',
    ];

    protected $casts = [
        'prix_unitaire_ht' => 'decimal:2',
        'taux_tva' => 'decimal:2',
        'quantite' => 'decimal:2',
        'quantite_livree' => 'decimal:2',
        'montant_ht' => 'decimal:2',
        'service_fait_le' => 'datetime',
    ];

    public function bonCommande(): BelongsTo
    {
        return $this->belongsTo(BonCommande::class, 'bon_commande_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function receptionsLicences(): HasMany
    {
        return $this->hasMany(ReceptionLicences::class, 'ligne_commande_id');
    }

    public function auteurServiceFait(): BelongsTo
    {
        return $this->belongsTo(User::class, 'service_fait_par');
    }

    // ── Reliquat (SFD §1.2 : Achat est la source de vérité du reste) ───────

    public function getResteAttribute(): float
    {
        return round((float) $this->quantite - (float) $this->quantite_livree, 2);
    }

    public function estSoldee(): bool
    {
        return $this->reste <= 0;
    }

    /** Part livrée en pourcentage — barre de progression par ligne (A-04). */
    public function getProgressionAttribute(): int
    {
        $quantite = (float) $this->quantite;

        return $quantite > 0
            ? (int) min(100, round((float) $this->quantite_livree * 100 / $quantite))
            : 0;
    }

    public function estPrestation(): bool
    {
        return $this->nature === 'prestation';
    }

    public function estLicence(): bool
    {
        return $this->nature === Article::NATURE_LICENCE;
    }

    protected static function newFactory()
    {
        return \Modules\Achat\Database\Factories\LigneCommandeFactory::new();
    }
}
