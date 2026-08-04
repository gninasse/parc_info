<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Stock\Http\Requests\Concerns\ValideLignesTransfert;

class UpdateTransfertRequest extends FormRequest
{
    use ValideLignesTransfert;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->reglesCommunes();
    }
}
