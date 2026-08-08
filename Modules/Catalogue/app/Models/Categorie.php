<?php

namespace Modules\Catalogue\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use Modules\Catalogue\Services\CodeSequence;
use Modules\Core\Traits\LogsActivityWithModule;

class Categorie extends Model
{
    use HasFactory, LogsActivityWithModule;

    protected $table = 'catalogue_categories';

    protected $fillable = [
        'code',
        'libelle',
        'parent_id',
        'est_actif',
        'est_systeme',
        'created_by',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'est_systeme' => 'boolean',
    ];

    protected static function booted(): void
    {
        // La propriété du trait ne peut pas être redéclarée avec une autre valeur.
        static::$activityModule = 'catalogue';

        static::saving(function (self $categorie) {
            $categorie->validerProfondeur();

            if (! $categorie->exists) {
                $categorie->code = $categorie->code ?: static::generateCode();
                $categorie->created_by = $categorie->created_by ?? auth()->id();
            }
        });
    }

    /**
     * Hiérarchie limitée à 2 niveaux (SFD §6) : une sous-catégorie ne peut
     * ni avoir pour parent une autre sous-catégorie, ni posséder d'enfants.
     */
    protected function validerProfondeur(): void
    {
        if ($this->parent_id === null) {
            return;
        }

        if ($this->exists && (int) $this->parent_id === (int) $this->id) {
            throw new InvalidArgumentException('Une catégorie ne peut pas être son propre parent.');
        }

        $parent = static::find($this->parent_id);

        if ($parent === null) {
            throw new InvalidArgumentException('La catégorie parente est introuvable.');
        }

        if ($parent->parent_id !== null) {
            throw new InvalidArgumentException('Profondeur maximale de 2 niveaux : la catégorie parente est déjà une sous-catégorie.');
        }

        if ($this->exists && $this->enfants()->exists()) {
            throw new InvalidArgumentException('Une catégorie possédant des sous-catégories ne peut pas devenir elle-même une sous-catégorie.');
        }
    }

    /**
     * Code CAT-XXX, transactionnel sous verrou (voir CodeSequence).
     */
    public static function generateCode(): string
    {
        return sprintf('CAT-%03d', CodeSequence::next('CAT'));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'categorie_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\User::class, 'created_by');
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('est_actif', true);
    }

    /**
     * Chemin lisible « Parent > Enfant » (ou le libellé seul pour une racine).
     */
    public function getCheminAttribute(): string
    {
        return $this->parent
            ? "{$this->parent->libelle} > {$this->libelle}"
            : $this->libelle;
    }

    protected static function newFactory()
    {
        return \Modules\Catalogue\Database\Factories\CategorieFactory::new();
    }
}
