<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

abstract class BaseDictionnaireRequest extends FormRequest
{
    abstract protected function getDictionnaireCode(): string;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $code = $this->getDictionnaireCode();
        $dictId = DB::table('parc_info_dictionnaires')->where('code', $code)->value('id');

        $uniqueRule = Rule::unique('parc_info_dictionnaire_valeurs', 'valeur')
            ->where('dictionnaire_id', $dictId);

        if ($this->route('id')) {
            $uniqueRule->ignore($this->route('id'));
        }

        return [
            'libelle' => [
                'required',
                'string',
                'max:255',
                $uniqueRule,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire',
            'libelle.unique' => 'Ce libellé existe déjà',
        ];
    }
}
