<?php

namespace Modules\Stock\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\Niveau;

/**
 * Contrôle de cohérence (SFD §9.3, D9) : l'invariant « niveau = Σ mouvements »
 * est recalculé depuis le journal ; les unités rattachées à un magasin dont
 * le statut ParcInfo n'est plus « en stock » sont listées (incohérence D8,
 * résolue à l'inventaire).
 */
class ControleCoherenceCommand extends Command
{
    protected $signature = 'stock:controle-coherence';

    protected $description = 'Recalcule les niveaux depuis le journal des mouvements et liste les écarts (code retour ≠ 0 si écart)';

    public function handle(): int
    {
        $ecartsNiveaux = $this->ecartsNiveaux();
        $incoherencesEquipements = $this->incoherencesEquipements();

        if ($ecartsNiveaux === [] && $incoherencesEquipements === []) {
            $verifies = Niveau::query()->count();
            $this->info("✓ {$verifies} niveau(x) vérifié(s), 0 écart, 0 incohérence équipement.");

            return self::SUCCESS;
        }

        if ($ecartsNiveaux !== []) {
            $this->error(count($ecartsNiveaux).' écart(s) niveau ≠ Σ mouvements :');
            $this->table(
                ['Magasin', 'Article', 'Niveau enregistré', 'Σ mouvements', 'Écart'],
                $ecartsNiveaux
            );
        }

        if ($incoherencesEquipements !== []) {
            $this->error(count($incoherencesEquipements).' incohérence(s) équipement (rattaché mais statut ≠ « en stock ») :');
            $this->table(
                ['Équipement', 'N° série', 'Magasin', 'Statut ParcInfo'],
                $incoherencesEquipements
            );
        }

        return self::FAILURE;
    }

    /**
     * Confronte chaque couple (magasin, article) connu — par les niveaux OU
     * par le journal — à la somme signée de ses mouvements.
     */
    private function ecartsNiveaux(): array
    {
        $sommes = DB::table('stock_mouvements')
            ->selectRaw('magasin_id, article_id, SUM(sens * quantite) AS somme')
            ->whereNotNull('article_id')
            ->groupBy('magasin_id', 'article_id')
            ->get()
            ->keyBy(fn ($ligne) => $ligne->magasin_id.'-'.$ligne->article_id);

        $niveaux = Niveau::query()->with(['magasin:id,code', 'article:id,nom'])->get()
            ->keyBy(fn (Niveau $niveau) => $niveau->magasin_id.'-'.$niveau->article_id);

        $ecarts = [];

        foreach ($niveaux as $cle => $niveau) {
            $somme = (float) ($sommes[$cle]->somme ?? 0);

            if (abs((float) $niveau->quantite - $somme) > 0.001) {
                $ecarts[] = [
                    $niveau->magasin->code,
                    $niveau->article->nom,
                    (string) $niveau->quantite,
                    number_format($somme, 2, '.', ''),
                    number_format((float) $niveau->quantite - $somme, 2, '.', ''),
                ];
            }
        }

        // Couples mouvementés sans ligne de niveau (dérive inverse)
        foreach ($sommes as $cle => $somme) {
            if (! $niveaux->has($cle) && abs((float) $somme->somme) > 0.001) {
                $ecarts[] = [
                    (string) $somme->magasin_id,
                    'article #'.$somme->article_id.' (aucune ligne de niveau)',
                    '—',
                    number_format((float) $somme->somme, 2, '.', ''),
                    number_format(-(float) $somme->somme, 2, '.', ''),
                ];
            }
        }

        return $ecarts;
    }

    /**
     * Unités rattachées à un magasin dont le statut ParcInfo n'est plus un
     * statut « en stock » (en_stock, en_stock_magasin, en_stock_dsi…) —
     * LIKE uniquement, portable SQLite/PostgreSQL (SFD §9.4).
     */
    private function incoherencesEquipements(): array
    {
        return EquipementMagasin::query()
            ->with(['equipement:id,code_inventaire,numero_serie,statut', 'magasin:id,code'])
            ->get()
            ->reject(fn (EquipementMagasin $rattachement) => str_starts_with((string) $rattachement->equipement?->statut, 'en_stock'))
            ->map(fn (EquipementMagasin $rattachement) => [
                $rattachement->equipement->code_inventaire ?? '#'.$rattachement->equipement_id,
                $rattachement->equipement->numero_serie ?? '—',
                $rattachement->magasin->code,
                $rattachement->equipement->statut ?? '—',
            ])
            ->values()
            ->all();
    }
}
