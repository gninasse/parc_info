<?php

namespace Modules\Achat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Achat\Http\Requests\Concerns\MessagesValidationFr;

/**
 * M-07 (annulation) et M-03 (clôture du reliquat) — le motif obligatoire.
 *
 * Même doctrine que le renvoi M-06 : un geste définitif sur un document
 * officiel s'explique par écrit, et le minimum de longueur écarte les « ok »
 * qui ne renseignent rien. Le libellé de l'attribut est fourni par la route.
 */
class MotifRequest extends FormRequest
{
    use MessagesValidationFr;

    public function authorize(): bool
    {
        return true; // permission portée par le contrôleur (annuler / cloturer)
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    protected function messagesSpecifiques(): array
    {
        return [
            'motif.required' => 'Indiquez le motif : un geste définitif s\'explique par écrit.',
            'motif.min' => 'Le motif doit être un peu plus explicite (5 caractères au minimum).',
        ];
    }

    protected function attributsSpecifiques(): array
    {
        return ['motif' => 'motif'];
    }
}
