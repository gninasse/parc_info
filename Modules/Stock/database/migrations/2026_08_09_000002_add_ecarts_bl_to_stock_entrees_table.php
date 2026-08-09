<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BR-04 — le RAPPROCHEMENT BL ↔ saisie : ce que le bordereau annonce
     * face à ce qui est réellement compté.
     *
     * Aujourd'hui l'écart se perd dans l'observation en texte libre
     * (« il manquait 2 cartons »), donc il n'est ni imprimable proprement sur
     * la pièce de réclamation, ni agrégeable par fournisseur. Un fournisseur
     * qui annonce 10 et livre 8 trois fois par trimestre reste invisible.
     *
     * Le stockage est un JSON de lignes :
     *
     *   [{ "article_id": 12, "designation": "Toner 26A",
     *      "quantite_annoncee_bl": 10, "quantite_comptee": 8,
     *      "motif": "manquant" }]
     *
     * Choix assumés :
     *
     *   - FACULTATIF : jamais un frein au quai. Un magasinier pressé valide
     *     sans rien remplir, comme avant ;
     *   - AUCUN effet sur les compteurs : les reliquats d'Achat ne connaissent
     *     que le COMPTÉ (RGC-02 inchangé). L'écart documente et signale,
     *     c'est tout. C'est ce qui permet de le saisir sans risque ;
     *   - JSON et non une table : ces lignes ne se recherchent pas isolément,
     *     elles accompagnent leur bon d'entrée et se lisent avec lui. Une
     *     table dédiée coûterait des jointures pour aucun usage réel.
     */
    public function up(): void
    {
        if (! Schema::hasTable('stock_entrees') || Schema::hasColumn('stock_entrees', 'ecarts_bl')) {
            return;
        }

        Schema::table('stock_entrees', function (Blueprint $table) {
            $table->json('ecarts_bl')->nullable()->after('observation');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('stock_entrees', 'ecarts_bl')) {
            return;
        }

        Schema::table('stock_entrees', function (Blueprint $table) {
            $table->dropColumn('ecarts_bl');
        });
    }
};
