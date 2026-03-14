<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_unites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('organisation_services');
            $table->foreignId('site_id')->constrained('organisation_sites'); // Dénormalisation
            $table->string('code')->unique();
            $table->string('libelle');
            $table->foreignId('major_id')->nullable()->constrained('users')->comment('Infirmier chef responsable');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_unites');
    }
};
