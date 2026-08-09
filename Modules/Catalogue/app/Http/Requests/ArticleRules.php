<?php

namespace Modules\Catalogue\Http\Requests;

use Closure;
use Illuminate\Validation\Rule;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\ParcInfo\Models\Logiciel;

/**
 * Règles partagées Store/Update : les règles conditionnelles dépendent de la
 * nature — celle du payload en création, celle EN BASE en modification (C6 :
 * la nature reçue est ignorée).
 */
class ArticleRules
{
    public static function pour(?string $nature, ?int $ignorerId, mixed $marqueId): array
    {
        $referenceUnique = ['nullable', 'string', 'max:255'];
        if (! empty($marqueId)) {
            $unique = Rule::unique('catalogue_articles', 'reference_constructeur')->where('marque_id', $marqueId);
            if ($ignorerId !== null) {
                $unique = $unique->ignore($ignorerId);
            }
            $referenceUnique[] = $unique;
        }

        $regles = [
            'nom' => ['required', 'string', 'max:255'],
            'categorie_id' => [
                'required',
                'exists:catalogue_categories,id',
                function (string $attribute, mixed $value, Closure $fail) {
                    $categorie = Categorie::find($value);
                    if ($categorie && ! $categorie->est_actif) {
                        $fail('La catégorie doit être active.');
                    }
                },
            ],
            'marque_id' => ['nullable', 'exists:parc_info_marques,id'],
            'modele' => ['nullable', 'string', 'max:255'],
            'reference_constructeur' => $referenceUnique,
            'prix_indicatif' => ['nullable', 'numeric', 'min:0'],
            'taux_tva' => ['nullable', 'numeric', 'between:0,100'],
            // P0-B (PRQ-03) : format LIBRE en v1 — le plan comptable de
            // l'établissement n'est pas arrêté dans l'application.
            'compte_comptable' => ['nullable', 'string', 'max:50'],
            'fournisseur_principal_id' => ['nullable', 'exists:catalogue_fournisseurs,id'],
            'notes' => ['nullable', 'string'],
        ];

        // Règles conditionnelles par nature (§3.1). La prestation (P0-A) est
        // immatérielle comme la licence : aucun champ de stock, pas de
        // logiciel, pas de catégorie d'équipements.
        $estEquipement = $nature === Article::NATURE_EQUIPEMENT;
        $estLicence = $nature === Article::NATURE_LICENCE;
        $estStockable = in_array($nature, [Article::NATURE_CONSOMMABLE, Article::NATURE_PIECE], true);

        $regles['categorie_equipement_id'] = $estEquipement
            ? ['required', 'exists:parc_info_categories_equipements,id']
            : ['prohibited'];

        $regles['logiciel_id'] = $estLicence
            ? [
                'required',
                'exists:parc_info_logiciels,id',
                function (string $attribute, mixed $value, Closure $fail) {
                    $logiciel = Logiciel::find($value);
                    if ($logiciel && ! $logiciel->est_actif) {
                        $fail('Le logiciel référencé doit être actif.');
                    }
                },
            ]
            : ['prohibited'];

        // unite_stock : requise pour les natures stockées, forcée « unité »
        // par le modèle pour equipement/licence (valeur reçue ignorée)
        $regles['unite_stock'] = $estStockable
            ? ['required', 'string', 'max:30']
            : ['nullable', 'string', 'max:30'];

        $regles['seuil_defaut'] = $estStockable
            ? ['nullable', 'numeric', 'min:0']
            : ['prohibited'];

        $regles['compatibilites'] = $estStockable
            ? ['nullable', 'array']
            : ['prohibited'];
        $regles['compatibilites.*'] = ['string', 'exists:parc_info_categories_equipements,code'];

        return $regles;
    }

    public static function messages(): array
    {
        return [
            'nom.required' => 'La désignation est obligatoire.',
            'nom.max' => 'La désignation ne peut pas dépasser 255 caractères.',
            'categorie_id.required' => 'La catégorie est obligatoire.',
            'categorie_id.exists' => 'La catégorie est introuvable.',
            'marque_id.exists' => 'La marque est introuvable.',
            'modele.max' => 'Le modèle ne peut pas dépasser 255 caractères.',
            'reference_constructeur.unique' => 'Cette référence constructeur existe déjà pour cette marque.',
            'prix_indicatif.numeric' => 'Le prix indicatif doit être un nombre.',
            'prix_indicatif.min' => 'Le prix indicatif ne peut pas être négatif.',
            'taux_tva.between' => 'Le taux de TVA doit être compris entre 0 et 100.',
            'taux_tva.numeric' => 'Le taux de TVA doit être un nombre.',
            'compte_comptable.max' => 'Le compte comptable ne dépasse pas 50 caractères.',
            'fournisseur_principal_id.exists' => 'Le fournisseur est introuvable.',
            'categorie_equipement_id.required' => "La catégorie d'équipements du parc est obligatoire pour un modèle d'équipement.",
            'categorie_equipement_id.exists' => "La catégorie d'équipements est introuvable.",
            'categorie_equipement_id.prohibited' => "La catégorie d'équipements est réservée à la nature « équipement ».",
            'logiciel_id.required' => 'Le logiciel du parc est obligatoire pour une licence.',
            'logiciel_id.exists' => 'Le logiciel est introuvable.',
            'logiciel_id.prohibited' => 'Le logiciel est réservé à la nature « licence ».',
            'unite_stock.required' => "L'unité de stock est obligatoire pour cette nature.",
            'seuil_defaut.prohibited' => 'Le seuil par défaut est réservé aux natures stockables.',
            'seuil_defaut.min' => 'Le seuil par défaut ne peut pas être négatif.',
            'compatibilites.prohibited' => 'Les compatibilités sont réservées aux consommables et aux pièces.',
            'compatibilites.array' => 'Les compatibilités doivent être une liste.',
            'compatibilites.*.exists' => "Compatibilité inconnue : ce code de catégorie d'équipements n'existe pas.",
        ];
    }
}
