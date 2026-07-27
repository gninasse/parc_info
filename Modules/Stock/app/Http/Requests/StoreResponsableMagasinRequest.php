<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResponsableMagasinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.magasins.admin');
    }

    public function rules(): array
    {
        return [
            // L'existence dans GRH est contrôlée par le service via le contrat.
            'employe_id' => ['required', 'integer'],
            'role' => ['required', Rule::in(['principal', 'adjoint'])],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after:date_debut'],
        ];
    }
}
