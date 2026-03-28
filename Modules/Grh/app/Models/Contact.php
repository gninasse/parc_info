<?php

namespace Modules\Grh\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $table = 'grh_contacts_employes';

    protected $fillable = [
        'employe_id',
        'type_contact',
        'valeur',
        'est_whatsapp',
    ];

    protected $casts = [
        'est_whatsapp' => 'boolean',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }
}
