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
        Schema::create('organisation_postes_travail', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique()->comment('Format: POST-[CODE_SERVICE]-[SEQ]');
            $table->string('libelle', 255);
            $table->text('description')->nullable();

            $table->foreignId('direction_id')->constrained('organisation_directions')->onDelete('restrict');
            $table->foreignId('service_id')->constrained('organisation_services')->onDelete('restrict');
            $table->foreignId('unite_id')->nullable()->constrained('organisation_unites')->onDelete('set null');
            $table->foreignId('local_id')->nullable()->constrained('organisation_locaux')->onDelete('set null');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');

            $table->string('statut', 50)->default('actif')->comment('actif, inactif, en_renovation, supprime');
            $table->boolean('actif')->default(true);

            $table->timestamps();

            $table->index(['code']);
            $table->index(['service_id']);
            $table->index(['agent_id']);
            $table->index(['statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organisation_postes_travail');
    }
};
