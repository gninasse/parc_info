<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\Document;
use Modules\Achat\Models\LigneCommande;

/**
 * Les 8 SIGNAUX du SFD §7.7 — la carte sous permission dédiée (UX4-09).
 *
 * Doctrine, et elle n'est pas négociable : ces indicateurs SIGNALENT, ils
 * n'accusent pas. Un écart de prix peut être parfaitement justifié, une
 * auto-validation peut être la seule option un jour de congés. Aucun de ces
 * chiffres ne bloque quoi que ce soit dans l'application — ils donnent à un
 * responsable de quoi POSER UNE QUESTION, ce qui est tout autre chose.
 *
 * Tout est calculé À LA VOLÉE : aucune table dédiée, donc rien à
 * resynchroniser, et un signal qui disparaît quand la donnée change.
 */
class SignauxService
{
    /** Fenêtre des rapprochements et des « fournisseurs récents » (jours). */
    private const FENETRE_JOURS = 30;

    public function __construct(
        private readonly AchatParametres $parametres,
        private readonly ReferencePrixService $referencePrix,
    ) {}

    /**
     * Les huit indicateurs, dans l'ordre du SFD.
     *
     * @return array<string, array{titre: string, aide: string, lignes: list<array<string, mixed>>}>
     */
    public function tous(?string $du = null, ?string $au = null): array
    {
        return [
            'ecarts_prix' => [
                'titre' => 'Écarts au dernier prix payé',
                'aide' => 'Lignes dont le prix négocié s\'écarte de plus de '
                    .$this->parametres->seuilEcartPrixPct().' % du dernier prix effectivement payé.',
                'lignes' => $this->ecartsAuDernierPrix($du, $au),
            ],
            'prix_modifies' => [
                'titre' => 'Prix indicatifs modifiés peu avant un bon',
                'aide' => 'Articles dont le prix indicatif a changé moins de 30 jours avant d\'être commandés.',
                'lignes' => $this->prixModifiesAvantCommande($du, $au),
            ],
            'fournisseurs_recents' => [
                'titre' => 'Fournisseurs récents au premier bon',
                'aide' => 'Fournisseurs créés au Catalogue moins de 30 jours avant leur premier bon engagé.',
                'lignes' => $this->fournisseursRecents($du, $au),
            ],
            'bons_rapproches' => [
                'titre' => 'Bons rapprochés',
                'aide' => 'Plusieurs bons pour le même fournisseur à moins de 30 jours d\'intervalle — un marché fractionné se lit ainsi.',
                'lignes' => $this->bonsRapproches($du, $au),
            ],
            'clotures_non_livres' => [
                'titre' => 'Montants clôturés non livrés',
                'aide' => 'Ce à quoi l\'établissement a renoncé, par fournisseur.',
                'lignes' => $this->cloturesNonLivres($du, $au),
            ],
            'auto_validations' => [
                'titre' => 'Auto-validations',
                'aide' => 'Bons saisis et validés par la même personne (UX4-07).',
                'lignes' => $this->autoValidations($du, $au),
            ],
            'delai_visa' => [
                'titre' => 'Délai médian de visa par validateur',
                'aide' => 'Temps écoulé entre la soumission et le visa. La médiane, pas la moyenne : un bon oublié trois mois ne doit pas masquer le quotidien.',
                'lignes' => $this->delaiMedianDeVisa($du, $au),
            ],
            'pierres_tombales' => [
                'titre' => 'Pièces supprimées après validation',
                'aide' => 'Suppressions motivées de pièces justificatives sur des bons engagés (A16).',
                'lignes' => $this->pierresTombales($du, $au),
            ],
        ];
    }

    // ── 1. Écarts au dernier prix payé ─────────────────────────────────────

    private function ecartsAuDernierPrix(?string $du, ?string $au): array
    {
        $lignes = LigneCommande::query()
            ->whereIn('bon_commande_id', $this->bonsEngages($du, $au)->select('achat_bons_commande.id'))
            ->whereNotNull('article_id')
            ->with('bonCommande:id,numero,fournisseur_libelle,date_document')
            ->get();

        $resultats = [];

        foreach ($lignes as $ligne) {
            $ecart = $this->referencePrix->ecart($ligne->article_id, (float) $ligne->prix_unitaire_ht);

            if (! $ecart['depasse_seuil']) {
                continue;
            }

            $resultats[] = [
                'bon' => $ligne->bonCommande?->numero,
                'date' => $ligne->bonCommande?->date_document?->format('d/m/Y'),
                'fournisseur' => $ligne->bonCommande?->fournisseur_libelle,
                'article' => $ligne->designation,
                'prix_negocie' => (float) $ligne->prix_unitaire_ht,
                'reference' => $ecart['reference'],
                'ecart_pct' => $ecart['ecart_pct'],
            ];
        }

        usort($resultats, fn ($a, $b) => abs($b['ecart_pct']) <=> abs($a['ecart_pct']));

        return $resultats;
    }

    // ── 2. Prix indicatif modifié < 30 j avant la commande ─────────────────

    /**
     * Lecture du journal CATALOGUE : les modifications de `prix_indicatif`
     * survenues dans les 30 jours précédant un bon portant cet article.
     */
    private function prixModifiesAvantCommande(?string $du, ?string $au): array
    {
        if (! Schema::hasTable('activity_log')) {
            return [];
        }

        $lignes = LigneCommande::query()
            ->whereIn('bon_commande_id', $this->bonsEngages($du, $au)->select('achat_bons_commande.id'))
            ->whereNotNull('article_id')
            ->with('bonCommande:id,numero,date_document')
            ->get();

        $resultats = [];

        foreach ($lignes as $ligne) {
            $dateBon = $ligne->bonCommande?->date_document;

            if ($dateBon === null) {
                continue;
            }

            $modification = DB::table('activity_log')
                ->where('subject_type', \Modules\Catalogue\Models\Article::class)
                ->where('subject_id', $ligne->article_id)
                ->where('created_at', '>=', $dateBon->copy()->subDays(self::FENETRE_JOURS))
                ->where('created_at', '<=', $dateBon->copy()->endOfDay())
                ->orderByDesc('created_at')
                ->first(['properties', 'created_at']);

            if ($modification === null) {
                continue;
            }

            $proprietes = json_decode($modification->properties ?? '{}', true);
            $ancien = $proprietes['old']['prix_indicatif'] ?? null;
            $nouveau = $proprietes['attributes']['prix_indicatif'] ?? null;

            // Seules les modifications DE PRIX comptent : un changement de
            // libellé n'a rien à faire dans un signal d'achat.
            if ($ancien === null || $nouveau === null || (float) $ancien === (float) $nouveau) {
                continue;
            }

            $resultats[] = [
                'bon' => $ligne->bonCommande?->numero,
                'article' => $ligne->designation,
                'ancien_prix' => round((float) $ancien, 2),
                'nouveau_prix' => round((float) $nouveau, 2),
                'modifie_le' => \Illuminate\Support\Carbon::parse($modification->created_at)->format('d/m/Y'),
                'commande_le' => $dateBon->format('d/m/Y'),
            ];
        }

        return $resultats;
    }

    // ── 3. Fournisseurs récents au premier bon ─────────────────────────────

    private function fournisseursRecents(?string $du, ?string $au): array
    {
        $premiers = $this->bonsEngages($du, $au)
            ->join('catalogue_fournisseurs', 'catalogue_fournisseurs.id', '=', 'achat_bons_commande.fournisseur_id')
            ->orderBy('achat_bons_commande.valide_le')
            ->get([
                'achat_bons_commande.id',
                'achat_bons_commande.numero',
                'achat_bons_commande.valide_le',
                'achat_bons_commande.montant_ttc',
                'catalogue_fournisseurs.id AS fournisseur_id',
                'catalogue_fournisseurs.raison_sociale',
                'catalogue_fournisseurs.created_at AS fournisseur_cree_le',
            ])
            ->groupBy('fournisseur_id')
            ->map(fn ($bons) => $bons->first());

        $resultats = [];

        foreach ($premiers as $bon) {
            if ($bon->fournisseur_cree_le === null || $bon->valide_le === null) {
                continue;
            }

            $creeLe = \Illuminate\Support\Carbon::parse($bon->fournisseur_cree_le);
            $anciennete = (int) $creeLe->diffInDays(\Illuminate\Support\Carbon::parse($bon->valide_le));

            if ($anciennete > self::FENETRE_JOURS) {
                continue;
            }

            $resultats[] = [
                'fournisseur' => $bon->raison_sociale,
                'cree_le' => $creeLe->format('d/m/Y'),
                'premier_bon' => $bon->numero,
                'anciennete_jours' => $anciennete,
                'montant_ttc' => round((float) $bon->montant_ttc, 2),
            ];
        }

        return $resultats;
    }

    // ── 4. Bons rapprochés (même fournisseur, < 30 jours) ──────────────────

    private function bonsRapproches(?string $du, ?string $au): array
    {
        $bons = $this->bonsEngages($du, $au)
            ->leftJoin('catalogue_fournisseurs', 'catalogue_fournisseurs.id', '=', 'achat_bons_commande.fournisseur_id')
            ->orderBy('achat_bons_commande.fournisseur_id')
            ->orderBy('achat_bons_commande.date_document')
            ->get([
                'achat_bons_commande.numero',
                'achat_bons_commande.date_document',
                'achat_bons_commande.montant_ttc',
                'achat_bons_commande.fournisseur_id',
                'catalogue_fournisseurs.raison_sociale',
            ]);

        $resultats = [];

        foreach ($bons->groupBy('fournisseur_id') as $duFournisseur) {
            $precedent = null;

            foreach ($duFournisseur as $bon) {
                if ($precedent !== null) {
                    $ecart = (int) \Illuminate\Support\Carbon::parse($precedent->date_document)
                        ->diffInDays(\Illuminate\Support\Carbon::parse($bon->date_document));

                    if ($ecart <= self::FENETRE_JOURS) {
                        $resultats[] = [
                            'fournisseur' => $bon->raison_sociale ?? '—',
                            'premier_bon' => $precedent->numero,
                            'second_bon' => $bon->numero,
                            'ecart_jours' => $ecart,
                            'cumul_ttc' => round((float) $precedent->montant_ttc + (float) $bon->montant_ttc, 2),
                        ];
                    }
                }

                $precedent = $bon;
            }
        }

        usort($resultats, fn ($a, $b) => $a['ecart_jours'] <=> $b['ecart_jours']);

        return $resultats;
    }

    // ── 5. Montants clôturés non livrés ────────────────────────────────────

    private function cloturesNonLivres(?string $du, ?string $au): array
    {
        $bons = BonCommande::query()
            ->where('statut', BonCommande::STATUT_CLOTURE)
            ->horsRegularisation()
            ->when($du !== null, fn ($q) => $q->whereDate('date_document', '>=', $du))
            ->when($au !== null, fn ($q) => $q->whereDate('date_document', '<=', $au))
            ->with(['lignes', 'fournisseur:id,raison_sociale', 'createur:id,name'])
            ->get();

        $parFournisseur = [];

        foreach ($bons as $bon) {
            $abandonne = $bon->lignes->sum(
                fn (LigneCommande $ligne) => max(0, $ligne->reste) * (float) $ligne->prix_unitaire_ht
            );

            if ($abandonne <= 0) {
                continue;
            }

            $cle = $bon->fournisseur_libelle ?? $bon->fournisseur?->raison_sociale ?? '—';

            $parFournisseur[$cle] ??= [
                'fournisseur' => $cle,
                'nombre_bons' => 0,
                'montant_abandonne_ht' => 0.0,
                'auteurs' => [],
            ];

            $parFournisseur[$cle]['nombre_bons']++;
            $parFournisseur[$cle]['montant_abandonne_ht'] += $abandonne;

            if ($bon->createur?->name !== null) {
                $parFournisseur[$cle]['auteurs'][$bon->createur->name] = true;
            }
        }

        $resultats = array_map(fn (array $ligne) => [
            'fournisseur' => $ligne['fournisseur'],
            'nombre_bons' => $ligne['nombre_bons'],
            'montant_abandonne_ht' => round($ligne['montant_abandonne_ht'], 2),
            'auteurs' => implode(', ', array_keys($ligne['auteurs'])),
        ], array_values($parFournisseur));

        usort($resultats, fn ($a, $b) => $b['montant_abandonne_ht'] <=> $a['montant_abandonne_ht']);

        return $resultats;
    }

    // ── 6. Auto-validations ────────────────────────────────────────────────

    private function autoValidations(?string $du, ?string $au): array
    {
        return $this->bonsEngages($du, $au)
            ->whereNotNull('achat_bons_commande.valide_par')
            ->whereColumn('achat_bons_commande.created_by', 'achat_bons_commande.valide_par')
            ->leftJoin('users', 'users.id', '=', 'achat_bons_commande.valide_par')
            ->orderByDesc('achat_bons_commande.valide_le')
            ->get([
                'achat_bons_commande.numero',
                'achat_bons_commande.valide_le',
                'achat_bons_commande.montant_ttc',
                'users.name AS auteur',
            ])
            ->map(fn ($bon) => [
                'bon' => $bon->numero,
                'auteur' => $bon->auteur ?? '—',
                'valide_le' => \Illuminate\Support\Carbon::parse($bon->valide_le)->format('d/m/Y'),
                'montant_ttc' => round((float) $bon->montant_ttc, 2),
            ])
            ->all();
    }

    // ── 7. Délai médian de visa, par validateur ────────────────────────────

    /**
     * MÉDIANE et non moyenne : un bon oublié trois mois tirerait la moyenne
     * au point de rendre l'indicateur inutilisable, alors que la médiane
     * décrit le quotidien réel du validateur.
     */
    private function delaiMedianDeVisa(?string $du, ?string $au): array
    {
        $bons = $this->bonsEngages($du, $au)
            ->whereNotNull('achat_bons_commande.soumis_le')
            ->whereNotNull('achat_bons_commande.valide_le')
            ->leftJoin('users', 'users.id', '=', 'achat_bons_commande.valide_par')
            ->get([
                'achat_bons_commande.soumis_le',
                'achat_bons_commande.valide_le',
                'achat_bons_commande.valide_par',
                'users.name AS validateur',
            ]);

        $resultats = [];

        foreach ($bons->groupBy('valide_par') as $parValidateur) {
            $heures = $parValidateur
                ->map(fn ($bon) => \Illuminate\Support\Carbon::parse($bon->soumis_le)
                    ->diffInHours(\Illuminate\Support\Carbon::parse($bon->valide_le)))
                ->sort()
                ->values();

            if ($heures->isEmpty()) {
                continue;
            }

            $milieu = intdiv($heures->count(), 2);
            $mediane = $heures->count() % 2 === 1
                ? $heures[$milieu]
                : ($heures[$milieu - 1] + $heures[$milieu]) / 2;

            $resultats[] = [
                'validateur' => $parValidateur->first()->validateur ?? '—',
                'nombre_visas' => $heures->count(),
                'delai_median_heures' => round((float) $mediane, 1),
                'delai_median_jours' => round((float) $mediane / 24, 1),
            ];
        }

        usort($resultats, fn ($a, $b) => $b['delai_median_heures'] <=> $a['delai_median_heures']);

        return $resultats;
    }

    // ── 8. Pierres tombales de pièces ──────────────────────────────────────

    private function pierresTombales(?string $du, ?string $au): array
    {
        return Document::query()
            ->where('est_supprime', true)
            ->when($du !== null, fn ($q) => $q->whereDate('supprime_le', '>=', $du))
            ->when($au !== null, fn ($q) => $q->whereDate('supprime_le', '<=', $au))
            ->with(['bonCommande:id,numero', 'suppresseur:id,name'])
            ->orderByDesc('supprime_le')
            ->get()
            ->map(fn (Document $document) => [
                'bon' => $document->bonCommande?->numero ?? '—',
                'piece' => $document->nom_original,
                'type' => $document->type_label,
                'supprimee_par' => $document->suppresseur?->name ?? '—',
                'le' => $document->supprime_le?->format('d/m/Y H:i'),
                'motif' => $document->motif_suppression,
            ])
            ->all();
    }

    // ── Base commune ───────────────────────────────────────────────────────

    /** Bons engagés hors régularisation : le périmètre de tous les signaux. */
    private function bonsEngages(?string $du, ?string $au)
    {
        return BonCommande::query()
            ->engages()
            ->horsRegularisation()
            ->when($du !== null, fn ($q) => $q->whereDate('achat_bons_commande.date_document', '>=', $du))
            ->when($au !== null, fn ($q) => $q->whereDate('achat_bons_commande.date_document', '<=', $au));
    }
}
