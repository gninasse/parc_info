<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_types_consommables', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->enum('categorie', [
                'Impression',
                'Fournitures Bureau',
                'Maintenance',
                'Reseau',
                'Securite',
                'Accessoires'
            ]);
            $table->string('sous_categorie')->nullable();
            $table->string('unite_stock'); // Cartouche, Rame, Boîte, Litre
            $table->unsignedInteger('seul_reapprovisionnement')->default(5);
            $table->unsignedInteger('duree_conservation_jours')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index('categorie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_types_consommables');
    }
};
