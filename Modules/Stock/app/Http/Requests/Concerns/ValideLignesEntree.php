<?php

namespace Modules\Stock\Http\Requests\Concerns;

use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
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
 *
 * Mode « Livraison sur commande » (RACCORDEMENT §2.3) : quand le brouillon
 * porte un bon_commande_id, le fournisseur est IMPOSÉ par le BC, chaque
 * article doit être SUR la commande, et la quantité saisie est plafonnée au
 * reste à livrer — contrôle champ par champ avec le reste dans le message.
 * Le re-contrôle définitif sous verrou appartient à la validation (D-12) :
 * ici on protège la saisie, pas les compteurs.
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

            // Liaison à un bon de commande (PRQ-05). Uniquement sous nature
            // livraison : un retour ne livre pas une commande.
            'bon_commande_id' => [
                'nullable', 'integer', 'prohibited_if:nature,retour',
                function (string $attribute, $value, \Closure $fail) {
                    if ($value === null) {
                        return;
                    }

                    // Module Achat absent : la liaison n'existe pas.
                    if (! Schema::hasTable('achat_bons_commande')) {
                        $fail('Le module Achat n\'est pas installé : aucune commande à lier.');

                        return;
                    }

                    $bon = \Modules\Achat\Models\BonCommande::query()->find($value);

                    if ($bon === null) {
                        $fail('Bon de commande introuvable.');
                    } elseif (! in_array($bon->statut, \Modules\Achat\Models\BonCommande::STATUTS_RECEPTIONNABLES, true)) {
                        $fail("Ce bon n'est pas livrable (statut : {$bon->statut_label}).");
                    }
                },
            ],
            'observation_type' => ['nullable', Rule::in(array_keys(config('stock.motifs_observation_entree', [])))],
            'observation' => ['nullable', 'string', 'required_if:observation_type,autre'],

            /*
             * BR-04 — le rapprochement BL ↔ saisie.
             *
             * FACULTATIF par construction : jamais un frein au quai. Le
             * magasinier pressé valide sans rien remplir, comme avant. Mais
             * s'il déclare un écart, celui-ci doit être exploitable — sinon
             * la pièce de réclamation ne vaut rien devant le fournisseur.
             */
            'ecarts_bl' => ['nullable', 'array', 'max:200'],
            'ecarts_bl.*.article_id' => ['required', 'integer', Rule::exists('catalogue_articles', 'id')],
            'ecarts_bl.*.quantite_annoncee_bl' => ['required', 'numeric', 'min:0'],
            'ecarts_bl.*.quantite_comptee' => ['required', 'numeric', 'min:0'],
            'ecarts_bl.*.motif' => ['required', Rule::in(array_keys(\Modules\Stock\Models\Entree::MOTIFS_ECART))],

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

    /**
     * Contrôles croisés du mode commande : ils lisent le BC entier, donc ils
     * vivent APRÈS les règles champ à champ, quand la forme est déjà bonne.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->controlerEcartsBl($validator);

            $bonCommandeId = $this->input('bon_commande_id');

            if ($bonCommandeId === null || $validator->errors()->has('bon_commande_id')) {
                return;
            }

            $bon = \Modules\Achat\Models\BonCommande::query()
                ->with('lignes')
                ->find($bonCommandeId);

            if ($bon === null) {
                return; // déjà refusé par la règle du champ
            }

            // Fournisseur IMPOSÉ par le BC (§2.3) : le champ est verrouillé à
            // l'écran, le serveur refuse quand même un POST forgé.
            if ($this->filled('fournisseur_id') && (int) $this->input('fournisseur_id') !== (int) $bon->fournisseur_id) {
                $validator->errors()->add(
                    'fournisseur_id',
                    'Le fournisseur est défini par la commande liée — il ne peut pas être changé.'
                );
            }

            $lignesBc = $bon->lignes->keyBy('article_id');

            foreach ($this->input('lignes', []) as $index => $ligne) {
                $articleId = $ligne['article_id'] ?? null;

                if ($articleId === null) {
                    // Un rattachement d'unité existante n'est pas une ligne de
                    // commande : pas de mélange sur un bon lié (§2.3).
                    if (($ligne['equipement_id'] ?? null) !== null) {
                        $validator->errors()->add(
                            "lignes.{$index}.equipement_id",
                            'Un bon lié à une commande ne rattache pas d\'unités existantes — créez un bon d\'entrée séparé.'
                        );
                    }

                    continue;
                }

                $ligneBc = $lignesBc->get((int) $articleId);

                // Article hors commande : refusé (§2.3, message §15.2).
                if ($ligneBc === null) {
                    $validator->errors()->add(
                        "lignes.{$index}.article_id",
                        'Cet article n\'est pas sur la commande — créez un bon d\'entrée séparé.'
                    );

                    continue;
                }

                // Plafond de SAISIE : quantité ≤ reste à livrer, champ par
                // champ, le reste dans le message (§15.2).
                $reste = $ligneBc->reste;
                $quantite = (float) ($ligne['quantite'] ?? 0);

                if ($quantite > $reste) {
                    $validator->errors()->add(
                        "lignes.{$index}.quantite",
                        sprintf(
                            'Quantité supérieure au reste à livrer (reste : %s).',
                            rtrim(rtrim(number_format($reste, 2, ',', ' '), '0'), ',')
                        )
                    );
                }
            }
        });
    }

    /**
     * BR-04 — cohérence du rapprochement BL, quand il est renseigné.
     *
     * Trois exigences, et pas une de plus (le quai n'est pas un guichet) :
     *
     *   1. le motif « Écart BL — réclamation » : déclarer des écarts sous
     *      « Livraison conforme » produirait un bordereau qui se contredit ;
     *   2. l'article concerné est SUR le bon d'entrée — un écart porte sur ce
     *      qu'on a reçu, pas sur une ligne imaginaire ;
     *   3. l'écart en est un : annoncé ≠ compté. Deux nombres égaux ne
     *      documentent rien et pollueraient le signal par fournisseur.
     */
    private function controlerEcartsBl(Validator $validator): void
    {
        $ecarts = $this->input('ecarts_bl', []);

        if (! is_array($ecarts) || $ecarts === []) {
            return;
        }

        if ($this->input('observation_type') !== \Modules\Stock\Models\Entree::OBSERVATION_ECART_BL) {
            $validator->errors()->add(
                'ecarts_bl',
                'Choisissez le motif « Écart BL — réclamation » pour déclarer des écarts de livraison.'
            );

            return;
        }

        $articlesDuBon = collect($this->input('lignes', []))
            ->pluck('article_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($ecarts as $index => $ecart) {
            if (! is_array($ecart)) {
                continue;
            }

            $articleId = (int) ($ecart['article_id'] ?? 0);

            if ($articleId > 0 && ! in_array($articleId, $articlesDuBon, true)) {
                $validator->errors()->add(
                    "ecarts_bl.{$index}.article_id",
                    'Cet article n\'est pas sur le bon d\'entrée : un écart porte sur une ligne reçue.'
                );
            }

            $annoncee = (float) ($ecart['quantite_annoncee_bl'] ?? 0);
            $comptee = (float) ($ecart['quantite_comptee'] ?? 0);

            if (abs($annoncee - $comptee) < 0.0001) {
                $validator->errors()->add(
                    "ecarts_bl.{$index}.quantite_comptee",
                    'Annoncé et compté sont identiques : il n\'y a pas d\'écart à déclarer.'
                );
            }
        }
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
            'bon_commande_id.prohibited_if' => 'Un retour ne livre pas une commande : déliez le bon avant de changer la nature.',
        ];
    }
}
