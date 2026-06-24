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

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'unites_data' => 'array',
            'attributs_communs' => 'array',
            'completed' => 'boolean',
        ];
    }

    /**
     * Get the associated delivery slip.
     */
    public function bordereauLivraison(): BelongsTo
    {
        return $this->belongsTo(BordereauLivraison::class, 'bordereau_livraison_id');
    }

    /**
     * Get the associated article.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
