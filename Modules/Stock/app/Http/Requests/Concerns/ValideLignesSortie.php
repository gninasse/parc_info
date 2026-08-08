<?php

namespace Modules\Stock\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Models\Magasin;

/**
 * Contrôles de FORME du brouillon de sortie (UX §4.2) — le disponible et le
 * pointage se re-contrôlent à la validation (I15/I17). Lignes : toujours un
 * article_id (quantitatif ou « modèle × N » — les unités vivent au tampon).
 */
trait ValideLignesSortie
{
    protected function reglesCommunes(): array
    {
        return [
            'magasin_id' => [
                'required', 'integer',
                Rule::exists('stock_magasins', 'id'),
                function (string $attribute, $value, \Closure $fail) {
                    if (! Magasin::query()->where('id', $value)->where('est_actif', true)->exists()) {
                        $fail('Ce magasin est désactivé : aucune sortie possible.');
                    }
                },
            ],
            'date_document' => ['required', 'date'],

            // Motif typé de config ; « autre » déplie le texte requis ;
            // « urgence_hors_ouverture » exige la date/heure réelle de remise
            'motif_type' => ['required', Rule::in(array_keys(config('stock.motifs_sortie', [])))],
            'motif_texte' => ['nullable', 'string', 'required_if:motif_type,autre'],
            'remise_reelle_le' => ['nullable', 'date', 'required_if:motif_type,urgence_hors_ouverture'],

            // Bénéficiaire OBLIGATOIRE (D5) : type + la FK correspondante
            'beneficiaire_type' => ['required', Rule::in(['direction', 'service', 'unite', 'poste', 'local', 'employe'])],
            'beneficiaire_direction_id' => ['nullable', 'integer', Rule::exists('organisation_directions', 'id'), 'required_if:beneficiaire_type,direction'],
            'beneficiaire_service_id' => ['nullable', 'integer', Rule::exists('organisation_services', 'id'), 'required_if:beneficiaire_type,service'],
            'beneficiaire_unite_id' => ['nullable', 'integer', Rule::exists('organisation_unites', 'id'), 'required_if:beneficiaire_type,unite'],
            'beneficiaire_poste_id' => ['nullable', 'integer', Rule::exists('organisation_postes_travail', 'id'), 'required_if:beneficiaire_type,poste'],
            'beneficiaire_local_id' => ['nullable', 'integer', Rule::exists('organisation_locaux', 'id'), 'required_if:beneficiaire_type,local'],
            'beneficiaire_employe_id' => ['nullable', 'integer', Rule::exists('grh_dossiers_employes', 'id'), 'required_if:beneficiaire_type,employe'],

            // « Remis à » — requis À LA VALIDATION si équipements (pas ici)
            'remis_a_nom' => ['nullable', 'string', 'max:255'],
            'remis_a_employe_id' => ['nullable', 'integer', Rule::exists('grh_dossiers_employes', 'id')],
            'observation' => ['nullable', 'string'],

            'lignes' => ['array'],
            'lignes.*.article_id' => [
                'required', 'integer',
                function (string $attribute, $value, \Closure $fail) {
                    $article = Article::query()->find($value);

                    if ($article === null) {
                        $fail('Article inconnu au catalogue.');
                    } elseif (! $article->est_stockable) {
                        $fail("L'article « {$article->nom} » n'est pas stockable.");
                    }
                },
            ],
            'lignes.*.quantite' => ['required', 'numeric', 'gt:0'],
            'lignes.*.emplacement_local_id' => ['nullable', 'integer', Rule::exists('organisation_locaux', 'id')],
        ];
    }

    protected function messagesSpecifiques(): array
    {
        return [
            'motif_type.required' => 'Le motif de sortie est obligatoire.',
            'motif_texte.required_if' => 'Le texte du motif est requis pour « Autre ».',
            'remise_reelle_le.required_if' => 'Indiquez quand la remise a réellement eu lieu (urgence hors ouverture).',
            'beneficiaire_type.required' => 'Le bénéficiaire est obligatoire.',
            'lignes.*.quantite.gt' => 'La quantité doit être strictement positive.',
        ];
    }
}
