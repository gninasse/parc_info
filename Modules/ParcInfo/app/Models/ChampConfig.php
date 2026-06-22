<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChampConfig extends Model
{
    protected $table = 'parc_info_champs_config';

    protected $fillable = [
        'categorie_id',
        'code',
        'libelle',
        'type_champ',
        'source_options',
        'regles_validation',
        'nom_panel',
        'ordre_affichage',
        'afficher_dans_modal',
        'afficher_dans_show',
        'afficher_dans_liste',
        'ordre_colonne_liste',
    ];

    protected $casts = [
        'afficher_dans_modal' => 'boolean',
        'afficher_dans_show' => 'boolean',
        'afficher_dans_liste' => 'boolean',
        'ordre_affichage' => 'integer',
        'ordre_colonne_liste' => 'integer',
    ];

    /**
     * Get parent category.
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieEquipement::class, 'categorie_id');
    }

    /**
     * Resolve options for select inputs.
     */
    public function getOptionsResolvedAttribute(): array
    {
        if ($this->type_champ !== 'select' || ! $this->source_options) {
            return [];
        }

        if (str_starts_with($this->source_options, 'DICT:')) {
            $dictCode = substr($this->source_options, 5);
            $dict = Dictionnaire::where('code', $dictCode)->first();
            if ($dict) {
                return $dict->valeurs()->pluck('valeur', 'id')->toArray();
            }

            return [];
        }

        if (str_starts_with($this->source_options, '[')) {
            $decoded = json_decode($this->source_options, true);
            if (is_array($decoded)) {
                return array_combine($decoded, $decoded);
            }
        }

        return [];
    }
}
