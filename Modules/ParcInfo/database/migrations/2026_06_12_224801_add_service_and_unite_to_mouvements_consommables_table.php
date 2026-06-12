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
        Schema::table('parc_info_mouvements_consommables', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->constrained('organisation_services')->onDelete('set null');
            $table->foreignId('unite_id')->nullable()->constrained('organisation_unites')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parc_info_mouvements_consommables', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
            $table->dropForeign(['unite_id']);
            $table->dropColumn('unite_id');
        });
    }
};
