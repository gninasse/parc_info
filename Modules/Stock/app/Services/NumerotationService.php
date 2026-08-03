<?php

namespace Modules\Stock\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Inventaire;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Models\Transfert;

/**
 * Numérotation S4 : numéro attribué UNIQUEMENT à la validation, sous verrou,
 * par table et par année. Les brouillons n'en consomment pas — aucun trou
 * de séquence à la suppression d'un brouillon.
 *
 * Préfixe des entrées : ENT (choix ENT vs REC figé ici, conforme SFD §6.1).
 */
class NumerotationService
{
    private const PREFIXES = [
        Entree::class => 'ENT',
        Sortie::class => 'SOR',
        Transfert::class => 'TRF',
        Inventaire::class => 'INV',
    ];

    /**
     * Attribue et enregistre le numéro du document ({PREFIXE}-{année}-{séq
     * sur 4 chiffres}, remise à 1 au changement d'année).
     */
    public function attribuer(Model $document): string
    {
        $prefixe = self::PREFIXES[get_class($document)] ?? null;

        if ($prefixe === null) {
            throw new InvalidArgumentException('Document non numérotable : '.get_class($document));
        }

        $annee = now()->year;

        $numero = DB::transaction(function () use ($prefixe, $annee) {
            DB::table('stock_sequences')->insertOrIgnore([
                'prefixe' => $prefixe,
                'annee' => $annee,
                'last_value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $suivant = (int) DB::table('stock_sequences')
                ->where('prefixe', $prefixe)
                ->where('annee', $annee)
                ->lockForUpdate()
                ->value('last_value') + 1;

            DB::table('stock_sequences')
                ->where('prefixe', $prefixe)
                ->where('annee', $annee)
                ->update(['last_value' => $suivant, 'updated_at' => now()]);

            return sprintf('%s-%d-%04d', $prefixe, $annee, $suivant);
        });

        $document->forceFill(['numero' => $numero])->save();

        return $numero;
    }
}
