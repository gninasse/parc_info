<?php

namespace Modules\Stock\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Stock\Models\Document;

/**
 * Bon pouvant recevoir des pièces jointes (entrée, sortie, transfert).
 * Les fichiers suivent le bon : supprimer un brouillon supprime ses pièces.
 */
trait PorteDesDocuments
{
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest('id');
    }

    public static function bootPorteDesDocuments(): void
    {
        static::deleting(function ($document) {
            // delete() ligne à ligne : l'observer du modèle Document efface
            // aussi le fichier sur le disque
            $document->documents->each->delete();
        });
    }
}
