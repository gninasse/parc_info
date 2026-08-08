<?php

namespace Modules\Stock\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Modules\Catalogue\Models\Article;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\Magasin;

/**
 * Contrôles de FORME du brouillon d'entrée (UX §3.2) — la complétude
 * (tampon, disponible…) appartient à la validation (ValiderEntreeService).
 *
 * Lignes : {article_id XOR equipement_id, quantite, cout_unitaire?}.
 * article_id = quantitatif ou « modèle × N » ; equipement_id = rattachement
 * d'une unité existante « en stock » non rattachée.
 */
trait ValideLignesEntree
{
    protected function reglesCommunes(): array
    {
        return [
            'magasin_id' => [
                'required', 'integer',
                Rule::exists('stock_magasins', 'id'),
                function (string $attribute, $value, \Closure $fail) {
                    if (! Magasin::query()->where('id', $value)->where('est_actif', true)->exists()) {
                        $fail('Ce magasin est désactivé : aucune réception possible.');
                    }
                },
            ],
            'date_document' => ['required', 'date'],
            'nature' => ['required', Rule::in(['livraison', 'retour'])],
            'fournisseur_id' => ['nullable', 'integer', Rule::exists('catalogue_fournisseurs', 'id')],
            'reference_externe' => ['nullable', 'string', 'max:255'],
            'observation_type' => ['nullable', Rule::in(array_keys(config('stock.motifs_observation_entree', [])))],
            'observation' => ['nullable', 'string', 'required_if:observation_type,autre'],

            // Bénéficiaire d'origine — retours uniquement, optionnel
            'beneficiaire_type' => ['nullable', 'required_with:beneficiaire_direction_id,beneficiaire_service_id,beneficiaire_unite_id,beneficiaire_poste_id,beneficiaire_local_id,beneficiaire_employe_id', Rule::in(['direction', 'service', 'unite', 'poste', 'local', 'employe'])],
            'beneficiaire_direction_id' => ['nullable', 'integer', Rule::exists('organisation_directions', 'id')],
            'beneficiaire_service_id' => ['nullable', 'integer', Rule::exists('organisation_services', 'id')],
            'beneficiaire_unite_id' => ['nullable', 'integer', Rule::exists('organisation_unites', 'id')],
            'beneficiaire_poste_id' => ['nullable', 'integer', Rule::exists('organisation_postes_travail', 'id')],
            'beneficiaire_local_id' => ['nullable', 'integer', Rule::exists('organisation_locaux', 'id')],
            'beneficiaire_employe_id' => ['nullable', 'integer', Rule::exists('grh_dossiers_employes', 'id')],

            'lignes' => ['array'],
            'lignes.*.article_id' => [
                'nullable', 'integer', 'required_without:lignes.*.equipement_id', 'prohibits:lignes.*.equipement_id',
                function (string $attribute, $value, \Closure $fail) {
                    if ($value === null) {
                        return;
                    }

                    $article = Article::query()->find($value);

                    if ($article === null) {
                        $fail('Article inconnu au catalogue — pas de création implicite (D7).');
                    } elseif (! $article->est_actif) {
                        $fail("L'article « {$article->nom} » est désactivé au catalogue : plus aucune entrée possible.");
                    } elseif (! $article->est_stockable) {
                        $fail("L'article « {$article->nom} » n'est pas stockable.");
                    }
                },
            ],
            'lignes.*.equipement_id' => [
                'nullable', 'integer',
                function (string $attribute, $value, \Closure $fail) {
                    if ($value === null) {
                        return;
                    }

                    $equipement = Equipement::query()->find($value);

                    if ($equipement === null) {
                        $fail('Équipement introuvable.');

                        return;
                    }

                    // Un retour ramène une unité « en service » ; une livraison
                    // ne rattache que des unités « en stock » non rattachées.
                    $statutsAdmis = $this->input('nature') === 'retour'
                        ? ['en_stock', 'en_service']
                        : ['en_stock'];

                    $admis = collect($statutsAdmis)
                        ->contains(fn (string $prefixe) => str_starts_with((string) $equipement->statut, $prefixe));

                    if (! $admis) {
                        $fail("L'unité {$equipement->code_inventaire} n'est ni « en stock » ni « en service » (statut : {$equipement->statut}).");
                    }

                    if (EquipementMagasin::query()->where('equipement_id', $value)->exists()) {
                        $fail("L'unité {$equipement->code_inventaire} est déjà rattachée à un magasin — c'est un transfert.");
                    }
                },
            ],
            'lignes.*.quantite' => ['required', 'numeric', 'gt:0'],
            'lignes.*.cout_unitaire' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function messagesSpecifiques(): array
    {
        return [
            'lignes.*.quantite.gt' => 'La quantité doit être strictement positive.',
            'lignes.*.quantite.required' => 'La quantité est obligatoire.',
            'lignes.*.article_id.required_without' => 'Chaque ligne porte un article ou une unité rattachée.',
            'lignes.*.article_id.prohibits' => 'Une ligne porte un article OU une unité, pas les deux.',
            'observation.required_if' => 'Le texte d\'observation est requis pour le motif « Autre ».',
            'date_document.required' => 'La date de livraison est obligatoire.',
        ];
    }
}
