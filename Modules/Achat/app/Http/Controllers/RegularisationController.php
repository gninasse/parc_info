<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Achat\Exceptions\AchatException;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Services\AchatParametres;
use Modules\Achat\Services\RegularisationService;

/**
 * D-15 — régularisation de l'intérim : rattachements M-09, extinction et
 * réactivation de la porte (A15).
 *
 * Deux permissions distinctes, et c'est délibéré : `regulariser` documente
 * la dette (rattacher des équipements) ; `administration.manage` rouvre la
 * porte après extinction (SW-06). Rouvrir n'est pas documenter.
 */
class RegularisationController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly RegularisationService $regularisation,
        private readonly AchatParametres $parametres,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:achat.bons_commande.regulariser', only: [
                'candidats', 'rattacher', 'detacher', 'etat',
            ]),
            new Middleware('permission:achat.administration.manage', only: ['reactiver']),
        ];
    }

    /** L'état de la porte et de la dette — bandeau A-08 et écran M-09. */
    public function etat(): JsonResponse
    {
        return response()->json([
            'active' => $this->parametres->regularisationActive(),
            'dette_restante' => $this->regularisation->detteRestante(),
            'intermede' => [
                'debut' => $this->parametres->intermedeDebut()?->toDateString(),
                'fin' => $this->parametres->intermedeFin()?->toDateString(),
            ],
        ]);
    }

    /** M-09 — les équipements SANS commande d'origine. */
    public function candidats(Request $request): JsonResponse
    {
        $candidats = $this->regularisation->equipementsCandidats(
            $request->input('q'),
            max(1, min(200, (int) $request->input('limit', 50)))
        );

        return response()->json([
            'data' => $candidats->map(fn ($equipement) => [
                'id' => $equipement->id,
                'code_inventaire' => $equipement->code_inventaire,
                'numero_serie' => $equipement->numero_serie,
                'modele' => $equipement->modele,
                'date_acquisition' => $equipement->date_acquisition?->format('d/m/Y'),
                'statut' => $equipement->statut,
            ])->all(),
            'dette_restante' => $this->regularisation->detteRestante(),
        ]);
    }

    /** M-09 — rattachement multiple, avec décrément de la dette (UX-17). */
    public function rattacher(Request $request, int $id): JsonResponse
    {
        $bon = BonCommande::query()->findOrFail($id);

        $valide = $request->validate([
            'equipements' => ['required', 'array', 'min:1'],
            'equipements.*' => ['integer'],
        ], [
            'equipements.required' => 'Sélectionnez au moins un équipement à rattacher.',
        ]);

        try {
            $resultat = $this->regularisation->rattacher($bon, $valide['equipements'], $request->user());
        } catch (AchatException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }

        return response()->json([
            'success' => true,
            'message' => $resultat['eteinte']
                // L'extinction est une bonne nouvelle : elle se dit.
                ? "{$resultat['rattaches']} équipement(s) rattaché(s) — la dette de l'intérim est soldée, le mode régularisation se referme."
                : "{$resultat['rattaches']} équipement(s) rattaché(s).",
            'data' => $resultat,
        ]);
    }

    public function detacher(Request $request, int $id, int $equipementId): JsonResponse
    {
        $bon = BonCommande::query()->findOrFail($id);

        $dette = $this->regularisation->detacher($bon, $equipementId, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Équipement détaché.',
            'data' => ['dette_restante' => $dette],
        ]);
    }

    /** SW-06 — réactivation motivée de la porte (administrateur). */
    public function reactiver(Request $request): JsonResponse
    {
        $valide = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'motif.required' => 'Indiquez pourquoi la régularisation doit rouvrir.',
            'motif.min' => 'Le motif doit être un peu plus explicite (5 caractères au minimum).',
        ]);

        $this->regularisation->reactiver($request->user(), $valide['motif']);

        return response()->json([
            'success' => true,
            'message' => 'Mode régularisation réactivé — le geste est tracé au journal.',
        ]);
    }
}
