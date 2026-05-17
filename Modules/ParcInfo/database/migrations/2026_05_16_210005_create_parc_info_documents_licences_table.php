<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_documents_licences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licence_id')->constrained('parc_info_licences')->onDelete('cascade');
            $table->enum('type', ['Facture', 'Certificat', 'Contrat', 'Cle', 'Proof of License']);
            $table->string('nom_fichier');
            $table->string('chemin_stockage');
            $table->date('date_document')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['licence_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_documents_licences');
    }
};
