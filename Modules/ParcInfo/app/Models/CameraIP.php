<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class CameraIP extends Model
{
    protected $table = 'parc_info_cameras_ip';
    protected $primaryKey = 'equipement_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'equipement_id',
        'adresse_ip',
        'adresse_mac',
        'resolution',
        'type_camera',
        'emplacement',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class, 'equipement_id');
    }
}
