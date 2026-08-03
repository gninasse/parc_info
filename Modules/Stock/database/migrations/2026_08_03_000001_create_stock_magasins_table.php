<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SFD §6.2 : un magasin par site (site_id unique, restrict), rattachement
     * optionnel à un local et à un responsable Grh (set null).
     */
    public function up(): void
    {
        Schema::create('stock_magasins', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // MAG-{CODE_SITE}
            $table->string('libelle');
            $table->foreignId('site_id')->unique()->constrained('organisation_sites')->restrictOnDelete();
            $table->foreignId('local_id')->nullable()->constrained('organisation_locaux')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('grh_dossiers_employes')->nullOnDelete();
            $table->boolean('est_actif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_magasins');
    }
};
