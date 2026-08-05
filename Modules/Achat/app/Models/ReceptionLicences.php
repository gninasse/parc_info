<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Achat\Models\Concerns\JournaliseActiviteAchat;
use Modules\Core\Models\User;

/**
 * Session du wizard de réception des licences (A-05, SFD §7.4).
 * FINALISEE = les licences existent dans ParcInfo ; ABANDONNEE = retour en
 * arrière tracé. La session survit à la ligne (FK restrict) : c'est une trace.
 */
class ReceptionLicences extends Model
{
    use HasFactory, JournaliseActiviteAchat;

    public const STATUT_EN_COURS = 'EN_COURS';

    public const STATUT_FINALISEE = 'FINALISEE';

    public const STATUT_ABANDONNEE = 'ABANDONNEE';

    public const STATUT_LABELS = [
        self::STATUT_EN_COURS => 'Saisie en cours',
        self::STATUT_FINALISEE => 'Finalisée',
        self::STATUT_ABANDONNEE => 'Abandonnée',
    ];

    protected $table = 'achat_receptions_licences';

    protected $attributes = [
        'statut' => self::STATUT_EN_COURS,
    ];

    protected $fillable = [
        'ligne_commande_id',
        'quantite',
        'created_by',
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
        'finalisee_le' => 'datetime',
    ];

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(LigneCommande::class, 'ligne_commande_id');
    }

    public function tampon(): HasMany
    {
        return $this->hasMany(TamponLicence::class, 'reception_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeEnCours(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_EN_COURS);
    }

    public function estEnCours(): bool
    {
        return $this->statut === self::STATUT_EN_COURS;
    }

    /** Clés restant à saisir avant de pouvoir finaliser (diagnostic SW-03). */
    public function getManquantesAttribute(): int
    {
        return max(0, (int) $this->quantite - $this->tampon()->count());
    }

    protected static function newFactory()
    {
        return \Modules\Achat\Database\Factories\ReceptionLicencesFactory::new();
    }
}
