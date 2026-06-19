<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Equipement extends Model
{
    use LogsActivity;

    protected $table = 'parc_info_equipements';

    protected $fillable = [
        'code_inventaire',
        'numero_serie',
        'marque_id',
        'modele',
        'date_acquisition',
        'date_mise_en_service',
        'valeur_achat',
        'duree_vie_probable',
        'date_fin_garantie',
        'statut',
        'etat',
        'tags',
        'ref_bordereau',
        'direction_id',
        'service_id',
        'unite_id',
    ];

    protected $casts = [
        'date_acquisition' => 'date',
        'date_mise_en_service' => 'date',
        'date_fin_garantie' => 'date',
        'valeur_achat' => 'decimal:2',
        'tags' => 'array',
    ];

    // Spécialisations ──
    public function ordinateur(): HasOne
    {
        return $this->hasOne(Ordinateur::class, 'equipement_id');
    }

    public function reseau(): HasOne
    {
        return $this->hasOne(EquipementReseau::class, 'equipement_id');
    }

    public function serveur(): HasOne
    {
        return $this->hasOne(Serveur::class, 'equipement_id');
    }

    public function serveurVirtuel(): HasOne
    {
        return $this->hasOne(ServeurVirtuel::class, 'equipement_id');
    }

    public function infrastructure(): HasOne
    {
        return $this->hasOne(Infrastructure::class, 'equipement_id');
    }

    public function mobile(): HasOne
    {
        return $this->hasOne(Mobile::class, 'equipement_id');
    }

    public function imprimante(): HasOne
    {
        return $this->hasOne(Imprimante::class, 'equipement_id');
    }

    public function scanner(): HasOne
    {
        return $this->hasOne(Scanner::class, 'equipement_id');
    }

    public function telephone(): HasOne
    {
        return $this->hasOne(Telephone::class, 'equipement_id');
    }

    public function camera(): HasOne
    {
        return $this->hasOne(CameraIP::class, 'equipement_id');
    }

    // Propriétés ──
    public function marque(): BelongsTo
    {
        return $this->belongsTo(Marque::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(\Modules\Organisation\Models\Direction::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(\Modules\Organisation\Models\Service::class);
    }

    public function unite(): BelongsTo
    {
        return $this->belongsTo(\Modules\Organisation\Models\Unite::class);
    }

    // Affectations ──
    public function affectations(): HasMany
    {
        return $this->hasMany(AffectationEquipement::class, 'equipement_id');
    }

    public function affectationsLicences(): HasMany
    {
        return $this->hasMany(AffectationLicence::class, 'equipement_id');
    }

    public function licencesActives(): HasMany
    {
        return $this->hasMany(AffectationLicence::class, 'equipement_id')
            ->where('actif', true);
    }

    public function affectationActive(): HasOne
    {
        return $this->hasOne(AffectationEquipement::class, 'equipement_id')
            ->where('statut', true);
    }

    // Historique ──
    public function historique(): HasMany
    {
        return $this->hasMany(HistoriqueChangement::class, 'equipement_id')
            ->orderBy('date_changement', 'desc');
    }

    protected static function booted(): void
    {
        static::creating(function (Equipement $equipement) {
            if (request()->has('ref_bordereau')) {
                $ref = request()->input('ref_bordereau');
                $equipement->ref_bordereau = is_string($ref) ? substr($ref, 0, 255) : null;
            }
        });

        static::updating(function (Equipement $equipement) {
            if (request()->has('ref_bordereau')) {
                $ref = request()->input('ref_bordereau');
                $equipement->ref_bordereau = is_string($ref) ? substr($ref, 0, 255) : null;
            }
        });

        static::saving(function (Equipement $equipement) {
            if ($equipement->id) {
                $activeAff = AffectationEquipement::where('equipement_id', $equipement->id)
                    ->where('statut', true)
                    ->first();
                if ($activeAff) {
                    $equipement->direction_id = $activeAff->direction_id;
                    $equipement->service_id = $activeAff->service_id;
                    $equipement->unite_id = $activeAff->unite_id;
                } else {
                    $equipement->direction_id = null;
                    $equipement->service_id = null;
                    $equipement->unite_id = null;
                }
            } else {
                $equipement->direction_id = null;
                $equipement->service_id = null;
                $equipement->unite_id = null;
            }
        });
    }

    // Logs ──
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Accessors ──
    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            'en_stock' => '<span class="badge bg-secondary"><i class="bi bi-box me-1"></i>En stock</span>',
            'en_service' => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>En service</span>',
            'en_reparation' => '<span class="badge bg-warning"><i class="bi bi-tools me-1"></i>En réparation</span>',
            'perdu' => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Perdu/Volé</span>',
            'reforme' => '<span class="badge bg-dark"><i class="bi bi-trash me-1"></i>Réformé</span>',
            default => '<span class="badge bg-light text-dark">Inconnu</span>',
        };
    }
}
