<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Achat\Services\StatistiquesService;

/**
 * Tableau de bord du module (E-01).
 *
 * Correction AN-19 : les méthodes de squelette (create, store, edit, update,
 * destroy) de la version précédente, dont trois renvoyaient vers des vues
 * inexistantes, ont été supprimées.
 */
class AchatController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected StatistiquesService $statistiques) {}

    public function index(): View
    {
        $this->authorize('achat.dashboard.view');

        $parStatutBl = BordereauLivraison::get(['statut'])->countBy('statut');

        return view('achat::index', [
            'indicateurs' => $this->statistiques->indicateurs(),
            'totalBl' => $parStatutBl->sum(),
            'blEnCours' => $parStatutBl->get('brouillon', 0) + $parStatutBl->get('wizard', 0),
            'blValide' => $parStatutBl->get('valide', 0),
            'depensesMensuelles' => $this->statistiques->depensesMensuelles(),
            'depensesParFournisseur' => $this->statistiques->depensesParFournisseur(),

            // Les vues ne requêtent pas la base : tout est préparé ici.
            'dernieresCommandes' => BonCommande::with('fournisseur')->latest()->limit(5)->get(),
            'dernieresLivraisons' => BordereauLivraison::with('bonCommande')->latest()->limit(5)->get(),
            'reliquatsAnciens' => $this->statistiques->reliquatsAnciens()->take(5),
        ]);
    }
}
