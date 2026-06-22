<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Find the old grouped categories
        $oldReseau = DB::table('parc_info_categories_equipements')->where('code', 'reseau')->first();
        $oldInfra = DB::table('parc_info_categories_equipements')->where('code', 'infrastructure')->first();

        // 2. Define the new specific categories to create
        $newCategories = [
            'switch' => ['libelle' => 'Switch', 'icone' => 'bi-hdd-network'],
            'routeur' => ['libelle' => 'Routeur', 'icone' => 'bi-router'],
            'wifi' => ['libelle' => 'WiFi', 'icone' => 'bi-wifi'],
            'parefeu' => ['libelle' => 'Pare-feu', 'icone' => 'bi-shield-shaded'],
            'onduleur' => ['libelle' => 'Onduleur', 'icone' => 'bi-lightning-charge'],
            'rack' => ['libelle' => 'Baie & Rack', 'icone' => 'bi-grid-3x3-gap'],
            'brassage' => ['libelle' => 'Brassage', 'icone' => 'bi-ethernet'],
        ];

        $newCatIds = [];
        foreach ($newCategories as $code => $data) {
            // Check if already exists to prevent duplicate key errors
            $existing = DB::table('parc_info_categories_equipements')->where('code', $code)->first();
            if ($existing) {
                $newCatIds[$code] = $existing->id;
                DB::table('parc_info_categories_equipements')->where('id', $existing->id)->update([
                    'libelle' => $data['libelle'],
                    'icone' => $data['icone'],
                    'updated_at' => now(),
                ]);
            } else {
                $newCatIds[$code] = DB::table('parc_info_categories_equipements')->insertGetId([
                    'code' => $code,
                    'libelle' => $data['libelle'],
                    'icone' => $data['icone'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Replicate fields configuration from parent categories to children
        if ($oldReseau) {
            $reseauFields = DB::table('parc_info_champs_config')->where('categorie_id', $oldReseau->id)->get();
            foreach (['switch', 'routeur', 'wifi', 'parefeu'] as $childCode) {
                $childId = $newCatIds[$childCode];
                foreach ($reseauFields as $field) {
                    $exists = DB::table('parc_info_champs_config')
                        ->where('categorie_id', $childId)
                        ->where('code', $field->code)
                        ->exists();

                    if (! $exists) {
                        DB::table('parc_info_champs_config')->insert([
                            'categorie_id' => $childId,
                            'code' => $field->code,
                            'libelle' => $field->libelle,
                            'type_champ' => $field->type_champ,
                            'source_options' => $field->source_options,
                            'regles_validation' => $field->regles_validation,
                            'nom_panel' => $field->nom_panel,
                            'ordre_affichage' => $field->ordre_affichage,
                            'afficher_dans_modal' => $field->afficher_dans_modal,
                            'afficher_dans_show' => $field->afficher_dans_show,
                            'afficher_dans_liste' => $field->afficher_dans_liste,
                            'ordre_colonne_liste' => $field->ordre_colonne_liste,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        if ($oldInfra) {
            $infraFields = DB::table('parc_info_champs_config')->where('categorie_id', $oldInfra->id)->get();
            foreach (['onduleur', 'rack', 'brassage'] as $childCode) {
                $childId = $newCatIds[$childCode];
                foreach ($infraFields as $field) {
                    $exists = DB::table('parc_info_champs_config')
                        ->where('categorie_id', $childId)
                        ->where('code', $field->code)
                        ->exists();

                    if (! $exists) {
                        DB::table('parc_info_champs_config')->insert([
                            'categorie_id' => $childId,
                            'code' => $field->code,
                            'libelle' => $field->libelle,
                            'type_champ' => $field->type_champ,
                            'source_options' => $field->source_options,
                            'regles_validation' => $field->regles_validation,
                            'nom_panel' => $field->nom_panel,
                            'ordre_affichage' => $field->ordre_affichage,
                            'afficher_dans_modal' => $field->afficher_dans_modal,
                            'afficher_dans_show' => $field->afficher_dans_show,
                            'afficher_dans_liste' => $field->afficher_dans_liste,
                            'ordre_colonne_liste' => $field->ordre_colonne_liste,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // 4. Map existing equipment records to their new specific category IDs
        // Build maps from dictionary values
        $typeReseauMap = [];
        $typeReseauDict = DB::table('parc_info_dictionnaires')->where('code', 'type_reseau')->first();
        if ($typeReseauDict) {
            $vals = DB::table('parc_info_dictionnaire_valeurs')->where('dictionnaire_id', $typeReseauDict->id)->get();
            foreach ($vals as $v) {
                $lowerVal = strtolower($v->valeur);
                if (str_contains($lowerVal, 'switch') || str_contains($lowerVal, 'commutateur')) {
                    $typeReseauMap[$v->id] = $newCatIds['switch'];
                } elseif (str_contains($lowerVal, 'routeur')) {
                    $typeReseauMap[$v->id] = $newCatIds['routeur'];
                } elseif (str_contains($lowerVal, 'wifi') || str_contains($lowerVal, 'accès') || str_contains($lowerVal, 'borne')) {
                    $typeReseauMap[$v->id] = $newCatIds['wifi'];
                } elseif (str_contains($lowerVal, 'firewall') || str_contains($lowerVal, 'pare-feu') || str_contains($lowerVal, 'parefeu')) {
                    $typeReseauMap[$v->id] = $newCatIds['parefeu'];
                }
            }
        }

        $typeInfraMap = [];
        $typeInfraDict = DB::table('parc_info_dictionnaires')->where('code', 'type_infrastructure')->first();
        if ($typeInfraDict) {
            $vals = DB::table('parc_info_dictionnaire_valeurs')->where('dictionnaire_id', $typeInfraDict->id)->get();
            foreach ($vals as $v) {
                $lowerVal = strtolower($v->valeur);
                if (str_contains($lowerVal, 'onduleur')) {
                    $typeInfraMap[$v->id] = $newCatIds['onduleur'];
                } elseif (str_contains($lowerVal, 'rack') || str_contains($lowerVal, 'baie')) {
                    $typeInfraMap[$v->id] = $newCatIds['rack'];
                } elseif (str_contains($lowerVal, 'brassage') || str_contains($lowerVal, 'panneau')) {
                    $typeInfraMap[$v->id] = $newCatIds['brassage'];
                }
            }
        }

        // Migrate Reseau equipments
        if ($oldReseau) {
            $equipments = DB::table('parc_info_equipements')->where('categorie_id', $oldReseau->id)->get();
            foreach ($equipments as $eq) {
                $champs = json_decode($eq->champs_valeurs, true) ?? [];
                $typeReseauId = $champs['type_reseau_id'] ?? null;
                $targetCatId = $typeReseauMap[$typeReseauId] ?? $newCatIds['switch'];

                DB::table('parc_info_equipements')->where('id', $eq->id)->update([
                    'categorie_id' => $targetCatId,
                ]);
            }
        }

        // Migrate Infra equipments
        if ($oldInfra) {
            $equipments = DB::table('parc_info_equipements')->where('categorie_id', $oldInfra->id)->get();
            foreach ($equipments as $eq) {
                $champs = json_decode($eq->champs_valeurs, true) ?? [];
                $typeInfraId = $champs['type_infra_id'] ?? null;
                $targetCatId = $typeInfraMap[$typeInfraId] ?? $newCatIds['onduleur'];

                DB::table('parc_info_equipements')->where('id', $eq->id)->update([
                    'categorie_id' => $targetCatId,
                ]);
            }
        }

        // 5. Clean up old category records & old permissions
        if ($oldReseau) {
            DB::table('parc_info_champs_config')->where('categorie_id', $oldReseau->id)->delete();
            DB::table('parc_info_categories_equipements')->where('id', $oldReseau->id)->delete();
            DB::table('permissions')->where('name', 'like', 'parcinfo.reseaux.%')->delete();
        }

        if ($oldInfra) {
            DB::table('parc_info_champs_config')->where('categorie_id', $oldInfra->id)->delete();
            DB::table('parc_info_categories_equipements')->where('id', $oldInfra->id)->delete();
            DB::table('permissions')->where('name', 'like', 'parcinfo.infrastructures.%')->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversal is not trivial without restoring backups, but seeding can reset state
    }
};
