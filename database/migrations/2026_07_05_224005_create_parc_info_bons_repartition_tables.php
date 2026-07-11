<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_bons_repartition', function (Blueprint $table) {
            $table->id();
            $table->string('numero_bon', 30)->unique();
            $table->date('date_bon');
            $table->foreignId('fournisseur_id')->nullable()->constrained('parc_info_fournisseurs')->nullOnDelete();
            $table->text('observation')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('parc_info_lignes_bon_repartition', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_id')->constrained('parc_info_bons_repartition')->cascadeOnDelete();
            $table->foreignId('equipement_id')->constrained('parc_info_equipements')->restrictOnDelete();
            $table->string('type_cible', 20)->nullable()->comment('DIRECTION, SERVICE');
            $table->foreignId('direction_id')->nullable()->constrained('organisation_directions')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('organisation_services')->nullOnDelete();
            $table->string('nom_receptionniste', 255)->nullable();
            $table->date('date_livraison')->nullable();
            $table->boolean('est_signe')->default(false);
            $table->timestamp('date_signature')->nullable();
            $table->foreignId('affectation_id')->nullable()->constrained('parc_info_affectation_equipements')->nullOnDelete();
            $table->text('observation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_lignes_bon_repartition');
        Schema::dropIfExists('parc_info_bons_repartition');
    }
};
