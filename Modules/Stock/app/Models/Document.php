<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\User;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;

/**
 * Pièce jointe d'un bon (entrée, sortie, transfert) : BL scanné, photo,
 * courrier… Stockée sur le disque privé, servie par une route contrôlée.
 */
class Document extends Model
{
    use HasFactory, JournaliseActiviteStock;

    /** Disque privé : aucun accès direct par URL (pas de symlink public). */
    public const DISQUE = 'local';

    public const DOSSIER = 'stock/documents';

    protected $table = 'stock_documents';

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'nom_original',
        'chemin',
        'mime',
        'taille',
        'created_by',
    ];

    protected $casts = [
        'taille' => 'integer',
    ];

    protected static function booted(): void
    {
        // Le fichier suit la ligne : suppression de l'un = suppression de l'autre
        static::deleted(function (Document $document) {
            Storage::disk(self::DISQUE)->delete($document->chemin);
        });
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function estImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function estPdf(): bool
    {
        return $this->mime === 'application/pdf';
    }

    /** Icône Bootstrap correspondant au type de fichier. */
    public function getIconeAttribute(): string
    {
        return match (true) {
            $this->estPdf() => 'bi-file-earmark-pdf',
            $this->estImage() => 'bi-file-earmark-image',
            str_contains($this->mime, 'sheet') || str_contains($this->mime, 'excel') => 'bi-file-earmark-spreadsheet',
            str_contains($this->mime, 'word') => 'bi-file-earmark-word',
            default => 'bi-file-earmark',
        };
    }

    /** Taille lisible : « 1,2 Mo ». */
    public function getTailleLisibleAttribute(): string
    {
        $octets = (int) $this->taille;

        return match (true) {
            $octets >= 1_048_576 => number_format($octets / 1_048_576, 1, ',', ' ').' Mo',
            $octets >= 1_024 => number_format($octets / 1_024, 0, ',', ' ').' Ko',
            default => $octets.' o',
        };
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\DocumentFactory::new();
    }
}
