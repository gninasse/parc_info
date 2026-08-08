<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Stock\Http\Requests\Concerns\MessagesValidationFr;
use Modules\Stock\Http\Requests\Concerns\ValideLignesEntree;

class UpdateEntreeRequest extends FormRequest
{
    use MessagesValidationFr, ValideLignesEntree;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->reglesCommunes();
    }
}
