<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Collection;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\IntegrationReception;
use Modules\Core\Models\Activity;

/**
 * Chronologie de la fiche A-04 (SPEC_UX A-04, maquette P-04).
 *
 * IA-14 : la chronologie reflète EXACTEMENT le journal — chaque élément
 * affiché EST une ligne de `activity_log` (module = achat), jamais un
 * événement reconstruit depuis les colonnes du bon. Si le journal et la fiche
 * divergeaient, on ne saurait plus lequel croire.
 *
 * Le journal contient deux familles de lignes :
 *
 *   - les ÉVÉNEMENTS MÉTIER, écrits explicitement par les services
 *     (`soumission`, `renvoi_en_brouillon`, `validation`…) et par la création
 *     des modèles (`created`) — ce sont eux que la chronologie raconte ;
 *   - les traces TECHNIQUES de persistance (`updated` à chaque save : recalcul
 *     de montants, dénormalisations…), qui décrivent les mêmes gestes en
 *     morceaux. Les afficher noierait la timeline sous des « mis à jour »
 *     muets ; elles restent consultables au journal Core.
 */
class ChronologieBonCommande
{
    /**
     * Les éléments de la timeline, du plus ancien au plus récent.
     *
     * @return Collection<int, array{id: int, icone: string, couleur: string,
     *   phrase: string, auteur: ?string, quand: \Illuminate\Support\Carbon,
     *   details: ?string}>
     */
    public function pour(BonCommande $bon): Collection
    {
        $integrations = IntegrationReception::query()
            ->where('bon_commande_id', $bon->id)
            ->pluck('id');

        return Activity::query()
            ->forModule('achat')
            ->with('causer:id,name')
            ->where(function ($query) use ($bon, $integrations) {
                $query->where(function ($sous) use ($bon) {
                    $sous->where('subject_type', BonCommande::class)
                        ->where('subject_id', $bon->id);
                });

                if ($integrations->isNotEmpty()) {
                    $query->orWhere(function ($sous) use ($integrations) {
                        $sous->where('subject_type', IntegrationReception::class)
                            ->whereIn('subject_id', $integrations->all());
                    });
                }
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Activity $activite) => $this->presenter($activite, $bon))
            ->filter()
            ->values();
    }

    /** Une ligne du journal devient une phrase au passé — ou rien (trace technique). */
    private function presenter(Activity $activite, BonCommande $bon): ?array
    {
        $element = $activite->subject_type === IntegrationReception::class
            ? $this->presenterIntegration($activite)
            : $this->presenterBon($activite, $bon);

        if ($element === null) {
            return null;
        }

        return $element + [
            'id' => $activite->id,
            'auteur' => $activite->causer?->name,
            'quand' => $activite->created_at,
        ];
    }

    private function presenterBon(Activity $activite, BonCommande $bon): ?array
    {
        $props = $activite->properties ?? collect();

        return match ($activite->description) {
            'created' => [
                'icone' => 'bi-plus-circle',
                'couleur' => 'secondary',
                'phrase' => 'Brouillon créé',
                'details' => null,
            ],

            CircuitSoumissionService::EVENEMENT_SOUMISSION => [
                'icone' => 'bi-send',
                'couleur' => 'warning',
                'phrase' => 'Soumis au visa',
                'details' => sprintf(
                    '%s ligne(s) · %s FCFA TTC',
                    $props->get('nb_lignes') ?? '—',
                    number_format((float) $props->get('montant_ttc'), 0, ',', ' ')
                ),
            ],

            // Les rejets en ROUGE (SPEC_UX A-04) : un renvoi est un refus, il
            // doit se voir d'un coup d'œil dans l'histoire du bon.
            CircuitSoumissionService::EVENEMENT_RENVOI => [
                'icone' => 'bi-arrow-return-left',
                'couleur' => 'danger',
                'phrase' => 'Renvoyé en brouillon par le visa',
                'details' => $props->get('motif') !== null
                    ? 'Motif : « '.$props->get('motif').' »'
                    : null,
            ],

            CircuitSoumissionService::EVENEMENT_REPRISE => [
                'icone' => 'bi-arrow-counterclockwise',
                'couleur' => 'secondary',
                'phrase' => 'Repris par son auteur',
                'details' => null,
            ],

            VisaService::EVENEMENT_VALIDATION => [
                'icone' => 'bi-check-lg',
                'couleur' => 'primary',
                'phrase' => sprintf('Validé — numéro %s attribué', $props->get('numero') ?? $bon->numero ?? '—'),
                'details' => $props->get('auto_validation')
                    ? 'Saisi et validé par la même personne (auto-validation)'
                    : null,
            ],

            FinDeVieService::EVENEMENT_ANNULATION => [
                'icone' => 'bi-x-octagon',
                'couleur' => 'danger',
                'phrase' => 'Annulé',
                'details' => $props->get('motif') !== null
                    ? 'Motif : « '.$props->get('motif').' »'
                    : null,
            ],

            FinDeVieService::EVENEMENT_CLOTURE => [
                'icone' => 'bi-lock',
                'couleur' => 'dark',
                'phrase' => 'Reliquat clôturé',
                'details' => collect([
                    $props->get('motif') !== null ? 'Motif : « '.$props->get('motif').' »' : null,
                    collect($props->get('reliquat_abandonne') ?? [])
                        ->map(fn ($ligne) => sprintf(
                            '%s : %s abandonnée(s)',
                            $ligne['designation'] ?? 'Ligne',
                            rtrim(rtrim(number_format((float) ($ligne['reste'] ?? 0), 2, ',', ' '), '0'), ',')
                        ))->implode(' · ') ?: null,
                ])->filter()->implode(' — ') ?: null,
            ],

            // Les traces techniques (`updated`, `deleted`…) décrivent la
            // persistance des gestes ci-dessus : elles restent au journal.
            default => null,
        };
    }

    /**
     * Réceptions et contre-passations : l'événement `created` de la trace
     * d'intégration (API_Inter_Modules §5.1) EST l'acte au journal.
     */
    private function presenterIntegration(Activity $activite): ?array
    {
        if ($activite->description !== 'created') {
            return null;
        }

        $attributs = collect($activite->properties?->get('attributes') ?? []);
        $reference = $attributs->get('reference');
        $detail = collect($attributs->get('detail') ?? []);
        $unites = $detail->sum(fn ($ligne) => (float) ($ligne['quantite'] ?? 0));

        if ($attributs->get('sens') === IntegrationReception::SENS_CONTRE_PASSATION) {
            return [
                'icone' => 'bi-arrow-counterclockwise',
                'couleur' => 'danger',
                'phrase' => sprintf(
                    'Contre-passation %s — %s unité(s) retirées des livraisons',
                    $reference ?? 'd\'une réception',
                    rtrim(rtrim(number_format($unites, 2, ',', ' '), '0'), ',')
                ),
                'details' => null,
            ];
        }

        return [
            'icone' => 'bi-box-arrow-in-down',
            'couleur' => 'success',
            'phrase' => sprintf(
                'Réception %s intégrée — %s unité(s)',
                $reference ?? 'du magasin',
                rtrim(rtrim(number_format($unites, 2, ',', ' '), '0'), ',')
            ),
            'details' => $detail->isNotEmpty()
                ? $detail->map(fn ($ligne) => sprintf(
                    '%s : %s reçue(s), reste %s',
                    $ligne['designation'] ?? 'Ligne',
                    rtrim(rtrim(number_format((float) ($ligne['quantite'] ?? 0), 2, ',', ' '), '0'), ','),
                    rtrim(rtrim(number_format((float) ($ligne['reste'] ?? 0), 2, ',', ' '), '0'), ',')
                ))->implode(' · ')
                : null,
        ];
    }
}
