<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Achat\Services\NotificationsAchat;

/**
 * D-22 — les préférences de notification, écran de l'UTILISATEUR.
 *
 * Elles ne relèvent PAS de l'administration du module : chacun règle ce
 * qu'il reçoit, personne ne le règle à sa place. Exiger
 * `achat.administration.manage` ici obligerait un acheteur noyé de courriels
 * à demander l'intervention d'un administrateur — et il finirait par créer
 * une règle de filtrage dans sa messagerie, ce qui perdrait l'information
 * pour de bon.
 *
 * La seule permission exigée est celle d'utiliser le module (`index`) : si
 * on peut voir les bons, on peut décider si on veut en être averti.
 */
class PreferencesNotificationController extends Controller implements HasMiddleware
{
    public function __construct(private readonly NotificationsAchat $notifications) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:achat.bons_commande.index'),
        ];
    }

    /** L'écran : un interrupteur par type, deux canaux. */
    public function index(Request $request)
    {
        $utilisateur = $request->user();

        $preferences = collect(NotificationsAchat::TYPES)
            ->map(fn (string $libelle, string $type) => [
                'type' => $type,
                'libelle' => $libelle,
                ...$this->notifications->preference($utilisateur, $type),
            ])
            ->values();

        return view('achat::preferences.index', [
            'preferences' => $preferences,
            // Ce que l'utilisateur reçoit dépend AUSSI de ses permissions : un
            // acheteur sans droit de visa ne recevra jamais « bon à viser »,
            // même en laissant l'interrupteur ouvert. L'écran le dit.
            'peutViser' => $utilisateur->can('achat.bons_commande.valider'),
        ]);
    }

    /** Une carte = un PATCH, comme l'écran d'administration (D-17). */
    public function modifier(Request $request, string $type): JsonResponse
    {
        $valide = $request->validate([
            'par_mail' => ['required', 'boolean'],
            'par_cloche' => ['required', 'boolean'],
        ]);

        abort_unless(
            array_key_exists($type, NotificationsAchat::TYPES),
            404,
            'Type de notification inconnu.'
        );

        $this->notifications->definirPreference(
            $request->user(),
            $type,
            (bool) $valide['par_mail'],
            (bool) $valide['par_cloche'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Préférence enregistrée.',
        ]);
    }
}
