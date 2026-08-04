<?php

namespace Modules\Stock\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Stock\Exceptions\StockException;
use Modules\Stock\Services\PointageService;

/**
 * Handlers HTTP du pointage, partagés SortieController/TransfertController
 * (exigence « zéro duplication » — D14/D17). La classe hôte définit
 * MESSAGE_VERROUILLAGE.
 */
trait GerePointageHttp
{
    /** {action: pointer|depointer, ligne_id|tampon_id, equipement_id} */
    protected function traiterPointageUpdate($document, Request $request, PointageService $pointageService): JsonResponse
    {
        if ($document->statut !== 'POINTAGE') {
            return $this->refusVerrouillage($document);
        }

        $valide = $request->validate([
            'action' => ['required', 'in:pointer,depointer'],
            'ligne_id' => ['required_if:action,pointer', 'integer'],
            'equipement_id' => ['required_if:action,pointer', 'integer'],
            'tampon_id' => ['required_if:action,depointer', 'integer'],
        ]);

        try {
            if ($valide['action'] === 'pointer') {
                $ligne = $document->lignes()->with('article')->findOrFail((int) $valide['ligne_id']);
                $pointageService->pointer($document, $ligne, (int) $valide['equipement_id']);
            } else {
                $pointageService->depointer($document, (int) $valide['tampon_id']);
            }
        } catch (StockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }

        [$pointees, $attendues] = $pointageService->progression($document);

        return response()->json([
            'success' => true,
            'statut_pointage' => ['pointees' => $pointees, 'attendues' => $attendues],
        ]);
    }

    /** Scan express (brouillon — D17). */
    protected function traiterScanExpress($document, Request $request, PointageService $pointageService): JsonResponse
    {
        if ($document->statut !== 'BROUILLON') {
            return $this->refusVerrouillage($document);
        }

        $valide = $request->validate(['numero_serie' => ['required', 'string', 'max:255']]);

        try {
            $resultat = $pointageService->scanExpress($document, $valide['numero_serie']);
        } catch (StockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }

        return response()->json([
            'success' => true,
            'message' => trim(($resultat['ligne']->article->nom ?? '').' — '.($resultat['equipement']['numero_serie'] ?? '').' ajouté'),
            'data' => $resultat,
        ]);
    }

    protected function refusVerrouillage($document): JsonResponse
    {
        $message = $document->verrouille()
            ? static::MESSAGE_VERROUILLAGE
            : 'Bon validé — non modifiable. Corrigez par contre-mouvement depuis l\'historique.';

        return response()->json(['success' => false, 'message' => $message], 409);
    }
}
