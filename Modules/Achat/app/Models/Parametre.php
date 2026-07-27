<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * Paramétrage dynamique du module (EF-ADM-01 à EF-ADM-03).
 *
 * La valeur en base prime toujours sur la configuration de fichier, qui ne
 * sert que de repli au premier démarrage.
 */
class Parametre extends Model
{
    use HasFactory;

    protected $table = 'achat_parametres';

    protected $fillable = [
        'cle',
        'valeur',
        'libelle',
        'description',
        'type_valeur',
        'modifiable',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'modifiable' => 'boolean',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Valeur brute d'un paramètre, ou la valeur de repli fournie. */
    public static function getVal(string $cle, mixed $defaut = null): mixed
    {
        $parametre = static::where('cle', $cle)->first();

        return $parametre ? $parametre->valeur : $defaut;
    }

    /** Valeur typée selon la colonne type_valeur. */
    public static function getTyped(string $cle, mixed $defaut = null): mixed
    {
        $parametre = static::where('cle', $cle)->first();

        if (! $parametre) {
            return $defaut;
        }

        return match ($parametre->type_valeur) {
            'entier' => (int) $parametre->valeur,
            'decimal' => (float) $parametre->valeur,
            'booleen' => filter_var($parametre->valeur, FILTER_VALIDATE_BOOLEAN),
            default => $parametre->valeur,
        };
    }

    public static function setVal(string $cle, ?string $valeur, ?int $userId = null): self
    {
        $parametre = static::firstOrNew(['cle' => $cle]);
        $parametre->valeur = $valeur;
        $parametre->updated_by = $userId;
        $parametre->save();

        return $parametre;
    }

    protected static function newFactory()
    {
        return \Modules\Achat\Database\Factories\ParametreFactory::new();
    }
}
