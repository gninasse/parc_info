<?php

namespace Modules\ParcInfo\Models;

use Modules\Core\Models\User;
use Modules\Grh\Models\Employe;
use Illuminate\Database\Eloquent\Model;

class MouvementConsommable extends Model
{
    protected $table = 'parc_info_mouvements_consommables';
    
    public $timestamps = true;
    
    protected $fillable = [
        'consommable_id',
        'type_mouvement',
        'quantite',
        'prix_unitaire',
        'date_mouvement',
        'reference_commande',
        'utilisateur_id',
        'equipement_id',
        'employe_id',
        'raison',
        'notes',
    ];
    
    protected $casts = [
        'date_mouvement' => 'datetime',
        'prix_unitaire' => 'decimal:2',
        'quantite' => 'integer',
    ];
    
    public function consommable()
    {
        return $this->belongsTo(Consommable::class);
    }
    
    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
    
    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }
    
    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }
    
    // SCOPES
    public function scopeConsommations($query)
    {
        return $query->where('type_mouvement', 'Consommation');
    }
    
    public function scopeAchats($query)
    {
        return $query->where('type_mouvement', 'Achat');
    }
}
