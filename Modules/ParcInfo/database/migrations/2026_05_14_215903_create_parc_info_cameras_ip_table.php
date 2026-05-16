<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_cameras_ip', function (Blueprint $table) {
            $table->foreignId('equipement_id')->primary()->constrained('parc_info_equipements')->cascadeOnDelete();
            $table->ipAddress('adresse_ip')->nullable();
            $table->text('adresse_mac')->nullable()->unique();
            $table->text('resolution')->nullable()->comment('ex: 1080p, 4K, 5MP');
            $table->text('type_camera')->nullable()->comment('Dôme, Bullet, PTZ, etc.');
            $table->text('emplacement')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_cameras_ip');
    }
};
