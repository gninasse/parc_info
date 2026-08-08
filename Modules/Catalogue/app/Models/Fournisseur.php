<?php

namespace Modules\Catalogue\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Catalogue\Services\CodeSequence;
use Modules\Core\Traits\LogsActivityWithModule;

class Fournisseur extends Model
{
    use HasFactory, LogsActivityWithModule;

    protected $table = 'catalogue_fournisseurs';

    protected $fillable = [
        'code',
        'raison_sociale',
        'contact',
        'adresse',
        'telephone',
        'email',
        'est_actif',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    protected static function booted(): void
    {
        // La propriété du trait ne peut pas être redéclarée avec une autre valeur.
        static::$activityModule = 'catalogue';

        static::saving(function (self $fournisseur) {
            if (! $fournisseur->exists) {
                $fournisseur->code = $fournisseur->code ?: static::generateCode($fournisseur->raison_sociale);
                $fournisseur->created_by = $fournisseur->created_by ?? auth()->id();
            }
        });
    }

    /**
     * Code FOUR-{2 lettres de la raison sociale}{séquence 3 chiffres},
     * séquence propre à chaque paire de lettres, transactionnelle sous
     * verrou (voir CodeSequence). Ex. : « SoftSell » → FOUR-SO001.
     */
    public static function generateCode(string $raisonSociale): string
    {
        $lettres = strtoupper(preg_replace('/[^A-Za-z]/', '', Str::ascii($raisonSociale)));
        $lettres = str_pad(substr($lettres, 0, 2), 2, 'X');

        $prefix = "FOUR-{$lettres}";

        return sprintf('%s%03d', $prefix, CodeSequence::next($prefix));
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'fournisseur_principal_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\User::class, 'created_by');
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('est_actif', true);
    }

    protected static function newFactory()
    {
        return \Modules\Catalogue\Database\Factories\FournisseurFactory::new();
    }
}
