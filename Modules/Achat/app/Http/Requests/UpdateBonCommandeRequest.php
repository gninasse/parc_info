<?php

namespace Modules\Achat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Achat\Http\Requests\Concerns\MessagesValidationFr;
use Modules\Achat\Http\Requests\Concerns\ValideBrouillonBonCommande;

class UpdateBonCommandeRequest extends FormRequest
{
    use MessagesValidationFr, ValideBrouillonBonCommande;

    public function authorize(): bool
    {
        return true; // permissions portées par le middleware du contrôleur
    }

    public function rules(): array
    {
        return array_merge($this->reglesCommunes(), [
            /*
             * Verrou optimiste (SPEC_UX §0.5 et §15.2). Le client renvoie la
             * version qu'il a chargée ; si elle ne correspond plus, le
             * contrôleur répond 409 plutôt que d'écraser silencieusement le
             * travail d'un autre onglet ou d'un collègue. Facultatif pour ne
             * pas casser un appel programmatique, mais l'écran l'envoie
             * toujours.
             */
            'updated_at' => ['nullable', 'date'],
        ]);
    }

    public function withValidator(\Illuminate\Validation\Validator $validateur): void
    {
        $validateur->after(fn () => $this->validerBornesRegularisation($validateur));
    }
}
