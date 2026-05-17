<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_logiciels', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->foreignId('type_licence_id')->constrained('parc_info_types_licences')->onDelete('restrict');
            $table->foreignId('editeur_id')->constrained('parc_info_editeurs')->onDelete('restrict');
            $table->string('categorie')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['editeur_id', 'est_actif']);
            $table->index(['code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_logiciels');
    }
};
