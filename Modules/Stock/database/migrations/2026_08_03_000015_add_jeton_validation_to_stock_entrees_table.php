<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotence de la validation (I9/S3) : le jeton émis avec l'écran est
     * enregistré au moment de la validation ; le rejouer renvoie la même
     * réponse sans réécrire.
     */
    public function up(): void
    {
        Schema::table('stock_entrees', function (Blueprint $table) {
            $table->string('jeton_validation')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stock_entrees', function (Blueprint $table) {
            $table->dropColumn('jeton_validation');
        });
    }
};
