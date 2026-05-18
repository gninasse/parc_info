<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parc_info_scanners', function (Blueprint $table) {
            $table->ipAddress('adresse_ip')->nullable()->after('type_capteur');
            $table->string('interface_connexion')->nullable()->after('adresse_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parc_info_scanners', function (Blueprint $table) {
            $table->dropColumn(['adresse_ip', 'interface_connexion']);
        });
    }
};
