<?php

namespace Modules\Grh\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Unite;

class Employe extends Model
{
    use HasFactory;

    protected $table = 'grh_dossiers_employes';

    protected $appends = ['full_name'];

    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'date_naissance',
        'genre',
        'date_embauche',
        'poste',
        'est_actif',
        'niveau_rattachement',
        'direction_id',
        'service_id',
        'unite_id',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_embauche' => 'date',
        'est_actif' => 'boolean',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'employe_id');
    }

    public function direction()
    {
        return $this->belongsTo(Direction::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function unite()
    {
        return $this->belongsTo(Unite::class);
    }

    public function getFullNameAttribute()
    {
        return strtoupper($this->nom).' '.ucwords(strtolower($this->prenom));
    }

    public function getOrganisationAttribute()
    {
        switch ($this->niveau_rattachement) {
            case 'direction':
                return $this->direction?->libelle;
            case 'service':
                return $this->service?->libelle;
            case 'unite':
                return $this->unite?->libelle;
            default:
                return '-';
        }
    }

    /**
     * Les comptes utilisateurs système liés à cet employé.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'dossier_employe_id');
    }

    protected static function newFactory()
    {
        return \Modules\Grh\database\factories\EmployeFactory::new();
    }
}
