<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_sites', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->text('adresse')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Seeder immédiat
        DB::table('organisation_sites')->insert([
            [
                'code' => 'SITE-PRINCIPAL',
                'libelle' => 'Site Principal',
                'description' => 'Campus historique du CHU-YO',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SITE-GERIATRIE',
                'libelle' => 'Site Gériatrie',
                'description' => 'Centre spécialisé en gériatrie',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_sites');
    }
};
