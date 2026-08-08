<?php

namespace Modules\Catalogue\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Catalogue\Models\Fournisseur;
use RuntimeException;

/**
 * Migration des référentiels ParcInfo vers le Catalogue (SFD §9.2).
 *
 * Transactionnelle : tout passe ou rien ne passe. --dry-run exécute la
 * totalité de la migration puis annule la transaction (rapport identique,
 * aucune écriture conservée). Refuse de tourner deux fois (détection des
 * données déjà migrées) et refuse toute collision de code (règle C5).
 */
class MigrateParcInfoCommand extends Command
{
    protected $signature = 'catalogue:migrate-parcinfo {--dry-run : Simule la migration puis annule la transaction}';

    protected $description = 'Migre types de consommables, consommables et fournisseurs ParcInfo vers le module Catalogue';

    /** Exception interne utilisée pour annuler la transaction en --dry-run. */
    private const DRY_RUN_ROLLBACK = 'catalogue:migrate-parcinfo dry-run rollback';

    public function handle(): int
    {
        $debut = microtime(true);
        $dryRun = (bool) $this->option('dry-run');

        if ($erreur = $this->prevol()) {
            $this->error($erreur);

            return self::FAILURE;
        }

        // Volumétrie source
        $nbTypes = DB::table('parc_info_types_consommables')->count();
        $nbConsommables = DB::table('parc_info_consommables')->count();
        $nbFournisseurs = DB::table('parc_info_fournisseurs')->count();
        $nbAffectations = DB::table('parc_info_affectations_consommables')->count();
        $nbLicences = DB::table('parc_info_licences')->count();

        $rapport = [];
        $ecarts = [];

        activity()->disableLogging();

        try {
            DB::transaction(function () use ($dryRun, $nbAffectations, $nbLicences, &$rapport, &$ecarts) {
                // 1. Types de consommables → catégories de niveau 1 (SFD §9.2)
                $mapCategories = [];
                foreach (DB::table('parc_info_types_consommables')->orderBy('id')->get() as $type) {
                    $categorie = Categorie::create([
                        'code' => $type->code,
                        'libelle' => $type->nom,
                    ]);
                    $mapCategories[$type->id] = $categorie->id;
                }
                $rapport[] = ['catalogue_categories', count($mapCategories), 'types de consommables → catégories niveau 1'];

                // 2. Fournisseurs → catalogue_fournisseurs (codes conservés)
                $mapFournisseurs = [];
                $fournisseurs = DB::table('parc_info_fournisseurs as f')
                    ->leftJoin('parc_info_contacts as c', 'c.id', '=', 'f.contact_principal_id')
                    ->select('f.*', 'c.nom as contact_nom', 'c.prenom as contact_prenom')
                    ->orderBy('f.id')
                    ->get();

                foreach ($fournisseurs as $source) {
                    $fournisseur = Fournisseur::create([
                        'code' => $source->code,
                        'raison_sociale' => $source->nom,
                        'contact' => trim(($source->contact_prenom ?? '').' '.($source->contact_nom ?? '')) ?: null,
                        'telephone' => $source->telephone,
                        'email' => $source->email,
                        'adresse' => implode(', ', array_filter([$source->adresse, $source->code_postal, $source->ville, $source->pays])) ?: null,
                        'est_actif' => (bool) $source->est_actif,
                    ]);
                    $mapFournisseurs[$source->id] = $fournisseur->id;
                }
                $rapport[] = ['catalogue_fournisseurs', count($mapFournisseurs), 'codes conservés'];

                // 3. Consommables → articles nature consommable (code conservé,
                //    unité et seuil descendus du type)
                $types = DB::table('parc_info_types_consommables')->get()->keyBy('id');
                $nbArticles = 0;
                foreach (DB::table('parc_info_consommables')->orderBy('id')->get() as $consommable) {
                    $type = $types[$consommable->type_consommable_id];

                    Article::create([
                        'code' => $consommable->code,
                        'nom' => $consommable->nom,
                        'nature' => Article::NATURE_CONSOMMABLE,
                        'categorie_id' => $mapCategories[$consommable->type_consommable_id],
                        'marque_id' => $consommable->marque_id,
                        'reference_constructeur' => $consommable->modele_reference,
                        'unite_stock' => $type->unite_stock,
                        'seuil_defaut' => $type->seul_reapprovisionnement,
                        'prix_indicatif' => $consommable->cout_unitaire,
                        'fournisseur_principal_id' => $mapFournisseurs[$consommable->fournisseur_principal_id] ?? null,
                        'compatibilites' => $consommable->compatible_equipements !== null
                            ? json_decode($consommable->compatible_equipements, true)
                            : null,
                        'est_actif' => (bool) $consommable->est_actif,
                        'notes' => $consommable->notes,
                    ]);
                    $nbArticles++;
                }
                $rapport[] = ['catalogue_articles', $nbArticles, 'consommables, codes conservés'];

                // 4. Re-routage des affectations (correspondance par code)
                DB::update('
                    UPDATE parc_info_affectations_consommables SET article_id = (
                        SELECT ca.id FROM catalogue_articles ca
                        JOIN parc_info_consommables pc ON pc.code = ca.code
                        WHERE pc.id = parc_info_affectations_consommables.consommable_id
                    )
                ');
                $affectationsRoutees = DB::table('parc_info_affectations_consommables')->whereNotNull('article_id')->count();
                $rapport[] = ['parc_info_affectations_consommables.article_id', $affectationsRoutees, "sur {$nbAffectations} affectation(s)"];
                if ($affectationsRoutees !== $nbAffectations) {
                    $ecarts[] = ($nbAffectations - $affectationsRoutees).' affectation(s) sans article correspondant';
                }

                // 5. Re-routage du FK des licences vers catalogue_fournisseurs
                DB::update('
                    UPDATE parc_info_licences SET fournisseur_id = (
                        SELECT cf.id FROM catalogue_fournisseurs cf
                        JOIN parc_info_fournisseurs pf ON pf.code = cf.code
                        WHERE pf.id = parc_info_licences.fournisseur_id
                    )
                ');
                $rapport[] = ['parc_info_licences.fournisseur_id', $nbLicences, 'remappé vers catalogue_fournisseurs'];

                // 6. Nouveau FK des licences (PostgreSQL ; SQLite ne permet pas
                //    l'ajout de contrainte a posteriori — intégrité applicative)
                if (DB::getDriverName() === 'pgsql') {
                    DB::statement('
                        ALTER TABLE parc_info_licences
                        ADD CONSTRAINT parc_info_licences_fournisseur_id_catalogue_foreign
                        FOREIGN KEY (fournisseur_id) REFERENCES catalogue_fournisseurs (id) ON DELETE RESTRICT
                    ');
                }

                if ($dryRun) {
                    throw new RuntimeException(self::DRY_RUN_ROLLBACK);
                }
            });
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== self::DRY_RUN_ROLLBACK) {
                activity()->enableLogging();
                throw $e;
            }
        } finally {
            activity()->enableLogging();
        }

        // Rapport final
        $this->newLine();
        $this->info($dryRun ? 'DRY-RUN — transaction annulée, aucune écriture conservée.' : 'Migration exécutée et validée.');
        $this->table(['Cible', 'Lignes migrées', 'Détail'], $rapport);

        $this->line(sprintf(
            'Volumétrie source : %d type(s), %d consommable(s), %d fournisseur(s), %d affectation(s), %d licence(s).',
            $nbTypes, $nbConsommables, $nbFournisseurs, $nbAffectations, $nbLicences
        ));

        if ($ecarts === []) {
            $this->info('Écarts : aucun.');
        } else {
            foreach ($ecarts as $ecart) {
                $this->warn("Écart : {$ecart}");
            }
        }

        $this->line(sprintf('Durée : %.2f s', microtime(true) - $debut));

        return $ecarts === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Contrôles préalables : tables présentes, colonne article_id posée,
     * anti-rejeu, collisions de codes (C5) et doublons (marque, référence).
     */
    private function prevol(): ?string
    {
        $tables = [
            'parc_info_types_consommables', 'parc_info_consommables',
            'parc_info_affectations_consommables', 'parc_info_fournisseurs',
            'parc_info_licences', 'catalogue_articles', 'catalogue_categories', 'catalogue_fournisseurs',
        ];
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                return "Table {$table} absente : exécutez d'abord les migrations (php artisan migrate).";
            }
        }

        if (! Schema::hasColumn('parc_info_affectations_consommables', 'article_id')) {
            return "Colonne parc_info_affectations_consommables.article_id absente : exécutez d'abord php artisan module:migrate ParcInfo.";
        }

        // Anti-rejeu : des affectations déjà re-routées = migration déjà passée
        if (DB::table('parc_info_affectations_consommables')->whereNotNull('article_id')->exists()) {
            return 'La migration a déjà été exécutée (affectations déjà re-routées). Elle ne peut pas être rejouée.';
        }

        $codesConsommables = DB::table('parc_info_consommables')->pluck('code');
        $codesFournisseurs = DB::table('parc_info_fournisseurs')->pluck('code');
        $codesTypes = DB::table('parc_info_types_consommables')->pluck('code');

        $collisionsArticles = $codesConsommables->isEmpty()
            ? collect()
            : DB::table('catalogue_articles')->whereIn('code', $codesConsommables)->pluck('code');
        $collisionsFournisseurs = $codesFournisseurs->isEmpty()
            ? collect()
            : DB::table('catalogue_fournisseurs')->whereIn('code', $codesFournisseurs)->pluck('code');
        $collisionsCategories = $codesTypes->isEmpty()
            ? collect()
            : DB::table('catalogue_categories')->whereIn('code', $codesTypes)->pluck('code');

        // Anti-rejeu : la totalité des codes déjà présents = migration déjà passée
        if ($codesConsommables->isNotEmpty() && $collisionsArticles->count() === $codesConsommables->unique()->count()) {
            return 'La migration a déjà été exécutée (tous les codes consommables existent déjà au catalogue). Elle ne peut pas être rejouée.';
        }

        // Collisions partielles = règle C5, rapport bloquant
        $collisions = $collisionsArticles->merge($collisionsFournisseurs)->merge($collisionsCategories);
        if ($collisions->isNotEmpty()) {
            return 'Collision de codes (règle C5), migration refusée. Codes déjà présents au catalogue : '.$collisions->implode(', ').'.';
        }

        // Doublons source qui violeraient l'index unique (marque, référence)
        $doublons = DB::table('parc_info_consommables')
            ->whereNotNull('marque_id')
            ->whereNotNull('modele_reference')
            ->select('marque_id', 'modele_reference', DB::raw('COUNT(*) as nb'))
            ->groupBy('marque_id', 'modele_reference')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        if ($doublons->isNotEmpty()) {
            return 'Doublons (marque, référence constructeur) dans parc_info_consommables, migration refusée : '
                .$doublons->map(fn ($d) => "marque #{$d->marque_id} / {$d->modele_reference} ({$d->nb} lignes)")->implode(' ; ').'.';
        }

        return null;
    }
}
