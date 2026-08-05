<?php

namespace Modules\Achat\Http\Requests\Concerns;

/**
 * Messages de validation en français pour les FormRequests du module Achat.
 *
 * Même dispositif que `Modules\Stock\...\MessagesValidationFr` : l'application
 * tourne en locale « en » sans fichiers de langue, donc toute règle sans
 * message explicite renverrait le texte anglais de Laravel (« The
 * fournisseur id field is required. »). Le module Achat a son propre trait
 * plutôt que d'emprunter celui du Stock : un module ne dépend pas des classes
 * internes d'un autre, seulement de ses modèles et de son API.
 *
 * Une requête précise ses cas particuliers via `messagesSpecifiques()` et
 * `attributsSpecifiques()`.
 */
trait MessagesValidationFr
{
    public function messages(): array
    {
        $generiques = [
            'required' => 'Le champ :attribute est obligatoire.',
            'required_if' => 'Le champ :attribute est obligatoire dans ce cas.',
            'required_with' => 'Le champ :attribute est obligatoire.',
            'integer' => 'Le champ :attribute doit être un nombre entier.',
            'numeric' => 'Le champ :attribute doit être un nombre.',
            'string' => 'Le champ :attribute doit être du texte.',
            'array' => 'Le champ :attribute est mal formé.',
            'boolean' => 'Le champ :attribute doit être vrai ou faux.',
            'date' => 'Le champ :attribute doit être une date valide.',
            'in' => 'La valeur choisie pour :attribute n\'est pas valide.',
            'exists' => 'La valeur choisie pour :attribute n\'existe pas ou a été supprimée.',
            'gt.numeric' => 'Le champ :attribute doit être supérieur à :value.',
            'min.numeric' => 'Le champ :attribute doit valoir au moins :min.',
            'max.numeric' => 'Le champ :attribute ne peut pas dépasser :max.',
            'max.string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
        ];

        return array_merge(
            $generiques,
            method_exists($this, 'messagesSpecifiques') ? $this->messagesSpecifiques() : []
        );
    }

    public function attributes(): array
    {
        $communs = [
            'fournisseur_id' => 'fournisseur',
            'date_document' => 'date du bon',
            'est_regularisation' => 'régularisation',
            'service_demandeur_id' => 'service demandeur',
            'reference_demande' => 'référence de la demande',
            'observation_type' => 'motif',
            'observation_texte' => 'texte du motif',
            'lignes' => 'lignes du bon',
            'lignes.*.article_id' => 'article de la ligne',
            'lignes.*.quantite' => 'quantité',
            'lignes.*.prix_unitaire_ht' => 'prix négocié',
            'lignes.*.taux_tva' => 'taux de TVA',
            'updated_at' => 'version du brouillon',
        ];

        return array_merge(
            $communs,
            method_exists($this, 'attributsSpecifiques') ? $this->attributsSpecifiques() : []
        );
    }
}
