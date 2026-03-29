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
        Schema::table('organisation_postes_travail', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->change();
            $table->string('niveau_rattachement', 50)->nullable()->after('agent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisation_postes_travail', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable(false)->change();
            if (Schema::hasColumn('organisation_postes_travail', 'niveau_rattachement')) {
                $table->dropColumn('niveau_rattachement');
            }
        });
    }
};
