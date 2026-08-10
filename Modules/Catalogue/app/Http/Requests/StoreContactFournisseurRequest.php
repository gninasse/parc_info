<?php

namespace Modules\Catalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactFournisseurRequest extends FormRequest
{
    use ContactFournisseurRules;

    public function authorize(): bool
    {
        return $this->user()->can('catalogue.contacts.store');
    }

    public function rules(): array
    {
        return $this->reglesContact();
    }

    public function validated($key = null, $default = null): array
    {
        return array_merge(parent::validated(), $this->normaliserCases());
    }
}
