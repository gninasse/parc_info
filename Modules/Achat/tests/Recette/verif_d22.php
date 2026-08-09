<?php

/*
 * Vérification D-22 sur la BASE RÉELLE, en transaction annulée.
 *
 * Les suites PHPUnit tournent sur une base FABRIQUÉE : elles ne disent rien
 * des comptes, des permissions et des adresses de courriel qui existent
 * vraiment. Ce script déclenche une notification par le service réel, sur un
 * vrai bon, et contrôle ce qui arrive en base et dans le canal courriel —
 * puis annule tout.
 *
 * C'est le même principe que Recette/lot_br.php : ce qui passe ici et échoue
 * en PHPUnit (ou l'inverse) désigne un écart entre la base de test et la
 * réalité de l'établissement.
 *
 * Usage :
 *   php Modules/Achat/tests/Recette/verif_d22.php
 */

require __DIR__.'/../../../../vendor/autoload.php';

$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Services\NotificationsAchat;
use Modules\Core\Models\User;

DB::beginTransaction();

try {
    $bon = BonCommande::query()->whereNotNull('numero')->first();

    if ($bon === null) {
        echo "Aucun bon numéroté en base : rien à vérifier.\n";
        DB::rollBack();
        exit(0);
    }

    $auteur = User::find($bon->created_by) ?? User::first();
    echo "Bon {$bon->numero} — auteur {$auteur->user_name} <{$auteur->email}>\n";

    // Le renvoi doit être fait par QUELQU'UN D'AUTRE que l'auteur : un
    // utilisateur ne s'auto-notifie jamais. Prendre la même personne des deux
    // côtés produirait « 0 notification » et ferait croire à une panne, alors
    // que ce serait la règle qui s'applique.
    $validateur = User::query()->whereKeyNot($auteur->id)->first();

    if ($validateur === null) {
        echo "Un seul compte en base : impossible de vérifier sans auto-notification.\n";
        DB::rollBack();
        exit(0);
    }

    echo "Renvoi effectué par {$validateur->user_name}\n";

    $avant = DB::table('notifications')->count();

    $journal = storage_path('logs/laravel.log');
    $tailleAvant = file_exists($journal) ? filesize($journal) : 0;

    app(NotificationsAchat::class)->bonRenvoye($bon, 'ESSAI — transaction annulée', $validateur);

    $apres = DB::table('notifications')->count();
    echo "Notifications en base : {$avant} -> {$apres}\n";

    clearstatcache();
    $tailleApres = file_exists($journal) ? filesize($journal) : 0;
    $delta = $tailleApres - $tailleAvant;
    echo 'Courriel rendu (MAIL_MAILER=log) : '.($delta > 0 ? "OUI, +{$delta} octets" : 'NON')."\n";

    $derniere = DB::table('notifications')->orderByDesc('created_at')->first();

    if ($derniere !== null) {
        $donnees = json_decode($derniere->data, true);
        echo "Titre   : {$donnees['titre']}\n";
        echo "Message : {$donnees['message']}\n";
        echo "URL     : {$donnees['url']}\n";
    }
} finally {
    DB::rollBack();
    echo "\n→ Annulé. Notifications restantes en base : ".DB::table('notifications')->count()."\n";
}
