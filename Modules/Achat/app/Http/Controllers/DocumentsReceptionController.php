<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Services\DocumentsReception;

/**
 * BR-03 — le PROXY documentaire : Achat sert les pièces de réception de ses
 * propres commandes.
 *
 * Le but est explicite dans le recueil : « un profil Achat sans AUCUN rôle
 * Stock consulte les bordereaux de SES commandes ». L'acheteur qui conteste
 * une facture doit pouvoir sortir le BL signé sans demander un accès magasin.
 *
 * Le danger l'est tout autant. Cette route lit des fichiers d'un autre module
 * et pourrait devenir une porte dérobée. Trois verrous, dans cet ordre :
 *
 *   1. `achat.documents.view` (middleware) — le droit de lire des pièces ;
 *   2. le bon de commande existe et l'utilisateur peut ouvrir sa fiche ;
 *   3. IA-16 — la pièce APPARTIENT à une entrée liée à CE bon. Sans ce
 *      troisième verrou, un identifiant deviné donnerait accès à n'importe
 *      quel document du magasin : c'est le contrôle qui compte vraiment.
 *
 * Le fichier est relayé en flux depuis le disque privé de Stock. Aucune URL
 * Stock n'est jamais rendue à l'utilisateur, et aucune permission Stock n'est
 * exigée : c'est bien un proxy, pas une redirection.
 */
class DocumentsReceptionController extends Controller implements HasMiddleware
{
    /** Le disque privé de Stock (Modules\Stock\Models\Document::DISQUE). */
    private const DISQUE_STOCK = 'local';

    public function __construct(private readonly DocumentsReception $service) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:achat.bons_commande.index'),
            new Middleware('permission:achat.documents.view'),
        ];
    }

    /** Une pièce jointe d'une entrée liée (BL fournisseur, photo…). */
    public function telecharger(int $id, int $entree, int $document)
    {
        $bon = BonCommande::query()->findOrFail($id);

        $piece = $this->service->pieceDuBon($bon, $entree, $document);

        // 404 et non 403 : pour l'acheteur, une pièce qui n'est pas au dossier
        // de SA commande n'existe pas — et nous n'apprenons rien à qui sonde.
        abort_if($piece === null, 404, 'Cette pièce n\'appartient pas au dossier de ce bon de commande.');

        // Pierre tombale : la pièce a existé, elle a été retirée (motivée).
        abort_if((bool) $piece->est_supprime, 410, 'Cette pièce a été supprimée du dossier de réception.');

        abort_unless(
            $piece->chemin !== null && Storage::disk(self::DISQUE_STOCK)->exists($piece->chemin),
            404,
            'Fichier introuvable sur le disque.'
        );

        return Storage::disk(self::DISQUE_STOCK)->download($piece->chemin, $piece->nom_original, [
            'Content-Type' => $piece->mime,
        ]);
    }

    /**
     * Le bordereau de réception (BR-02) régénéré pour l'acheteur.
     *
     * Il est REGÉNÉRÉ, pas stocké : le PDF reflète toujours l'état courant de
     * l'entrée (contre-passations comprises, avec leur filigrane), et il n'y a
     * pas de fichier obsolète à faire circuler.
     */
    public function bordereau(Request $request, int $id, int $entree)
    {
        $bon = BonCommande::query()->findOrFail($id);

        $ligne = $this->service->entreeValideeDuBon($bon, $entree);

        abort_if($ligne === null, 404, 'Aucune réception validée de ce bon ne porte ce numéro.');

        // La génération appartient à Stock : Achat ne réimplémente pas le
        // gabarit (deux bordereaux divergents seraient pires que pas de
        // bordereau du tout). Si le module est absent, la fiche le dit.
        abort_unless(
            class_exists(\Modules\Stock\Http\Controllers\BordereauReceptionController::class),
            503,
            'Le module Stock est indisponible : le bordereau ne peut pas être édité.'
        );

        return app(\Modules\Stock\Http\Controllers\BordereauReceptionController::class)
            ->rendre((int) $ligne->id);
    }
}
