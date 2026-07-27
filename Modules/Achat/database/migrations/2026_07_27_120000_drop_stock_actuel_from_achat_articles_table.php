<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // EF-STK-05 (lot L2) — le module Stock est le référentiel unique des
        // quantités : la projection dénormalisée est supprimée. Exécuter
        // `php artisan stock:reprise-initiale` AVANT cette migration sur une
        // base contenant des quantités à conserver.
        // Le seuil d'alerte reste porté par l'article (seuil global, lu par Stock).
        Schema::table('achat_articles', function (Blueprint $table) {
            $table->dropColumn('stock_actuel');
        });
    }

    public function down(): void
    {
        Schema::table('achat_articles', function (Blueprint $table) {
            $table->integer('stock_actuel')->default(0)->after('seuil_alerte');
        });
    }
};
