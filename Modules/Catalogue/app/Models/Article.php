<?php

namespace Modules\Catalogue\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use Modules\Catalogue\Services\CodeSequence;
use Modules\Core\Traits\LogsActivityWithModule;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Logiciel;
use Modules\ParcInfo\Models\Marque;

class Article extends Model
{
    use HasFactory, LogsActivityWithModule;

    protected $table = 'catalogue_articles';

    public const NATURE_CONSOMMABLE = 'consommable';

    public const NATURE_PIECE = 'piece';

    public const NATURE_EQUIPEMENT = 'equipement';

    public const NATURE_LICENCE = 'licence';

    public const NATURES = [
        self::NATURE_CONSOMMABLE,
        self::NATURE_PIECE,
        self::NATURE_EQUIPEMENT,
        self::NATURE_LICENCE,
    ];

    public const NATURE_LABELS = [
        self::NATURE_CONSOMMABLE => 'Consommable',
        self::NATURE_PIECE => 'Pièce détachée',
        self::NATURE_EQUIPEMENT => 'Équipement',
        self::NATURE_LICENCE => 'Licence',
    ];

    public const CODE_PREFIXES = [
        self::NATURE_CONSOMMABLE => 'CONS',
        self::NATURE_PIECE => 'PIE',
        self::NATURE_EQUIPEMENT => 'EQP',
        self::NATURE_LICENCE => 'LIC',
    ];

    protected $fillable = [
        'code',
        'nom',
        'nature',
        'categorie_id',
        'marque_id',
        'modele',
        'reference_constructeur',
        'unite_stock',
        'prix_indicatif',
        'taux_tva',
        'seuil_defaut',
        'fournisseur_principal_id',
        'categorie_equipement_id',
        'logiciel_id',
        'compatibilites',
        'est_actif',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'est_stockable' => 'boolean',
        'est_actif' => 'boolean',
        'prix_indicatif' => 'decimal:2',
        'taux_tva' => 'decimal:2',
        'seuil_defaut' => 'decimal:2',
        'compatibilites' => 'array',
    ];

    protected static function booted(): void
    {
        // La propriété du trait ne peut pas être redéclarée avec une autre valeur.
        static::$activityModule = 'catalogue';

        static::saving(function (self $article) {
            if (! $article->exists) {
                // est_stockable est dérivé de la nature : seule une licence est immatérielle.
                $article->est_stockable = $article->nature !== self::NATURE_LICENCE;
                $article->code = $article->code ?: static::generateCode($article->nature);
                $article->created_by = $article->created_by ?? auth()->id();
            }

            // §6.1 : l'unité est forcée à « unité » pour les modèles d'équipements
            // et les licences (les quantités n'y ont pas d'unité métier).
            if (in_array($article->nature, [self::NATURE_EQUIPEMENT, self::NATURE_LICENCE], true)) {
                $article->unite_stock = 'unité';
            }

            $article->validerCoherenceNature();
        });

        static::updating(function (self $article) {
            if ($article->isDirty('nature')) {
                throw new InvalidArgumentException("La nature d'un article est immuable (règle C6) : créez un nouvel article.");
            }
        });
    }

    /**
     * Double applicative des contraintes CHECK PostgreSQL (portage SQLite) :
     * les FK ParcInfo conditionnelles ne sont renseignées que pour la nature
     * qui les exige.
     */
    protected function validerCoherenceNature(): void
    {
        if (! in_array($this->nature, self::NATURES, true)) {
            throw new InvalidArgumentException("Nature d'article inconnue : {$this->nature}.");
        }

        if ($this->nature === self::NATURE_EQUIPEMENT && $this->categorie_equipement_id === null) {
            throw new InvalidArgumentException("Un article de nature « équipement » doit référencer une catégorie d'équipements du parc.");
        }

        if ($this->nature !== self::NATURE_EQUIPEMENT && $this->categorie_equipement_id !== null) {
            throw new InvalidArgumentException("Seul un article de nature « équipement » peut référencer une catégorie d'équipements.");
        }

        if ($this->nature === self::NATURE_LICENCE && $this->logiciel_id === null) {
            throw new InvalidArgumentException('Un article de nature « licence » doit référencer un logiciel du parc.');
        }

        if ($this->nature !== self::NATURE_LICENCE && $this->logiciel_id !== null) {
            throw new InvalidArgumentException('Seul un article de nature « licence » peut référencer un logiciel.');
        }

        if ($this->nature === self::NATURE_LICENCE && $this->seuil_defaut !== null) {
            throw new InvalidArgumentException('Le seuil par défaut est réservé aux natures stockables (une licence ne se stocke pas).');
        }

        if (in_array($this->nature, [self::NATURE_EQUIPEMENT, self::NATURE_LICENCE], true) && $this->compatibilites !== null) {
            throw new InvalidArgumentException('Les compatibilités sont réservées aux consommables et aux pièces détachées.');
        }
    }

    /**
     * Code {EQP|CONS|PIE|LIC}-XXXXX, séquence 5 chiffres propre à chaque
     * préfixe, transactionnelle sous verrou (voir CodeSequence).
     */
    public static function generateCode(string $nature): string
    {
        $prefix = self::CODE_PREFIXES[$nature] ?? null;

        if ($prefix === null) {
            throw new InvalidArgumentException("Nature d'article inconnue : {$nature}.");
        }

        return sprintf('%s-%05d', $prefix, CodeSequence::next($prefix));
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function marque(): BelongsTo
    {
        return $this->belongsTo(Marque::class, 'marque_id');
    }

    public function fournisseurPrincipal(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_principal_id');
    }

    public function categorieEquipement(): BelongsTo
    {
        return $this->belongsTo(CategorieEquipement::class, 'categorie_equipement_id');
    }

    public function logiciel(): BelongsTo
    {
        return $this->belongsTo(Logiciel::class, 'logiciel_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\User::class, 'created_by');
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('est_actif', true);
    }

    public function scopeNature(Builder $query, string $nature): Builder
    {
        return $query->where('nature', $nature);
    }

    public function getNatureLabelAttribute(): string
    {
        return self::NATURE_LABELS[$this->nature] ?? $this->nature;
    }

    /**
     * Chemin de la catégorie « Parent > Enfant ».
     */
    public function getCategorieCheminAttribute(): ?string
    {
        return $this->categorie?->chemin;
    }

    protected static function newFactory()
    {
        return \Modules\Catalogue\Database\Factories\ArticleFactory::new();
    }
}
