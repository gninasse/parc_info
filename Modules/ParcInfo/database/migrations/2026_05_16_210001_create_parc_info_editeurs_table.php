<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_editeurs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->string('logo_url')->nullable();
            $table->string('site_web')->nullable();
            $table->string('email_support')->nullable();
            $table->string('telephone_support')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_editeurs');
    }
};
