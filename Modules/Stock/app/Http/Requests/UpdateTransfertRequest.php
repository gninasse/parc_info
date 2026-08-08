<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Stock\Http\Requests\Concerns\MessagesValidationFr;
use Modules\Stock\Http\Requests\Concerns\ValideLignesTransfert;

class UpdateTransfertRequest extends FormRequest
{
    use MessagesValidationFr, ValideLignesTransfert;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->reglesCommunes();
    }
}
