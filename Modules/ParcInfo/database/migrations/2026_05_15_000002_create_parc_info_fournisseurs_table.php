<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->string('type')->nullable(); // Editeur, Revendeur, Distributeur
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->text('adresse')->nullable();
            $table->foreignId('contact_principal_id')->nullable()->constrained('parc_info_contacts')->onDelete('set null');
            $table->string('conditions_paiement')->nullable();
            $table->string('delai_livraison')->nullable();
            $table->integer('fiabilite_score')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_fournisseurs');
    }
};
