<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parc_info_infrastructures', function (Blueprint $table) {
            $table->integer('nb_ports')->nullable()->comment('Nombre de ports (ex: 24, 48) — Brassage');
            $table->string('categorie_cable', 50)->nullable()->comment('Cat5e, Cat6, Cat6A, Fibre — Brassage');
            $table->string('type_connecteur', 50)->nullable()->comment('RJ45, LC, SC — Brassage');
            $table->integer('u_taille')->nullable()->comment('Taille en U (ex: 1 ou 2) — Brassage');
        });
    }

    public function down(): void
    {
        Schema::table('parc_info_infrastructures', function (Blueprint $table) {
            $table->dropColumn(['nb_ports', 'categorie_cable', 'type_connecteur', 'u_taille']);
        });
    }
};
