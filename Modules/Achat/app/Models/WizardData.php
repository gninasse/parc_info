<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WizardData extends Model
{
    protected $table = 'achat_wizard_data';

    protected $fillable = [
        'bordereau_livraison_id',
        'article_id',
        'unites_data',
        'attributs_communs',
        'completed',
    ];

    protected function casts(): array
    {
        return [
            'unites_data' => 'array',
            'attributs_communs' => 'array',
            'completed' => 'boolean',
        ];
    }

    public function bordereauLivraison(): BelongsTo
    {
        return $this->belongsTo(BordereauLivraison::class, 'bordereau_livraison_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function getNombreUnitesAttribute(): int
    {
        return count($this->unites_data ?? []);
    }
}
