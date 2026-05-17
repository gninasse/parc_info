<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class Logiciel extends Model
{
    protected $table = 'parc_info_logiciels';

    protected $fillable = [
        'code',
        'nom',
        'description',
        'type_licence_id',
        'editeur_id',
        'categorie',
        'est_actif',
        'notes',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    public function typeLicence()
    {
        return $this->belongsTo(TypeLicence::class);
    }

    public function editeur()
    {
        return $this->belongsTo(Editeur::class);
    }

    public function licences()
    {
        return $this->hasMany(Licence::class);
    }

    // Statistiques
    public function getUtilisationAttribute()
    {
        $total = $this->licences()->count();
        if ($total === 0) {
            return 0;
        }

        return round(
            ($this->licences()->where('actif', true)->count() / $total) * 100
        );
    }

    public function getLicencesExpirantAttribute()
    {
        return $this->licences()
            ->whereDate('date_expiration', '<=', now()->addDays(30))
            ->where('actif', true)
            ->count();
    }
}
