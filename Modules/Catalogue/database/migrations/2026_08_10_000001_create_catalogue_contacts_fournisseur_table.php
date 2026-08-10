<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contacts d'un fournisseur.
 *
 * POURQUOI UNE TABLE, alors que `catalogue_fournisseurs.contact` existe déjà ?
 * Ce champ est un TEXTE LIBRE : il porte le nom de l'interlocuteur habituel,
 * et il n'en accepte qu'un. Or un fournisseur a en pratique plusieurs
 * interlocuteurs (commercial, service après-vente, comptabilité), chacun avec
 * ses coordonnées. Les empiler dans une chaîne rend l'information
 * inexploitable : on ne peut ni téléphoner depuis la fiche, ni savoir qui
 * appeler pour une facture.
 *
 * Le champ `contact` est CONSERVÉ tel quel : il est renseigné sur les
 * fournisseurs existants, il est affiché sur la fiche et il est repris par
 * les documents. Le supprimer réécrirait des données saisies. Les deux
 * coexistent donc, et la fiche explique lequel sert à quoi.
 *
 * `est_principal` désigne l'interlocuteur par défaut. La règle « un seul
 * principal par fournisseur » est tenue par le service (transaction), pas par
 * un index unique partiel : ce type d'index ne s'écrit pas de la même façon
 * sous SQLite et sous PostgreSQL, et le projet doit tourner sur les deux.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogue_contacts_fournisseur', function (Blueprint $table) {
            $table->id();

            // `cascade` : un contact n'a aucun sens sans son fournisseur. Il
            // ne s'agit pas d'une pièce à conserver, contrairement aux
            // documents d'un bon de commande.
            $table->foreignId('fournisseur_id')
                ->constrained('catalogue_fournisseurs')
                ->cascadeOnDelete();

            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('fonction')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('est_principal')->default(false);
            $table->boolean('est_actif')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // L'accès se fait toujours PAR fournisseur (onglet de la fiche) :
            // l'index porte donc sur cette colonne, avec l'état d'activité qui
            // sert au tri d'affichage.
            $table->index(['fournisseur_id', 'est_actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_contacts_fournisseur');
    }
};
