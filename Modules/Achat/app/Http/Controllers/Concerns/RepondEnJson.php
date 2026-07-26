<?php

namespace Modules\Achat\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Modules\Achat\Exceptions\RegleMetierException;
use Throwable;

/**
 * Réponses JSON normalisées (PATTERNS §5).
 *
 * ENF-FIA-06 — Une opération qui n'aboutit pas ne renvoie jamais success:true.
 * La version précédente signalait en succès la désactivation d'un article dont
 * la suppression avait été refusée (AN-11).
 */
trait RepondEnJson
{
    protected function succes(string $message, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
        ], $extra));
    }

    protected function donnees(mixed $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    protected function echec(string $message, int $statut = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statut);
    }

    /**
     * Exécute une opération métier et normalise le résultat.
     *
     * Une RegleMetierException produit un 422 porteur du message métier ;
     * toute autre exception produit un 500 au message neutre, le détail
     * partant dans les journaux.
     */
    protected function executer(callable $operation): JsonResponse
    {
        try {
            return $operation();
        } catch (RegleMetierException $e) {
            return $this->echec($e->getMessage(), $e->statut());
        } catch (Throwable $e) {
            report($e);

            return $this->echec(
                "Une erreur technique est survenue. L'opération a été annulée.",
                500
            );
        }
    }

    /**
     * Enveloppe une réponse Bootstrap Table (PATTERNS §5).
     */
    protected function table(int $total, iterable $rows): JsonResponse
    {
        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }
}
