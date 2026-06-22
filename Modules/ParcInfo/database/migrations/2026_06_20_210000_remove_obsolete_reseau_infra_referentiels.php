<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Delete type_reseau and type_infrastructure from parc_info_dictionnaires
        // This cascades to delete their associated values in parc_info_dictionnaire_valeurs
        DB::table('parc_info_dictionnaires')->whereIn('code', ['type_reseau', 'type_infrastructure'])->delete();

        // 2. Delete configuration entries for type_reseau_id and type_infra_id from parc_info_champs_config
        DB::table('parc_info_champs_config')->whereIn('code', ['type_reseau_id', 'type_infra_id'])->delete();

        // 3. Delete Spatie permissions associated with types-reseaux and types-infrastructures
        DB::table('permissions')->where('name', 'like', 'parc-info.referentiels.types-reseaux.%')->delete();
        DB::table('permissions')->where('name', 'like', 'parc-info.referentiels.types-infrastructures.%')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not easily reversible without restoring previous states
    }
};
