<?php

namespace Modules\Catalogue\Http\Requests;

/**
 * Règles communes aux deux sens d'écriture d'un contact fournisseur.
 *
 * Mutualisées pour qu'une règle ajoutée à la création ne soit pas oubliée à
 * la modification — c'est par cet écart que des données invalides finissent
 * par entrer en base.
 */
trait ContactFournisseurRules
{
    protected function reglesContact(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'fonction' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'est_principal' => ['nullable', 'boolean'],
            'est_actif' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du contact est obligatoire.',
            'nom.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'prenom.max' => 'Le prénom ne peut pas dépasser 255 caractères.',
            'fonction.max' => 'La fonction ne peut pas dépasser 255 caractères.',
            'telephone.max' => 'Le téléphone ne peut pas dépasser 30 caractères.',
            'email.email' => "L'adresse email n'est pas valide.",
        ];
    }

    /**
     * Les cases à cocher absentes valent « non ».
     *
     * Un navigateur n'envoie rien pour une case décochée : sans cette
     * normalisation, décocher « contact principal » laisserait la valeur
     * inchangée, et l'utilisateur constaterait que son action n'a rien fait.
     */
    protected function normaliserCases(): array
    {
        return [
            'est_principal' => $this->boolean('est_principal'),
            'est_actif' => $this->has('est_actif') ? $this->boolean('est_actif') : true,
        ];
    }
}
