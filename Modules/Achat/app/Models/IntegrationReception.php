<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Achat\Models\Concerns\JournaliseActiviteAchat;
use Modules\Core\Models\User;

/**
 * Trace d'une intégration de réception (API_Inter_Modules §5.1).
 *
 * C'est le support de l'idempotence : un bon d'entrée déjà intégré est
 * reconnu et n'incrémente rien une seconde fois. C'est aussi la piste d'audit
 * du raccordement — qui a intégré quoi, quand, et avec quelles quantités.
 */
class IntegrationReception extends Model
{
    use HasFactory, JournaliseActiviteAchat;

    public const SENS_RECEPTION = 'RECEPTION';

    public const SENS_CONTRE_PASSATION = 'CONTRE_PASSATION';

    protected $table = 'achat_integrations_receptions';

    protected $fillable = [
        'bon_commande_id',
        'sens',
        'entree_id',
        'mouvement_id',
        'reference',
        'detail',
        'created_by',
    ];

    protected $casts = [
        'detail' => 'array',
    ];

    public function bonCommande(): BelongsTo
    {
        return $this->belongsTo(BonCommande::class, 'bon_commande_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeReceptions(Builder $query): Builder
    {
        return $query->where('sens', self::SENS_RECEPTION);
    }

    public function scopeContrePassations(Builder $query): Builder
    {
        return $query->where('sens', self::SENS_CONTRE_PASSATION);
    }

    protected static function newFactory()
    {
        return \Modules\Achat\Database\Factories\IntegrationReceptionFactory::new();
    }
}
