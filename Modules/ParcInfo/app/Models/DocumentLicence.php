<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentLicence extends Model
{
    protected $table = 'parc_info_documents_licences';

    protected $fillable = [
        'licence_id',
        'nom',
        'type_document',
        'fichier_path',
        'date_document',
        'notes',
    ];

    protected $casts = [
        'date_document' => 'date',
    ];

    public function licence()
    {
        return $this->belongsTo(Licence::class);
    }
}
