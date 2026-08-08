<?php

namespace Modules\Catalogue\Services;

use Illuminate\Support\Facades\DB;

class CodeSequence
{
    /**
     * Prochaine valeur de la séquence d'un préfixe, sous verrou.
     *
     * Transactionnel : la ligne du préfixe est verrouillée (lockForUpdate)
     * jusqu'au commit, deux générations concurrentes sont donc sérialisées
     * et ne peuvent pas produire la même valeur.
     */
    public static function next(string $prefix): int
    {
        return DB::transaction(function () use ($prefix) {
            DB::table('catalogue_sequences')->insertOrIgnore([
                'prefix' => $prefix,
                'last_value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $current = (int) DB::table('catalogue_sequences')
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->value('last_value');

            $next = $current + 1;

            DB::table('catalogue_sequences')
                ->where('prefix', $prefix)
                ->update(['last_value' => $next, 'updated_at' => now()]);

            return $next;
        });
    }
}
