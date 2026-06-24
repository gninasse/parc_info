<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parametre extends Model
{
    use HasFactory;

    protected $table = 'achat_parametres';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cle',
        'valeur',
        'description',
    ];

    /**
     * Obtenir la valeur d'un paramètre par sa clé.
     */
    public static function getVal(string $cle, mixed $default = null): ?string
    {
        $param = static::where('cle', $cle)->first();

        return $param ? $param->valeur : $default;
    }

    /**
     * Définir la valeur d'un paramètre.
     */
    public static function setVal(string $cle, ?string $valeur, ?string $description = null): self
    {
        return static::updateOrCreate(
            ['cle' => $cle],
            [
                'valeur' => $valeur,
                'description' => $description ?? (static::where('cle', $cle)->value('description') ?? ''),
            ]
        );
    }
}
