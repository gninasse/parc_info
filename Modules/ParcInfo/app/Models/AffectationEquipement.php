<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\PosteTravail;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Unite;

class AffectationEquipement extends Model
{
    protected $table = 'parc_info_affectation_equipements';

    protected $fillable = [
        'code',
        'date_debut',
        'date_fin',
        'equipement_id',
        'statut',
        'type_affectation',
        'type_cible',
        'dossier_employe_id',
        'poste_travail_id',
        'local_id',
        'niveau_rattachement',
        'direction_id',
        'service_id',
        'unite_id',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'statut'     => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function equipement()
    {
        return $this->belongsTo(Equipement::class, 'equipement_id');
    }

    public function employe()
    {
        return $this->belongsTo(Employe::class, 'dossier_employe_id');
    }

    public function posteTravail()
    {
        return $this->belongsTo(PosteTravail::class, 'poste_travail_id');
    }

    public function local()
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    public function direction()
    {
        return $this->belongsTo(Direction::class, 'direction_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function unite()
    {
        return $this->belongsTo(Unite::class, 'unite_id');
    }
}
