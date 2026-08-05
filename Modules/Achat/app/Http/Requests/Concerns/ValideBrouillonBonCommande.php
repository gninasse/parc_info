<?php

namespace Modules\Achat\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Modules\Achat\Services\AchatParametres;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;

/**
 * Contrôles de FORME du brouillon de bon de commande (SPEC_UX A-03).
 *
 * Ce qui relève de la COMPLÉTUDE (au moins une ligne, licences avec logiciel)
 * appartient à la soumission, pas à l'enregistrement : un brouillon est un bac
 * à sable, on doit pouvoir l'enregistrer inachevé et y revenir. Cette
 * distinction est celle du SFD §7.1 et de la doctrine du module Stock.
 *
 * Les messages suivent le catalogue de textes SPEC_UX §15.2, mot pour mot :
 * ils ont été rédigés pour l'utilisateur final, pas pour le développeur.
 */
trait ValideBrouillonBonCommande
{
    protected function reglesCommunes(): array
    {
        return [
            'fournisseur_id' => [
                'required', 'integer',
                Rule::exists('catalogue_fournisseurs', 'id'),
                function (string $attribut, $valeur, \Closure $echouer) {
                    $fournisseur = Fournisseur::query()->find($valeur);

                    // Fournisseur désactivé : on refuse de l'ENGAGER, mais on
                    // ne détruit jamais un brouillon existant (UX3-05) — c'est
                    // pourquoi le contrôle est ici et non en cascade.
                    if ($fournisseur !== null && ! $fournisseur->est_actif) {
                        $echouer("Le fournisseur « {$fournisseur->raison_sociale} » est désactivé au Catalogue.");
                    }
                },
            ],

            'date_document' => ['required', 'date'],

            'est_regularisation' => ['boolean'],

            'service_demandeur_id' => ['nullable', 'integer', Rule::exists('organisation_services', 'id')],
            'reference_demande' => ['nullable', 'string', 'max:255'],

            // Pilules de choix rapide : la liste vit dans les paramètres du
            // module (A-08), pas dans le code — l'ajout d'un motif ne demande
            // pas de redéploiement (leçon AN-13/14).
            'observation_type' => ['nullable', Rule::in(array_keys(app(AchatParametres::class)->motifsObservation()))],
            'observation_texte' => ['nullable', 'string', 'max:2000', 'required_if:observation_type,autre'],

            'lignes' => ['array'],
            /*
             * L'identifiant d'une ligne DÉJÀ enregistrée. Sans cette règle,
             * `validated()` l'écarterait silencieusement : chaque
             * enregistrement supprimerait puis recréerait toutes les lignes,
             * leur faisant perdre leur identité — et avec elle les quantités
             * déjà livrées qui s'y rattachent.
             */
            'lignes.*.id' => ['nullable', 'integer'],
            'lignes.*.article_id' => [
                'required', 'integer',
                function (string $attribut, $valeur, \Closure $echouer) {
                    $article = Article::query()->find($valeur);

                    if ($article === null) {
                        $echouer('Article inconnu au Catalogue — pas de création implicite (D7).');

                        return;
                    }

                    if (! $article->est_actif) {
                        $echouer("L'article « {$article->nom} » est désactivé au Catalogue.");
                    }
                },
            ],
            'lignes.*.quantite' => ['required', 'numeric', 'gt:0'],
            'lignes.*.prix_unitaire_ht' => ['required', 'numeric', 'min:0'],
            'lignes.*.taux_tva' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Garde des bornes d'intérim (A15, écart n°3 du README) : impossible en
     * CHECK statique puisque les bornes sont des paramètres modifiables, donc
     * appliquée ici. Le message reprend SPEC_UX §15.2 en y injectant les
     * bornes réelles — dire « hors période » sans donner la période ne rend
     * pas service.
     */
    protected function validerBornesRegularisation(\Illuminate\Validation\Validator $validateur): void
    {
        if (! $this->boolean('est_regularisation') || ! $this->filled('date_document')) {
            return;
        }

        $parametres = app(AchatParametres::class);

        if ($parametres->dateDansIntermede($this->input('date_document'))) {
            return;
        }

        $debut = $parametres->intermedeDebut()?->format('d/m/Y') ?? '—';
        $fin = $parametres->intermedeFin()?->format('d/m/Y') ?? 'la mise en service';

        $validateur->errors()->add(
            'date_document',
            "La date doit être comprise entre le {$debut} et le {$fin}."
        );
    }

    protected function messagesSpecifiques(): array
    {
        return [
            // Textes définitifs de SPEC_UX §15.2
            'lignes.*.quantite.gt' => 'La quantité doit être supérieure à zéro.',
            'lignes.*.quantite.required' => 'La quantité doit être supérieure à zéro.',
            'lignes.*.prix_unitaire_ht.required' => 'Indiquez le prix négocié.',
            'lignes.*.prix_unitaire_ht.min' => 'Le prix négocié ne peut pas être négatif.',
            'lignes.*.article_id.required' => 'Chaque ligne porte un article du Catalogue.',
            'observation_texte.required_if' => 'Précisez le motif pour le choix « Autre ».',
            'fournisseur_id.required' => 'Le fournisseur est obligatoire.',
            'date_document.required' => 'La date du bon est obligatoire.',
        ];
    }
}
