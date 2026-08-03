<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Modules\Stock\Models\Concerns\EstDocumentStock;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;
use Modules\Stock\Models\Concerns\PorteBeneficiaire;

class Entree extends Model
{
    use EstDocumentStock, HasFactory, JournaliseActiviteStock, PorteBeneficiaire;

    public const STATUT_BROUILLON = 'BROUILLON';

    public const STATUT_REFERENCEMENT = 'REFERENCEMENT';

    public const STATUT_VALIDE = 'VALIDE';

    public const STATUT_ANNULE = 'ANNULE';

    /** Phase de références du triptyque (D13). */
    public const STATUT_PHASE = self::STATUT_REFERENCEMENT;

    public const STATUTS = [
        self::STATUT_BROUILLON,
        self::STATUT_REFERENCEMENT,
        self::STATUT_VALIDE,
        self::STATUT_ANNULE,
    ];

    /** Libellés orientés geste (amendement UX n°2 — noms techniques en base). */
    public const STATUT_LABELS = [
        self::STATUT_BROUILLON => 'Brouillon',
        self::STATUT_REFERENCEMENT => 'Saisie des n° de série',
        self::STATUT_VALIDE => 'Validé',
        self::STATUT_ANNULE => 'Annulé',
    ];

    public const NATURE_LIVRAISON = 'livraison';

    public const NATURE_RETOUR = 'retour';

    protected $table = 'stock_entrees';

    /** Reflet en mémoire des défauts du schéma (statut géré par transitions). */
    protected $attributes = [
        'statut' => self::STATUT_BROUILLON,
        'nature' => self::NATURE_LIVRAISON,
    ];

    protected $fillable = [
        'date_document',
        'magasin_id',
        'nature',
        'fournisseur_id',
        'reference_externe',
        'observation_type',
        'observation',
        'beneficiaire_type',
        'beneficiaire_direction_id',
        'beneficiaire_service_id',
        'beneficiaire_unite_id',
        'beneficiaire_poste_id',
        'beneficiaire_local_id',
        'beneficiaire_employe_id',
        'created_by',
    ];

    protected $casts = [
        'date_document' => 'date',
        'valide_le' => 'datetime',
    ];

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneEntree::class, 'entree_id');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(Mouvement::class, 'entree_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function valideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function scopeDuMagasin(Builder $query, int $magasinId): Builder
    {
        return $query->where('magasin_id', $magasinId);
    }

    /** BROUILLON → RÉFÉRENCEMENT : les tampons naissent, les quantités se verrouillent (D13/D16). */
    public function passerEnReferencement(): void
    {
        $this->transitionner([self::STATUT_BROUILLON], self::STATUT_REFERENCEMENT);
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\EntreeFactory::new();
    }
}
