<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * D-22 — les notifications du module Achat.
     *
     * Aujourd'hui les acteurs GUETTENT : le validateur ouvre la liste « au
     * cas où » un bon attendrait son visa, l'acheteur rappelle le magasin
     * pour savoir si la livraison est arrivée. Ce guet coûte du temps à tout
     * le monde et retarde les décisions.
     *
     * Deux tables :
     *
     *   - `notifications` (table standard de Laravel) : le canal « base de
     *     données » alimente la cloche de l'interface. Le mail seul ne
     *     suffirait pas — il se perd, et l'utilisateur qui revient d'une
     *     semaine d'absence n'a aucun endroit où voir ce qu'il a manqué ;
     *   - `achat_preferences_notification` : l'interrupteur par utilisateur
     *     et par type. Sans lui, la seule échappatoire d'un utilisateur
     *     noyé serait de créer une règle de filtrage dans sa messagerie —
     *     et l'information serait alors perdue pour de bon.
     *
     * L'absence de préférence vaut ACTIF : un nouvel utilisateur reçoit ce
     * qui le concerne sans avoir rien à configurer.
     */
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('achat_preferences_notification')) {
            return;
        }

        Schema::create('achat_preferences_notification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 60);
            $table->boolean('par_mail')->default(true);
            $table->boolean('par_cloche')->default(true);
            $table->timestamps();

            // Une seule préférence par utilisateur et par type.
            $table->unique(['user_id', 'type'], 'unq_preference_notification');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_preferences_notification');

        // `notifications` n'est PAS supprimée : elle est standard Laravel et
        // d'autres modules peuvent s'en servir. Retirer une table partagée au
        // rollback d'un module en casserait d'autres.
    }
};
