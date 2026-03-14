<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_batiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('organisation_sites')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->integer('nombre_etages')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_batiments');
    }
};
