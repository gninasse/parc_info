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
        Schema::table('parc_info_equipements', function (Blueprint $table) {
            $table->foreignId('direction_id')->nullable()->constrained('organisation_directions')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('organisation_services')->nullOnDelete();
            $table->foreignId('unite_id')->nullable()->constrained('organisation_unites')->nullOnDelete();
        });

        // Backfill des équipements existants à partir de leur affectation active
        $activeAssignments = \Illuminate\Support\Facades\DB::table('parc_info_affectation_equipements')
            ->where('statut', true)
            ->get();

        foreach ($activeAssignments as $assignment) {
            \Illuminate\Support\Facades\DB::table('parc_info_equipements')
                ->where('id', $assignment->equipement_id)
                ->update([
                    'direction_id' => $assignment->direction_id,
                    'service_id' => $assignment->service_id,
                    'unite_id' => $assignment->unite_id,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parc_info_equipements', function (Blueprint $table) {
            $table->dropForeign(['direction_id']);
            $table->dropForeign(['service_id']);
            $table->dropForeign(['unite_id']);

            $table->dropColumn(['direction_id', 'service_id', 'unite_id']);
        });
    }
};
