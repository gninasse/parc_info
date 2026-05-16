<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_types_cameras', function (Blueprint $table) {
            $table->id();
            $table->string('libelle')->unique();
            $table->timestamps();
        });

        Schema::table('parc_info_cameras_ip', function (Blueprint $table) {
            $table->dropColumn('type_camera');
            $table->foreignId('type_camera_id')->nullable()->constrained('parc_info_types_cameras')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parc_info_cameras_ip', function (Blueprint $table) {
            $table->dropForeign(['type_camera_id']);
            $table->dropColumn('type_camera_id');
            $table->text('type_camera')->nullable();
        });
        Schema::dropIfExists('parc_info_types_cameras');
    }
};
