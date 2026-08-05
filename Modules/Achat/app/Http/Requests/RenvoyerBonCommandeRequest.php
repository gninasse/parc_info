<?php

namespace Modules\Achat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Achat\Http\Requests\Concerns\MessagesValidationFr;

/**
 * M-06 — renvoi motivé en brouillon.
 *
 * Le motif est obligatoire, et c'est le fond du dispositif : renvoyer un bon
 * sans dire pourquoi oblige l'auteur à deviner ce qu'il doit corriger. Le
 * minimum de longueur écarte les « ok » et « non » qui ne renseignent rien.
 */
class RenvoyerBonCommandeRequest extends FormRequest
{
    use MessagesValidationFr;

    public function authorize(): bool
    {
        return true; // permission achat.bons_commande.valider portée par le contrôleur
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
            'motif.required' => 'Indiquez le motif du renvoi : l\'auteur doit savoir quoi corriger.',
            'motif.min' => 'Le motif doit être un peu plus explicite (5 caractères au minimum).',
        ];
    }

    protected function attributsSpecifiques(): array
    {
        return ['motif' => 'motif du renvoi'];
    }
}
