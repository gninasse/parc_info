<?php

namespace Modules\Achat\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Achat\Services\RegularisationService;
use Modules\Achat\Services\SignauxService;

/**
 * D-20 — les trois indicateurs du jalon J+30 (SFD §9.2).
 *
 * Un plan de mise en service qui se contente d'annoncer « on mesurera à
 * J+30 » ne mesure rien : le jour venu, personne ne sait où lire les
 * chiffres, et le jalon passe. Cette commande les sort en une ligne.
 *
 * Les trois indicateurs, et ce que chacun dit vraiment :
 *
 *   1. DETTE DE RÉGULARISATION — combien d'équipements du parc n'ont encore
 *      aucun bon de commande derrière eux. Elle doit décroître : si elle
 *      stagne, c'est que la saisie de la semaine 0 n'a pas été menée à bout ;
 *   2. DÉLAI MÉDIAN SOUMISSION → VISA — le temps qu'un bon attend son
 *      validateur. La médiane, pas la moyenne : un bon oublié trois mois ne
 *      doit pas masquer le quotidien ;
 *   3. PART DES RÉCEPTIONS LIÉES À UN BON — l'indicateur d'adoption, cible
 *      100 %. Une réception non liée signifie que le magasin a reçu une
 *      marchandise sans commande, ou qu'il n'a pas fait le lien : dans les
 *      deux cas, la chaîne est rompue à cet endroit précis.
 *
 * Aucun n'est un jugement : ce sont trois questions à poser en réunion.
 */
class IndicateursMiseEnServiceCommand extends Command
{
    protected $signature = 'achat:indicateurs
                            {--du= : début de la période (AAAA-MM-JJ)}
                            {--au= : fin de la période (AAAA-MM-JJ)}';

    protected $description = 'Les 3 indicateurs du jalon J+30 de la mise en service (SFD §9.2)';

    public function handle(RegularisationService $regularisation, SignauxService $signaux): int
    {
        $du = $this->option('du');
        $au = $this->option('au');

        $this->info('Indicateurs de mise en service — module Achat');
        $this->line('Période : '.($du ?? 'origine').' → '.($au ?? "aujourd'hui"));
        $this->newLine();

        $this->detteDeRegularisation($regularisation);
        $this->delaiDeVisa($signaux, $du, $au);
        $this->partDesReceptionsLiees($du, $au);

        $this->newLine();
        $this->line('Ces chiffres se lisent ensemble : une dette qui stagne avec un');
        $this->line('délai de visa court signale un problème de saisie, pas de circuit.');

        return self::SUCCESS;
    }

    /** 1. Ce que l'établissement possède sans pouvoir dire d'où ça vient. */
    private function detteDeRegularisation(RegularisationService $regularisation): void
    {
        $this->components->twoColumnDetail(
            '<fg=yellow>1. Dette de régularisation</>',
            'équipements sans bon de commande'
        );

        try {
            $dette = $regularisation->detteRestante();

            $this->components->twoColumnDetail('   Équipements concernés', (string) $dette);
            $this->components->twoColumnDetail(
                '   Lecture',
                $dette === 0
                    ? '<fg=green>dette éteinte</>'
                    : 'à comparer au relevé précédent — elle doit décroître'
            );
        } catch (\Throwable $e) {
            $this->components->twoColumnDetail('   Indisponible', $e->getMessage());
        }

        $this->newLine();
    }

    /** 2. Le temps qu'un bon passe à attendre son validateur. */
    private function delaiDeVisa(SignauxService $signaux, ?string $du, ?string $au): void
    {
        $this->components->twoColumnDetail(
            '<fg=yellow>2. Délai de visa</>',
            'de la soumission au visa, par validateur'
        );

        $lignes = collect($signaux->tous($du, $au)['delai_visa']['lignes'] ?? []);

        if ($lignes->isEmpty()) {
            $this->components->twoColumnDetail('   Aucun visa sur la période', '—');
            $this->newLine();

            return;
        }

        foreach ($lignes as $ligne) {
            $this->components->twoColumnDetail(
                '   '.$ligne['validateur'],
                sprintf(
                    '%s h médian (%s visa(s))',
                    number_format($ligne['delai_median_heures'], 1, ',', ' '),
                    $ligne['nombre_visas']
                )
            );
        }

        $this->newLine();
    }

    /**
     * 3. L'indicateur d'ADOPTION : une réception non liée à un bon signale
     * une chaîne rompue — marchandise reçue sans commande, ou lien oublié.
     */
    private function partDesReceptionsLiees(?string $du, ?string $au): void
    {
        $this->components->twoColumnDetail(
            '<fg=yellow>3. Réceptions liées à un bon</>',
            'cible : 100 %'
        );

        if (! Schema::hasTable('stock_entrees')) {
            $this->components->twoColumnDetail('   Module Stock indisponible', '—');
            $this->newLine();

            return;
        }

        $entrees = DB::table('stock_entrees')
            ->where('statut', 'VALIDE')
            // Un RETOUR de bénéficiaire ne livre aucune commande : l'inclure
            // ferait chuter l'indicateur sans qu'aucune chaîne soit rompue.
            ->where('nature', 'livraison')
            ->when($du !== null, fn ($q) => $q->whereDate('date_document', '>=', $du))
            ->when($au !== null, fn ($q) => $q->whereDate('date_document', '<=', $au))
            ->get(['id', 'numero', 'bon_commande_id']);

        if ($entrees->isEmpty()) {
            $this->components->twoColumnDetail('   Aucune livraison validée sur la période', '—');
            $this->newLine();

            return;
        }

        $liees = $entrees->whereNotNull('bon_commande_id');
        $part = round($liees->count() * 100 / $entrees->count(), 1);

        $this->components->twoColumnDetail('   Livraisons validées', (string) $entrees->count());
        $this->components->twoColumnDetail('   Dont liées à un bon', (string) $liees->count());
        $this->components->twoColumnDetail(
            '   Part',
            ($part >= 100 ? '<fg=green>' : ($part >= 80 ? '<fg=yellow>' : '<fg=red>'))
                .number_format($part, 1, ',', ' ').' %</>'
        );

        $orphelines = $entrees->whereNull('bon_commande_id');

        if ($orphelines->isNotEmpty()) {
            $this->components->twoColumnDetail(
                '   À examiner',
                $orphelines->take(8)->pluck('numero')->filter()->implode(', ')
                    .($orphelines->count() > 8 ? ' …' : '')
            );
        }

        $this->newLine();
    }
}
