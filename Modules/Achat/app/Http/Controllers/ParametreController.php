<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Achat\Exceptions\RegleMetierException;
use Modules\Achat\Http\Controllers\Concerns\RepondEnJson;
use Modules\Achat\Http\Requests\UpdateParametreRequest;
use Modules\Achat\Models\Parametre;

/**
 * Administration du paramétrage du module (EF-ADM-01 à EF-ADM-03).
 */
class ParametreController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    public function index(): View
    {
        $this->authorize('achat.parametres.view');

        return view('achat::parametres.index');
    }

    public function getData(): JsonResponse
    {
        $this->authorize('achat.parametres.view');

        $parametres = Parametre::orderBy('libelle')->with('updater')->get()
            ->map(fn (Parametre $parametre) => [
                'id' => $parametre->id,
                'cle' => $parametre->cle,
                'libelle' => $parametre->libelle,
                'description' => $parametre->description,
                'valeur' => $parametre->valeur,
                'type_valeur' => $parametre->type_valeur,
                'modifiable' => $parametre->modifiable,
                'updated_at' => $parametre->updated_at?->toDateTimeString(),
                'updater' => $parametre->updater?->name,
            ]);

        return $this->table($parametres->count(), $parametres);
    }

    public function update(UpdateParametreRequest $request, Parametre $parametre): JsonResponse
    {
        return $this->executer(function () use ($request, $parametre) {
            // EF-ADM-03 — certains paramètres structurels sont verrouillés.
            if (! $parametre->modifiable) {
                throw new RegleMetierException('Ce paramètre est verrouillé et ne peut pas être modifié.');
            }

            $valeur = trim($request->validated()['valeur']);
            $this->validerSelonType($parametre, $valeur);

            $parametre->update([
                'valeur' => $valeur,
                'updated_by' => $request->user()->id,
            ]);

            activity()->performedOn($parametre)
                ->log("Paramètre {$parametre->cle} modifié : {$valeur}");

            return $this->succes("Le paramètre « {$parametre->libelle} » a été mis à jour.");
        });
    }

    /** @throws RegleMetierException */
    protected function validerSelonType(Parametre $parametre, string $valeur): void
    {
        $valide = match ($parametre->type_valeur) {
            'entier' => filter_var($valeur, FILTER_VALIDATE_INT) !== false && (int) $valeur >= 0,
            'decimal' => is_numeric($valeur) && (float) $valeur >= 0,
            'booleen' => in_array($valeur, ['0', '1', 'true', 'false'], true),
            default => $valeur !== '',
        };

        if (! $valide) {
            throw new RegleMetierException(
                "La valeur « {$valeur} » n'est pas valide pour un paramètre de type {$parametre->type_valeur}."
            );
        }
    }
}
