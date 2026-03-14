<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_directions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('organisation_sites');
            $table->string('code')->unique();
            $table->string('libelle');
            $table->foreignId('responsable_id')->nullable()->constrained('users');
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_directions');
    }
};
