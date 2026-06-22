<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop CTI child tables (foreign keys to equipements and types)
        Schema::dropIfExists('parc_info_ordinateurs');
        Schema::dropIfExists('parc_info_imprimantes');
        Schema::dropIfExists('parc_info_scanners');
        Schema::dropIfExists('parc_info_mobiles');
        Schema::dropIfExists('parc_info_serveurs_virtuels'); // drops virtuels first as it might reference physical serveurs
        Schema::dropIfExists('parc_info_serveurs');
        Schema::dropIfExists('parc_info_telephones');
        Schema::dropIfExists('parc_info_equipements_reseaux');
        Schema::dropIfExists('parc_info_cameras_ip');
        Schema::dropIfExists('parc_info_infrastructures');

        // 2. Drop old lookup tables
        Schema::dropIfExists('parc_info_types_rams');
        Schema::dropIfExists('parc_info_types_cpus');
        Schema::dropIfExists('parc_info_types_disques');
        Schema::dropIfExists('parc_info_types_os');
        Schema::dropIfExists('parc_info_types_imprimantes');
        Schema::dropIfExists('parc_info_types_reseaux');
        Schema::dropIfExists('parc_info_types_mobiles');
        Schema::dropIfExists('parc_info_types_infrastructures');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop is not directly reversible without restoring the sql backup
    }
};
