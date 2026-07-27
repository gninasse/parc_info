<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // EF-STK-05 — lien fiable entre la fiche consommable (catalogue
        // ParcInfo) et le référentiel article (achat_articles), pour lire
        // les quantités auprès du module Stock.
        Schema::table('parc_info_consommables', function (Blueprint $table) {
            $table->foreignId('article_id')->nullable()
                ->constrained('achat_articles')->nullOnDelete();
        });

        // Rapprochement initial par code (parc_info_consommables.code est le
        // code_article utilisé par les intégrations Achat et Stock).
        $correspondances = DB::table('achat_articles')->pluck('id', 'code_article');

        DB::table('parc_info_consommables')
            ->whereNull('article_id')
            ->orderBy('id')
            ->each(function ($consommable) use ($correspondances) {
                if (isset($correspondances[$consommable->code])) {
                    DB::table('parc_info_consommables')
                        ->where('id', $consommable->id)
                        ->update(['article_id' => $correspondances[$consommable->code]]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('parc_info_consommables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('article_id');
        });
    }
};
