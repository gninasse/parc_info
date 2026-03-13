<?php

namespace Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Modules\Core\Models\Activity;

class CleanupExpiredActivitiesCommand extends Command
{
   /**
     * The name and signature of the console command.
     */
    protected $signature = 'activities:cleanup-expired 
                            {--dry-run : Afficher les activités à supprimer sans les supprimer}
                            {--module= : Nettoyer uniquement un module spécifique}
                            {--force : Supprimer même les activités critiques expirées}';

    /**
     * The console command description.
     */
    protected $description = 'Nettoyer les activités expirées selon leur date d\'expiration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Démarrage du nettoyage des activités expirées...');
        $this->newLine();

        $query = Activity::expired();

        // Filtre par module si spécifié
        if ($this->option('module')) {
            $module = $this->option('module');
            $query->forModule($module);
            $this->info("📦 Module filtré : {$module}");
        }

        // Exclure les activités critiques sauf si --force
        if (!$this->option('force')) {
            $query->where(function($q) {
                $q->whereNotIn('description', [
                    'deleted',
                    'permission_changed',
                    'role_changed',
                    'security_breach_detected',
                ]);
            });
            $this->warn('⚠️  Les activités critiques seront conservées (utilisez --force pour les inclure)');
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('✅ Aucune activité expirée à nettoyer.');
            return 0;
        }

        // Afficher les statistiques avant suppression
        $this->displayStatistics($query);

        // Mode dry-run
        if ($this->option('dry-run')) {
            $this->warn('🔍 Mode DRY RUN - Aucune suppression effectuée');
            $this->table(
                ['ID', 'Module', 'Description', 'Date', 'Expiration', 'Jours depuis expiration'],
                $query->take(20)->get()->map(fn($activity) => [
                    $activity->id,
                    $activity->module ?? 'N/A',
                    $activity->description,
                    $activity->created_at->format('Y-m-d'),
                    $activity->expires_at->format('Y-m-d'),
                    $activity->expires_at->diffInDays(now()),
                ])
            );

            if ($count > 20) {
                $this->info("... et " . ($count - 20) . " autres activités");
            }

            return 0;
        }

        // Confirmation
        if (!$this->confirm("Êtes-vous sûr de vouloir supprimer {$count} activité(s) expirée(s) ?")) {
            $this->info('❌ Opération annulée.');
            return 1;
        }

        // Suppression
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $deleted = 0;
        $query->chunk(100, function($activities) use ($bar, &$deleted) {
            foreach ($activities as $activity) {
                $activity->delete();
                $deleted++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$deleted} activité(s) expirée(s) supprimée(s) avec succès!");
        
        // Afficher l'espace disque libéré (estimation)
        $estimatedSpace = ($deleted * 2); // ~2KB par activité en moyenne
        $this->info("💾 Espace disque libéré (estimé) : ~{$estimatedSpace} KB");

        return 0;
    }

    /**
     * Afficher les statistiques avant suppression
     */
    protected function displayStatistics($query)
    {
        $this->info('📊 Statistiques des activités expirées :');
        $this->newLine();

        // Par module
        $byModule = (clone $query)
            ->selectRaw('module, COUNT(*) as count')
            ->groupBy('module')
            ->pluck('count', 'module');

        $this->table(
            ['Module', 'Nombre'],
            $byModule->map(fn($count, $module) => [$module ?? 'N/A', $count])
        );

        // Par durée d'expiration
        $byRetention = (clone $query)
            ->selectRaw('retention_months, COUNT(*) as count')
            ->whereNotNull('retention_months')
            ->groupBy('retention_months')
            ->pluck('count', 'retention_months');

        if ($byRetention->isNotEmpty()) {
            $this->newLine();
            $this->info('Par durée de rétention :');
            $this->table(
                ['Rétention (mois)', 'Nombre'],
                $byRetention->map(fn($count, $months) => [$months . ' mois', $count])
            );
        }

        $this->newLine();
    }
}
