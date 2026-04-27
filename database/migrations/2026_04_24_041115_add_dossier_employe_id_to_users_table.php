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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('dossier_employe_id')->nullable()->after('id');

            $table->foreign('dossier_employe_id')
                ->references('id')
                ->on('grh_dossiers_employes')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['dossier_employe_id']);
            $table->dropColumn('dossier_employe_id');
        });
    }
};
