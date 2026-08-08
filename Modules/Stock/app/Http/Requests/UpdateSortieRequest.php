<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Stock\Http\Requests\Concerns\MessagesValidationFr;
use Modules\Stock\Http\Requests\Concerns\ValideLignesSortie;

class UpdateSortieRequest extends FormRequest
{
    use MessagesValidationFr, ValideLignesSortie;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->reglesCommunes();
    }
}
