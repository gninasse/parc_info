<?php

namespace Modules\Achat\Tests;

use Illuminate\Support\Str;
use Modules\ParcInfo\Models\Equipement;

/**
 * Fabrique minimale d'objets ParcInfo pour les tests du module Achat : le
 * module lit le parc (rattachements de régularisation) sans le posséder.
 * Calqué sur `Modules\Stock\Database\Factories\ParcInfoDeTest`.
 */
final class ParcInfoDeTest
{
    public static function equipement(array $attributs = []): Equipement
    {
        $suffixe = Str::upper(Str::random(8));

        return Equipement::query()->create(array_merge([
            'code_inventaire' => 'ACH-'.$suffixe,
            'numero_serie' => 'SN-'.$suffixe,
            'modele' => 'Équipement de test',
            'statut' => 'en_stock',
            'etat' => 'bon',
        ], $attributs));
    }
}
