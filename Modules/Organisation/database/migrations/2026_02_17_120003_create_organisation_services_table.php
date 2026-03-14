<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('direction_id')->constrained('organisation_directions');
            $table->foreignId('site_id')->constrained('organisation_sites'); // Dénormalisation
            $table->string('code')->unique();
            $table->string('libelle');
            $table->foreignId('chef_service_id')->nullable()->constrained('users');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_services');
    }
};
