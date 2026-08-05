<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Achat\Models\Concerns\JournaliseActiviteAchat;

/**
 * Tampon du wizard de licences (SFD §6.2) : sauvegarde clé par clé pour
 * survivre à une coupure réseau (IA-8). Purgé à la finalisation — les données
 * vivent alors dans ParcInfo — et vidé à l'abandon : jamais une seconde
 * source de vérité.
 */
class TamponLicence extends Model
{
    use HasFactory, JournaliseActiviteAchat;

    protected $table = 'achat_tampon_licences';

    protected $fillable = [
        'reception_id',
        'cle',
        'date_activation',
        'date_expiration',
    ];

    protected $casts = [
        'date_activation' => 'date',
        'date_expiration' => 'date',
    ];

    public function reception(): BelongsTo
    {
        return $this->belongsTo(ReceptionLicences::class, 'reception_id');
    }

    protected static function newFactory()
    {
        return \Modules\Achat\Database\Factories\TamponLicenceFactory::new();
    }
}
