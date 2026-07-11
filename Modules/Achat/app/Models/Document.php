<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Achat\Traits\HasAuditFields;

class Document extends Model
{
    use HasAuditFields, HasFactory, SoftDeletes;

    protected $table = 'achat_documents';

    protected $fillable = [
        'nom',
        'fichier_path',
        'taille',
        'type_mime',
        'notes',
        'documentable_id',
        'documentable_type',
    ];

    /**
     * Get the owning documentable model.
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
