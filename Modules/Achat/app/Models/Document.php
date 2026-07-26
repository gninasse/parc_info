<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
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

    protected function casts(): array
    {
        return [
            'taille' => 'integer',
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Accesseurs ─────────────────────────────────────────────────────────

    public function getExisteAttribute(): bool
    {
        return Storage::disk(config('achat.documents.disque', 'public'))->exists($this->fichier_path);
    }

    public function getTailleLisibleAttribute(): string
    {
        $octets = (int) $this->taille;

        if ($octets >= 1048576) {
            return number_format($octets / 1048576, 1, ',', ' ').' Mo';
        }

        return number_format($octets / 1024, 1, ',', ' ').' Ko';
    }

    /** Classe d'icône FontAwesome déduite du type MIME. */
    public function getIconeAttribute(): string
    {
        return match (true) {
            str_contains((string) $this->type_mime, 'pdf') => 'far fa-file-pdf text-danger',
            str_contains((string) $this->type_mime, 'image') => 'far fa-file-image text-success',
            str_contains((string) $this->type_mime, 'sheet'),
            str_contains((string) $this->type_mime, 'excel') => 'far fa-file-excel text-success',
            str_contains((string) $this->type_mime, 'word') => 'far fa-file-word text-primary',
            default => 'far fa-file text-secondary',
        };
    }
}
