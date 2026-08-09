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

    /** BR-01 — nature de la pièce (le BL du livreur n'est pas une photo). */
    public const TYPE_BL_FOURNISSEUR = 'bl_fournisseur';

    public const TYPE_PHOTO_LIVRAISON = 'photo_livraison';

    public const TYPE_AUTRE = 'autre';

    public const TYPES = [
        self::TYPE_BL_FOURNISSEUR => 'Bordereau du fournisseur',
        self::TYPE_PHOTO_LIVRAISON => 'Photo de la livraison',
        self::TYPE_AUTRE => 'Autre pièce',
    ];

    protected $table = 'stock_documents';

    protected $attributes = [
        'type' => self::TYPE_AUTRE,
        'est_supprime' => false,
    ];

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'type',
        'nom_original',
        'chemin',
        'mime',
        'taille',
        'created_by',
    ];

    protected $casts = [
        'taille' => 'integer',
        'est_supprime' => 'boolean',
        'supprime_le' => 'datetime',
    ];

    protected static function booted(): void
    {
        /*
         * Le fichier suit la ligne — SAUF pour une pierre tombale, dont le
         * fichier a déjà été effacé à la pose (le chemin est nul).
         */
        static::deleted(function (Document $document) {
            if ($document->chemin !== null) {
                Storage::disk(self::DISQUE)->delete($document->chemin);
            }
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
        return str_starts_with((string) $this->mime, 'image/');
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
            str_contains((string) $this->mime, 'sheet') || str_contains((string) $this->mime, 'excel') => 'bi-file-earmark-spreadsheet',
            str_contains((string) $this->mime, 'word') => 'bi-file-earmark-word',
            default => 'bi-file-earmark',
        };
    }

    /** Libellé de la nature de pièce (BR-01). */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? self::TYPES[self::TYPE_AUTRE];
    }

    public function estBlFournisseur(): bool
    {
        return $this->type === self::TYPE_BL_FOURNISSEUR;
    }

    /** Pièces vivantes : les pierres tombales n'ont plus de fichier. */
    public function scopeVivants($query)
    {
        return $query->where('est_supprime', false);
    }

    public function scopeDeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /** Ligne grisée de la liste des pièces (même doctrine qu'Achat A16). */
    public function getLibellePierreTombaleAttribute(): ?string
    {
        if (! $this->est_supprime) {
            return null;
        }

        return sprintf(
            'Pièce supprimée par %s le %s — motif : « %s »',
            $this->suppresseur?->name ?? 'un utilisateur supprimé',
            $this->supprime_le?->format('d/m/Y à H:i') ?? '—',
            $this->motif_suppression
        );
    }

    public function suppresseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supprime_par');
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
