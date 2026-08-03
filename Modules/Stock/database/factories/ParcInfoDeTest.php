<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Support\Str;
use Modules\ParcInfo\Models\Equipement;

/**
 * ParcInfo n'a pas de factories : fabrique minimale d'équipements pour les
 * factories et tests du module Stock (réutilise les modèles Eloquent
 * existants — S10, jamais de duplication de logique métier).
 */
final class ParcInfoDeTest
{
    public static function equipement(array $attributs = []): Equipement
    {
        $suffixe = Str::upper(Str::random(8));

        return Equipement::query()->create(array_merge([
            'code_inventaire' => 'TST-'.$suffixe,
            'numero_serie' => 'SN-'.$suffixe,
            'modele' => 'Équipement de test',
            'statut' => 'en_stock',
            'etat' => 'bon',
        ], $attributs));
    }
}
