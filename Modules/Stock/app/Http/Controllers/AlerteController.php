<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Stock\Http\Controllers\Concerns\RepondEnJson;

/**
 * Alertes et notifications in-app du module Stock (F8).
 */
class AlerteController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    public function index(): View
    {
        $this->authorize('stock.dashboard.view');

        return view('stock::alertes.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.dashboard.view');

        $notifications = $request->user()
            ->notifications()
            ->limit(100)
            ->get()
            ->filter(fn ($notification) => str_starts_with($notification->type, 'Modules\\Stock'))
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'titre' => $notification->data['titre'] ?? '',
                'message' => $notification->data['message'] ?? '',
                'niveau' => $notification->data['niveau'] ?? 'INFO',
                'url' => $notification->data['url'] ?? null,
                'lue' => $notification->read_at !== null,
                'date' => $notification->created_at->format('d/m/Y H:i'),
            ])
            ->values();

        return $this->table($notifications->count(), $notifications);
    }

    /** Badge de la navbar. */
    public function count(Request $request): JsonResponse
    {
        $this->authorize('stock.dashboard.view');

        $count = $request->user()
            ->unreadNotifications()
            ->where('type', 'like', 'Modules\\Stock%')
            ->count();

        return $this->donnees(['non_lues' => $count]);
    }

    public function lire(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if (! $notification) {
            return $this->echec('Notification introuvable.', 404);
        }

        $notification->markAsRead();

        return $this->succes('Notification marquée comme lue.');
    }

    public function lireTout(Request $request): JsonResponse
    {
        $request->user()
            ->unreadNotifications()
            ->where('type', 'like', 'Modules\\Stock%')
            ->update(['read_at' => now()]);

        return $this->succes('Toutes les notifications ont été marquées comme lues.');
    }
}
