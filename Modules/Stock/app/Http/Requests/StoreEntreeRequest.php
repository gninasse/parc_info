<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Stock\Http\Requests\Concerns\ValideLignesEntree;

class StoreEntreeRequest extends FormRequest
{
    use ValideLignesEntree;

    public function authorize(): bool
    {
        return true; // permissions portées par le middleware du contrôleur
    }

    public function rules(): array
    {
        return $this->reglesCommunes();
    }
}
