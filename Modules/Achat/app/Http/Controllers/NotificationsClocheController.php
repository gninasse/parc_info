<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Achat\Notifications\NotificationAchat;

/**
 * D-22 — la cloche de la barre de navigation.
 *
 * Sans cet écran, le canal « database » écrirait dans une table que
 * personne ne lit : la moitié du dispositif de notification serait inerte.
 *
 * Deux partis pris :
 *
 *  1. **On ne lit QUE les notifications du module Achat**, en filtrant sur
 *     la colonne `type` et non sur le JSON `data`. La table `notifications`
 *     est partagée avec le reste de l'application : un filtre JSON serait à
 *     réécrire entre SQLite et PostgreSQL, alors qu'une colonne texte se
 *     compare partout de la même façon.
 *
 *  2. **Lire n'est pas agir.** Marquer une notification comme lue ne vise
 *     pas le bon et ne le fait pas avancer : c'est un geste de rangement.
 *     La permission exigée est donc celle d'utiliser le module, pas celle
 *     de viser.
 */
class NotificationsClocheController extends Controller implements HasMiddleware
{
    /** Ce que la cloche déroule d'un coup : au-delà, on renvoie vers la liste. */
    private const APERCU = 8;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:achat.bons_commande.index'),
        ];
    }

    /** Le contenu du menu déroulant, rafraîchi sans recharger la page. */
    public function index(Request $request): JsonResponse
    {
        $utilisateur = $request->user();

        $notifications = $utilisateur->notifications()
            ->where('type', NotificationAchat::class)
            ->limit(self::APERCU)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'titre' => $notification->data['titre'] ?? '',
                'message' => $notification->data['message'] ?? '',
                'url' => $notification->data['url'] ?? null,
                'lue' => $notification->read_at !== null,
                'depuis' => $notification->created_at?->diffForHumans(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'non_lues' => $this->compterNonLues($request),
        ]);
    }

    /**
     * Marquer une notification lue. On repasse par la relation de
     * l'utilisateur : rien ne garantirait, sinon, que l'identifiant fourni
     * lui appartienne — un identifiant deviné effacerait la notification
     * d'un collègue.
     */
    public function marquerLue(Request $request, string $notification): JsonResponse
    {
        $ligne = $request->user()->notifications()
            ->where('type', NotificationAchat::class)
            ->whereKey($notification)
            ->first();

        abort_if($ligne === null, 404, 'Notification introuvable.');

        $ligne->markAsRead();

        return response()->json([
            'success' => true,
            'non_lues' => $this->compterNonLues($request),
        ]);
    }

    /** « Tout marquer comme lu » : le geste qui vide la pastille. */
    public function marquerToutesLues(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()
            ->where('type', NotificationAchat::class)
            ->update(['read_at' => now()]);

        return response()->json(['success' => true, 'non_lues' => 0]);
    }

    private function compterNonLues(Request $request): int
    {
        return $request->user()->unreadNotifications()
            ->where('type', NotificationAchat::class)
            ->count();
    }
}
