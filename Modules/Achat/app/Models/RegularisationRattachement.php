<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Achat\Models\Concerns\JournaliseActiviteAchat;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\Equipement;

/**
 * Rattachement d'un équipement à son bon de commande de régularisation
 * (A15/M-09) : documente l'origine des acquisitions de la période d'intérim.
 * Un équipement n'a qu'une commande d'origine (contrainte d'unicité).
 */
class RegularisationRattachement extends Model
{
    use HasFactory, JournaliseActiviteAchat;

    protected $table = 'achat_regularisation_rattachements';

    protected $fillable = [
        'bon_commande_id',
        'equipement_id',
        'created_by',
    ];

    public function bonCommande(): BelongsTo
    {
        return $this->belongsTo(BonCommande::class, 'bon_commande_id');
    }

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory()
    {
        return \Modules\Achat\Database\Factories\RegularisationRattachementFactory::new();
    }
}
