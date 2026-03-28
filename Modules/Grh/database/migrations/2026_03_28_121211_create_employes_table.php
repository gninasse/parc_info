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
        Schema::create('grh_dossiers_employes', function (Blueprint $table) {
            $table->id();
            $table->string('matricule')->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->date('date_naissance')->nullable();
            $table->enum('genre', ['M', 'F'])->nullable();
            $table->date('date_embauche')->nullable();
            $table->string('poste')->nullable();
            $table->boolean('est_actif')->default(true);

            // Rattachement organisationnel
            $table->string('niveau_rattachement')->comment('direction, service, unite');
            $table->foreignId('direction_id')->nullable()->constrained('organisation_directions');
            $table->foreignId('service_id')->nullable()->constrained('organisation_services');
            $table->foreignId('unite_id')->nullable()->constrained('organisation_unites');

            $table->timestamps();
        });

        Schema::create('grh_contacts_employes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('grh_dossiers_employes')->onDelete('cascade');
            $table->string('type_contact')->comment('email, telephone, etc.');
            $table->string('valeur');
            $table->boolean('est_whatsapp')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grh_contacts_employes');
        Schema::dropIfExists('grh_dossiers_employes');
    }
};
