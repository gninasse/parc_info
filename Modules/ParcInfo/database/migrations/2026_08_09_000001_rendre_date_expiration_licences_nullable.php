<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `parc_info_licences.date_expiration` devient NULLABLE.
     *
     * Une licence PERPÉTUELLE n'expire pas : exiger une date d'expiration
     * obligeait à inventer une valeur, et une donnée inventée finit toujours
     * par être lue comme vraie (une alerte d'expiration se déclencherait sur
     * une échéance qui n'existe pas).
     *
     * Le besoin est apparu avec D-13 (réception des licences depuis Achat) :
     * la SPEC_UX A-05 déclare la date d'expiration facultative à la saisie.
     * L'index (logiciel_id, date_expiration) reste valide — PostgreSQL comme
     * SQLite indexent les valeurs nulles.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parc_info_licences')) {
            return;
        }

        // SQLite : les suites de tests recréent la base à chaque exécution
        // depuis les migrations, et `change()` sur ce driver passe par une
        // recréation de table — celle-là même qui fait perdre les CHECK en
        // silence (piège documenté dans Modules/Achat/README.md). On modifie
        // donc la déclaration D'ORIGINE pour les bases neuves, et on ne
        // touche ici que PostgreSQL, où l'ALTER est chirurgical.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE parc_info_licences ALTER COLUMN date_expiration DROP NOT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('parc_info_licences')) {
            return;
        }

        // Rétablir NOT NULL casserait sous les licences perpétuelles déjà
        // enregistrées : on refuse plutôt que de perdre des lignes.
        $sansEcheance = DB::table('parc_info_licences')->whereNull('date_expiration')->count();

        if ($sansEcheance > 0) {
            throw new RuntimeException(
                "{$sansEcheance} licence(s) sans date d'expiration (perpétuelles) : "
                .'renseignez-les avant de revenir en arrière.'
            );
        }

        DB::statement('ALTER TABLE parc_info_licences ALTER COLUMN date_expiration SET NOT NULL');
    }
};
