<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // EF-STK-05 (lot L2) — le compteur autonome de la fiche consommable
        // est supprimé : les quantités sont lues auprès du module Stock via
        // StockIntegrationInterface. Exécuter `php artisan
        // stock:reprise-initiale` AVANT cette migration.
        // quantite_stock_min/max sont conservés : ce sont des paramètres de
        // catalogue (aide au réapprovisionnement), pas des compteurs.
        Schema::table('parc_info_consommables', function (Blueprint $table) {
            $table->dropIndex(['quantite_stock_actuel']);
            $table->dropColumn('quantite_stock_actuel');
        });
    }

    public function down(): void
    {
        Schema::table('parc_info_consommables', function (Blueprint $table) {
            $table->unsignedInteger('quantite_stock_actuel')->default(0)->after('cout_unitaire');
        });
    }
};
