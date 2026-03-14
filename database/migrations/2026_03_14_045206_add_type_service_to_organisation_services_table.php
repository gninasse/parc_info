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
        Schema::table('organisation_services', function (Blueprint $table) {
            $table->enum('type_service', ['administratif', 'clinique', 'medico-technique'])->after('libelle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisation_services', function (Blueprint $table) {
            $table->dropColumn('type_service');
        });
    }
};
