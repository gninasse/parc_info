<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Achat\Models\Concerns\JournaliseActiviteAchat;
use Modules\Core\Models\User;

/**
 * Pièce jointe d'un bon de commande, avec « pierre tombale » (A16).
 *
 * Supprimer une pièce après validation efface le fichier physique mais
 * conserve la ligne, motivée et signée : une pièce gênante ne disparaît pas
 * silencieusement (IA-13).
 */
class Document extends Model
{
    use HasFactory, JournaliseActiviteAchat;

    protected $table = 'achat_documents';

    protected $fillable = [
        'bon_commande_id',
        'type',
        'chemin',
        'nom_original',
        'mime',
        'taille',
        'created_by',
    ];

    protected $casts = [
        'est_supprime' => 'boolean',
        'taille' => 'integer',
        'supprime_le' => 'datetime',
    ];

    public function bonCommande(): BelongsTo
    {
        return $this->belongsTo(BonCommande::class, 'bon_commande_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function suppresseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supprime_par');
    }

    public function scopeVivants(Builder $query): Builder
    {
        return $query->where('est_supprime', false);
    }

    public function getTypeLabelAttribute(): string
    {
        return config('achat.types_documents.'.$this->type, $this->type);
    }

    /** Icône Bootstrap correspondant au format (convention Stock). */
    public function getIconeAttribute(): string
    {
        return match (true) {
            $this->mime === 'application/pdf' => 'bi-file-earmark-pdf',
            str_starts_with((string) $this->mime, 'image/') => 'bi-file-earmark-image',
            str_contains((string) $this->mime, 'sheet') || str_contains((string) $this->mime, 'excel') => 'bi-file-earmark-spreadsheet',
            str_contains((string) $this->mime, 'word') => 'bi-file-earmark-word',
            default => 'bi-file-earmark',
        };
    }

    /** Taille lisible : « 1,2 Mo » (convention Stock). */
    public function getTailleLisibleAttribute(): string
    {
        $octets = (int) $this->taille;

        return match (true) {
            $octets >= 1_048_576 => number_format($octets / 1_048_576, 1, ',', ' ').' Mo',
            $octets >= 1_024 => number_format($octets / 1_024, 0, ',', ' ').' Ko',
            default => $octets.' o',
        };
    }

    /** Ligne grisée de l'onglet Documents (SPEC_UX A-04). */
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

    protected static function newFactory()
    {
        return \Modules\Achat\Database\Factories\DocumentFactory::new();
    }
}
