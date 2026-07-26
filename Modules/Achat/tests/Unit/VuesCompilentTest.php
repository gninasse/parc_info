<?php

namespace Modules\Achat\Tests\Unit;

use Illuminate\View\Compilers\BladeCompiler;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Garde-fou : toute vue du module doit produire du PHP syntaxiquement valide.
 *
 * Une erreur de structure Blade ne se manifeste qu'au rendu de la vue, donc
 * seulement si un test couvre l'écran concerné. Ce test ferme la brèche en
 * vérifiant l'ensemble des vues, y compris celles rarement empruntées.
 *
 * Il a été ajouté après qu'une directive « @php(...) » — forme courte non
 * supportée, interprétée comme l'ouverture d'un bloc — a rendu le PDF des
 * bordereaux inutilisable sans qu'aucun contrôle ne le signale.
 */
class VuesCompilentTest extends TestCase
{
    public function test_toutes_les_vues_produisent_du_php_valide(): void
    {
        /** @var BladeCompiler $compilateur */
        $compilateur = $this->app->make('blade.compiler');
        $fichierTemporaire = tempnam(sys_get_temp_dir(), 'blade_').'.php';
        $erreurs = [];
        $verifiees = 0;

        foreach ($this->vues() as $vue) {
            file_put_contents(
                $fichierTemporaire,
                $compilateur->compileString(file_get_contents($vue))
            );

            exec('php -l '.escapeshellarg($fichierTemporaire).' 2>&1', $sortie, $code);

            if ($code !== 0) {
                $erreurs[] = $this->cheminRelatif($vue).' : '.trim($sortie[0] ?? 'erreur inconnue');
            }

            $verifiees++;
            $sortie = [];
        }

        @unlink($fichierTemporaire);

        $this->assertGreaterThan(0, $verifiees, 'Aucune vue trouvée : le chemin de recherche est-il correct ?');
        $this->assertSame([], $erreurs, "Vues produisant du PHP invalide :\n".implode("\n", $erreurs));
    }

    /** Aucune vue ne doit utiliser la forme courte « @php(...) ». */
    public function test_aucune_vue_n_utilise_la_forme_courte_de_la_directive_php(): void
    {
        $fautives = [];

        foreach ($this->vues() as $vue) {
            if (preg_match('/@php\s*\(/', file_get_contents($vue))) {
                $fautives[] = $this->cheminRelatif($vue);
            }
        }

        $this->assertSame(
            [],
            $fautives,
            "La forme « @php(\$x = 1) » n'est pas supportée : Blade l'interprète comme l'ouverture ".
            "d'un bloc et avale les directives suivantes jusqu'au prochain @endphp. ".
            "Utiliser « @php \$x = 1; @endphp ».\nVues concernées :\n".implode("\n", $fautives)
        );
    }

    /** @return array<string> */
    protected function vues(): array
    {
        $racine = base_path('Modules/Achat/resources/views');
        $vues = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($racine)) as $fichier) {
            if (str_ends_with($fichier->getFilename(), '.blade.php')) {
                $vues[] = $fichier->getPathname();
            }
        }

        sort($vues);

        return $vues;
    }

    protected function cheminRelatif(string $chemin): string
    {
        return str_replace(base_path('Modules/Achat/resources/views').'/', '', $chemin);
    }
}
