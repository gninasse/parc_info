<?php

namespace Modules\Stock\Http\Requests\Concerns;

/**
 * Messages de validation en français pour les FormRequests du module.
 *
 * L'application tourne en locale « en » sans fichiers de langue : sans ce
 * trait, toute règle dépourvue de message explicite renvoie le texte anglais
 * de Laravel (« The magasin id field is required. »). On couvre donc ici les
 * règles génériques une fois pour toutes, et `attributes()` fournit des noms
 * de champs lisibles — « Le magasin est obligatoire. » plutôt que
 * « The magasin_id field is required. ».
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
            'required_without' => 'Le champ :attribute est obligatoire.',
            'prohibits' => 'Le champ :attribute ne peut pas être combiné avec un autre.',
            'integer' => 'Le champ :attribute doit être un nombre entier.',
            'numeric' => 'Le champ :attribute doit être un nombre.',
            'string' => 'Le champ :attribute doit être du texte.',
            'array' => 'Le champ :attribute est mal formé.',
            'boolean' => 'Le champ :attribute doit être vrai ou faux.',
            'date' => 'Le champ :attribute doit être une date valide.',
            'in' => 'La valeur choisie pour :attribute n\'est pas valide.',
            'exists' => 'La valeur choisie pour :attribute n\'existe pas ou a été supprimée.',
            'unique' => 'Cette valeur de :attribute est déjà utilisée.',
            'different' => 'Le champ :attribute doit être différent.',
            'gt.numeric' => 'Le champ :attribute doit être supérieur à :value.',
            'min.numeric' => 'Le champ :attribute doit valoir au moins :min.',
            'max.numeric' => 'Le champ :attribute ne peut pas dépasser :max.',
            'max.string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
            'max.array' => 'Le champ :attribute ne peut pas contenir plus de :max éléments.',
            'max.file' => 'Le fichier :attribute ne peut pas dépasser :max Ko.',
            'file' => 'Le champ :attribute doit être un fichier.',
            'mimes' => 'Le format de :attribute n\'est pas accepté.',
            'image' => 'Le champ :attribute doit être une image.',
        ];

        return array_merge(
            $generiques,
            method_exists($this, 'messagesSpecifiques') ? $this->messagesSpecifiques() : []
        );
    }

    public function attributes(): array
    {
        $communs = [
            'magasin_id' => 'magasin',
            'magasin_source_id' => 'magasin source',
            'magasin_cible_id' => 'magasin cible',
            'date_document' => 'date du bon',
            'nature' => 'nature',
            'fournisseur_id' => 'fournisseur',
            'reference_externe' => 'référence externe',
            'observation_type' => 'type d\'observation',
            'observation' => 'observation',
            'motif_type' => 'motif',
            'motif_texte' => 'texte du motif',
            'remise_reelle_le' => 'date et heure réelles de remise',
            'beneficiaire_type' => 'bénéficiaire',
            'beneficiaire_direction_id' => 'direction bénéficiaire',
            'beneficiaire_service_id' => 'service bénéficiaire',
            'beneficiaire_unite_id' => 'unité bénéficiaire',
            'beneficiaire_poste_id' => 'poste bénéficiaire',
            'beneficiaire_local_id' => 'local bénéficiaire',
            'beneficiaire_employe_id' => 'employé bénéficiaire',
            'remis_a_nom' => 'nom de la personne à qui le bon est remis',
            'remis_a_employe_id' => 'employé à qui le bon est remis',
            'transporte_par_nom' => 'nom du transporteur',
            'transporte_par_employe_id' => 'employé transporteur',
            'lignes' => 'lignes du bon',
            'lignes.*.article_id' => 'article de la ligne',
            'lignes.*.equipement_id' => 'unité de la ligne',
            'lignes.*.quantite' => 'quantité',
            'lignes.*.cout_unitaire' => 'coût unitaire',
            'lignes.*.emplacement_local_id' => 'emplacement',
            'libelle' => 'libellé',
            'site_id' => 'site',
            'local_id' => 'local',
            'responsable_id' => 'responsable',
            'seuil' => 'seuil',
            'article_id' => 'article',
            'fichiers' => 'pièces jointes',
            'fichiers.*' => 'fichier',
            'numero_serie' => 'numéro de série',
            'etat' => 'état',
        ];

        return array_merge(
            $communs,
            method_exists($this, 'attributsSpecifiques') ? $this->attributsSpecifiques() : []
        );
    }
}
