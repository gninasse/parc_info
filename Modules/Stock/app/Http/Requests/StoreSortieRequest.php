<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSortieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.sorties.create');
    }

    public function rules(): array
    {
        return [
            'magasin_id' => ['required', 'exists:stock_magasins,id'],
            'article_id' => ['required', 'exists:achat_articles,id'],
            'quantite' => ['required', 'integer', 'min:1'],
            'type_affectation_parcinfo' => ['required', 'in:EQUIPEMENT,CONSOMMABLE,LICENCE'],
            'type_cible' => ['required', 'in:EMPLOYE,SERVICE,DIRECTION,UNITE,POSTE'],
            'cible_id' => ['required', 'integer'],
            'equipement_id' => ['required_if:type_affectation_parcinfo,EQUIPEMENT', 'nullable', 'exists:parc_info_equipements,id'],
            'licence_id' => ['required_if:type_affectation_parcinfo,LICENCE', 'nullable', 'exists:parc_info_licences,id'],
            'motif' => ['required', 'string', 'max:500'],
            'reference_document' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'equipement_id.required_if' => 'L\'équipement physique est requis pour une affectation de type Équipement.',
            'licence_id.required_if' => 'La licence logicielle est requise pour une affectation de type Licence.',
        ];
    }
}
