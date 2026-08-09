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
use Modules\Stock\Models\Concerns\PorteDesDocuments;

class Entree extends Model
{
    use EstDocumentStock, HasFactory, JournaliseActiviteStock, PorteBeneficiaire, PorteDesDocuments;

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
        'bon_commande_id',
        'observation_type',
        'observation',
        'ecarts_bl',
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
        // BR-04 : le rapprochement BL ↔ saisie, ligne à ligne.
        'ecarts_bl' => 'array',
    ];

    /**
     * BR-04 — motifs courts d'un écart constaté au quai.
     *
     * Trois cas couvrent la réalité du comptoir : il en manque, c'est abîmé
     * et refusé, ou le livreur en apporte plus que commandé et on refuse le
     * surplus. Le texte libre reste dans l'observation.
     */
    public const MOTIFS_ECART = [
        'manquant' => 'Manquant',
        'endommage_refuse' => 'Endommagé — refusé',
        'excedent_refuse' => 'Excédent refusé',
    ];

    /** L'observation qui déclenche la saisie structurée des écarts. */
    public const OBSERVATION_ECART_BL = 'ecart_bl';

    /**
     * Les écarts déclarés, nettoyés — jamais null, toujours itérable.
     *
     * @return \Illuminate\Support\Collection<int, array>
     */
    public function ecartsDeclares(): \Illuminate\Support\Collection
    {
        return collect($this->ecarts_bl ?? [])->filter(fn ($ligne) => is_array($ligne))->values();
    }

    /** Y a-t-il un écart déclaré sur cette réception ? (pilule rouge d'Achat) */
    public function aUnEcartBl(): bool
    {
        return $this->ecartsDeclares()->isNotEmpty();
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    /**
     * Bon de commande dont cette entrée est une livraison (raccordement
     * PRQ-05). Nul en mode libre : retours, régularisations, achats hors
     * module — le raccordement ajoute un mode, il n'en supprime aucun.
     *
     * Relation déclarée en chaîne de caractères : le module Stock ne dépend
     * pas d'Achat (c'est Achat qui requiert Stock). Si Achat n'est pas
     * installé, la colonne reste nulle et la relation n'est jamais sollicitée.
     */
    public function bonCommande(): BelongsTo
    {
        return $this->belongsTo(\Modules\Achat\Models\BonCommande::class, 'bon_commande_id');
    }

    /** Le bon est-il une livraison adossée à une commande ? */
    public function estLieeAUneCommande(): bool
    {
        return $this->bon_commande_id !== null;
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
