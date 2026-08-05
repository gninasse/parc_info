<?php

namespace Modules\Achat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Achat\Http\Requests\Concerns\MessagesValidationFr;
use Modules\Achat\Http\Requests\Concerns\ValideBrouillonBonCommande;

class StoreBonCommandeRequest extends FormRequest
{
    use MessagesValidationFr, ValideBrouillonBonCommande;

    public function authorize(): bool
    {
        return true; // permissions portées par le middleware du contrôleur
    }

    public function rules(): array
    {
        return $this->reglesCommunes();
    }

    public function withValidator(\Illuminate\Validation\Validator $validateur): void
    {
        $validateur->after(fn () => $this->validerBornesRegularisation($validateur));
    }
}
