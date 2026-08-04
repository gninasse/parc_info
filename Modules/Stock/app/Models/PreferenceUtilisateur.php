<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;

class PreferenceUtilisateur extends Model
{
    use JournaliseActiviteStock;

    protected $table = 'stock_preferences';

    protected $fillable = [
        'user_id',
        'magasin_defaut_id',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function magasinDefaut(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_defaut_id');
    }
}
