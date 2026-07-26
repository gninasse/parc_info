<?php

namespace Modules\Stock\Traits;

use Illuminate\Support\Facades\DB;

trait GeneratesDocumentNumbers
{
    public function genererNumero(string $type, string $prefix): string
    {
        return DB::transaction(function () use ($type, $prefix) {
            $annee = date('Y');

            $seq = DB::table('stock_sequences')
                ->where('type', $type)
                ->where('annee', $annee)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                DB::table('stock_sequences')->insert([
                    'type' => $type,
                    'annee' => $annee,
                    'compteur' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $compteur = 1;
            } else {
                $compteur = $seq->compteur + 1;
                DB::table('stock_sequences')
                    ->where('id', $seq->id)
                    ->update([
                        'compteur' => $compteur,
                        'updated_at' => now(),
                    ]);
            }

            return $prefix.'-'.$annee.'-'.str_pad($compteur, 4, '0', STR_PAD_LEFT);
        });
    }
}
