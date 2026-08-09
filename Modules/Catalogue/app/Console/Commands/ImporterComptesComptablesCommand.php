<?php

namespace Modules\Catalogue\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;

/**
 * P0-B — reprise en masse des comptes comptables depuis un fichier CSV.
 *
 * Renseigner l'imputation article par article dans l'interface est réaliste
 * pour dix articles, pas pour un catalogue entier. Le service financier
 * travaille de toute façon sur un tableur : autant lire le sien.
 *
 * Format attendu, deux colonnes : `code_article ; compte_comptable`. Le
 * séparateur est détecté (`;` ou `,`), une éventuelle ligne d'en-tête est
 * ignorée.
 *
 * Trois partis pris de prudence :
 *
 *   - **rien n'est écrit sans que le rapport ait été montré**. La commande
 *     annonce ce qu'elle va faire, et demande confirmation ;
 *   - **un code inconnu n'interrompt pas** l'import : il est listé à la fin.
 *     Un fichier de 400 lignes dont la 12e comporte une coquille doit pouvoir
 *     passer, sinon personne ne l'utilisera ;
 *   - **les écrasements sont signalés** : remplacer une imputation déjà
 *     saisie n'est pas anodin, et se voit dans le rapport.
 */
class ImporterComptesComptablesCommand extends Command
{
    protected $signature = 'catalogue:importer-comptes
                            {fichier : chemin du CSV (code_article ; compte_comptable)}
                            {--simuler : afficher le rapport sans rien écrire}';

    protected $description = 'Importe les comptes comptables des articles depuis un CSV (P0-B)';

    public function handle(): int
    {
        $chemin = $this->argument('fichier');

        if (! is_readable($chemin)) {
            $this->components->error("Fichier illisible : {$chemin}");

            return self::FAILURE;
        }

        $lignes = $this->lire($chemin);

        if ($lignes === []) {
            $this->components->warn('Aucune ligne exploitable dans le fichier.');

            return self::SUCCESS;
        }

        $rapport = $this->analyser($lignes);

        $this->afficherRapport($rapport);

        if ($this->option('simuler')) {
            $this->components->info('Simulation : aucune écriture.');

            return self::SUCCESS;
        }

        $aEcrire = count($rapport['a_creer']) + count($rapport['a_ecraser']);

        if ($aEcrire === 0) {
            $this->components->info('Rien à écrire.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Appliquer {$aEcrire} imputation(s) ?", true)) {
            $this->components->warn('Abandonné — aucune écriture.');

            return self::SUCCESS;
        }

        $ecrites = $this->appliquer(array_merge($rapport['a_creer'], $rapport['a_ecraser']));

        $this->components->info("{$ecrites} article(s) mis à jour.");

        return self::SUCCESS;
    }

    /**
     * @return list<array{code: string, compte: string}>
     */
    private function lire(string $chemin): array
    {
        $lignes = [];

        $fichier = new \SplFileObject($chemin);
        $fichier->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

        // Le séparateur se déduit de la première ligne : les tableurs
        // francophones exportent en « ; », les autres en « , ».
        $premiere = (string) file($chemin)[0];
        $fichier->setCsvControl(substr_count($premiere, ';') >= substr_count($premiere, ',') ? ';' : ',');

        foreach ($fichier as $ligne) {
            if (! is_array($ligne) || count($ligne) < 2) {
                continue;
            }

            $code = trim((string) $ligne[0]);
            $compte = trim((string) $ligne[1]);

            if ($code === '' || $compte === '') {
                continue;
            }

            // Ligne d'en-tête : on la reconnaît à son intitulé, pas à sa
            // position (certains exports en ajoutent plusieurs).
            if (in_array(mb_strtolower($code), ['code', 'code_article', 'article'], true)) {
                continue;
            }

            $lignes[] = ['code' => $code, 'compte' => mb_substr($compte, 0, 50)];
        }

        return $lignes;
    }

    /**
     * @param  list<array{code: string, compte: string}>  $lignes
     * @return array{a_creer: list<array>, a_ecraser: list<array>, inchanges: int, inconnus: list<string>}
     */
    private function analyser(array $lignes): array
    {
        $articles = Article::query()
            ->whereIn('code', array_column($lignes, 'code'))
            ->get(['id', 'code', 'compte_comptable'])
            ->keyBy('code');

        $rapport = ['a_creer' => [], 'a_ecraser' => [], 'inchanges' => 0, 'inconnus' => []];

        foreach ($lignes as $ligne) {
            $article = $articles->get($ligne['code']);

            if ($article === null) {
                $rapport['inconnus'][] = $ligne['code'];

                continue;
            }

            if ((string) $article->compte_comptable === $ligne['compte']) {
                $rapport['inchanges']++;

                continue;
            }

            $entree = ['id' => $article->id, 'code' => $article->code,
                'ancien' => $article->compte_comptable, 'compte' => $ligne['compte']];

            if ($article->compte_comptable === null || $article->compte_comptable === '') {
                $rapport['a_creer'][] = $entree;
            } else {
                $rapport['a_ecraser'][] = $entree;
            }
        }

        return $rapport;
    }

    private function afficherRapport(array $rapport): void
    {
        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>À renseigner</>', (string) count($rapport['a_creer']));
        $this->components->twoColumnDetail('<fg=yellow>À écraser</>', (string) count($rapport['a_ecraser']));
        $this->components->twoColumnDetail('Déjà conformes', (string) $rapport['inchanges']);
        $this->components->twoColumnDetail('<fg=red>Codes inconnus</>', (string) count($rapport['inconnus']));

        // Un écrasement remplace une décision déjà prise : il se montre.
        foreach (array_slice($rapport['a_ecraser'], 0, 15) as $ligne) {
            $this->components->twoColumnDetail(
                "   {$ligne['code']}",
                "{$ligne['ancien']} → {$ligne['compte']}"
            );
        }

        if ($rapport['inconnus'] !== []) {
            $this->newLine();
            $this->components->warn(
                'Codes absents du catalogue : '
                .implode(', ', array_slice($rapport['inconnus'], 0, 20))
                .(count($rapport['inconnus']) > 20 ? ' …' : '')
            );
        }

        $this->newLine();
    }

    /** @param  list<array{id: int, compte: string}>  $lignes */
    private function appliquer(array $lignes): int
    {
        return DB::transaction(function () use ($lignes) {
            $ecrites = 0;

            foreach ($lignes as $ligne) {
                $article = Article::query()->find($ligne['id']);

                if ($article === null) {
                    continue;
                }

                // `update` et non `forceFill` : le journal doit enregistrer
                // qui a imputé quoi, comme pour toute modification d'article.
                $article->update(['compte_comptable' => $ligne['compte']]);
                $ecrites++;
            }

            return $ecrites;
        });
    }
}
