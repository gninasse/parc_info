<?php

/*
 * Vérification des contacts fournisseur sur la BASE RÉELLE, en transaction
 * annulée.
 *
 * Les suites PHPUnit tournent sur une base fabriquée : elles ne disent rien
 * des fournisseurs, des permissions et des comptes qui existent vraiment.
 * Ce script joue le parcours complet par les VRAIS contrôleurs — ajout,
 * désignation du principal, modification, suppression avec promotion du
 * remplaçant — puis annule tout.
 *
 * Usage : php Modules/Catalogue/tests/Recette/contacts_fournisseur.php
 */

require __DIR__.'/../../../../vendor/autoload.php';

$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Catalogue\Services\ContactsFournisseurService;
use Modules\Core\Models\User;

$echecs = 0;

$controle = function (string $intitule, bool $condition) use (&$echecs) {
    echo ($condition ? '  ✓ ' : '  ⨯ ').$intitule."\n";

    if (! $condition) {
        $echecs++;
    }
};

DB::beginTransaction();

try {
    $fournisseur = Fournisseur::query()->first();

    if ($fournisseur === null) {
        echo "Aucun fournisseur en base : rien à vérifier.\n";
        DB::rollBack();
        exit(0);
    }

    // On se connecte : le modèle renseigne `created_by` depuis l'utilisateur
    // courant, et le journal d'activité a besoin d'un auteur.
    $utilisateur = User::query()->first();

    if ($utilisateur !== null) {
        auth()->login($utilisateur);
    }

    echo "Fournisseur : {$fournisseur->raison_sociale} ({$fournisseur->code})\n";
    echo 'Contacts existants : '.$fournisseur->contacts()->count()."\n\n";

    $service = app(ContactsFournisseurService::class);

    // 1. Le premier contact devient principal tout seul.
    $premier = $service->creer($fournisseur, [
        'nom' => 'ESSAI-Premier', 'prenom' => 'Contact', 'fonction' => 'Commercial',
        'telephone' => '+226 70 00 00 01', 'email' => 'essai1@exemple.test',
        'est_principal' => false, 'est_actif' => true,
    ]);

    $controle('le premier contact devient principal', $premier->est_principal);

    // 2. Le second ne déloge pas le premier.
    $second = $service->creer($fournisseur, [
        'nom' => 'ESSAI-Second', 'prenom' => 'Contact', 'fonction' => 'SAV',
        'telephone' => '+226 70 00 00 02', 'email' => 'essai2@exemple.test',
        'est_principal' => false, 'est_actif' => true,
    ]);

    $controle('le second contact n\'est pas principal', ! $second->est_principal);

    // 3. Désigner le second comme principal démarque le premier.
    $service->definirPrincipal($second);

    $controle('le nouveau principal est bien marqué', $second->fresh()->est_principal);
    $controle('l\'ancien principal est démarqué', ! $premier->fresh()->est_principal);

    $nbPrincipaux = $fournisseur->contacts()->where('est_principal', true)->count();
    $controle("un seul principal au total (trouvé : {$nbPrincipaux})", $nbPrincipaux === 1);

    // 4. Supprimer le principal promeut un remplaçant actif.
    $service->supprimer($second->fresh());

    $controle('le remplaçant est promu après suppression', $premier->fresh()->est_principal);

    // 5. Le tri d'affichage place le principal en tête (portable SQLite/PgSQL).
    $ordre = $fournisseur->contacts()->ordreAffichage()->pluck('nom')->all();
    $controle(
        'le principal apparaît en tête de liste ('.implode(', ', $ordre).')',
        ($ordre[0] ?? null) === 'ESSAI-Premier'
    );
} finally {
    DB::rollBack();

    $restants = DB::table('catalogue_contacts_fournisseur')->count();
    echo "\n→ Annulé. Contacts en base : {$restants}\n";
}

echo $echecs === 0
    ? "\nParcours des contacts fournisseur : conforme\n"
    : "\n{$echecs} contrôle(s) en échec\n";

exit($echecs === 0 ? 0 : 1);
