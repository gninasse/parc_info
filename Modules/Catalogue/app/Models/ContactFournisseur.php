<?php

namespace Modules\Catalogue\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\LogsActivityWithModule;

/**
 * Un interlocuteur chez un fournisseur.
 *
 * Distinct du champ `contact` du fournisseur, qui reste un texte libre pour
 * l'interlocuteur habituel : ici, chaque personne a ses propres coordonnées
 * et sa fonction, de sorte qu'on sait qui appeler pour une commande et qui
 * appeler pour une facture.
 */
class ContactFournisseur extends Model
{
    use HasFactory, LogsActivityWithModule;

    protected $table = 'catalogue_contacts_fournisseur';

    protected $fillable = [
        'fournisseur_id',
        'nom',
        'prenom',
        'fonction',
        'telephone',
        'email',
        'notes',
        'est_principal',
        'est_actif',
        'created_by',
    ];

    protected $casts = [
        'est_principal' => 'boolean',
        'est_actif' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::$activityModule = 'catalogue';

        static::saving(function (self $contact) {
            if (! $contact->exists) {
                $contact->created_by = $contact->created_by ?? auth()->id();
            }
        });
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    /** Nom affichable, sans double espace si le prénom manque. */
    public function getNomCompletAttribute(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('est_actif', true);
    }

    /**
     * Ordre d'affichage : le principal d'abord, puis les actifs, puis par nom.
     *
     * `est_principal` et `est_actif` sont des booléens, que SQLite stocke en
     * 0/1 et PostgreSQL en false/true : un `ORDER BY` direct sur la colonne
     * trie donc dans un sens opposé selon le moteur. On trie explicitement en
     * DESC (vrai avant faux), ce qui se comporte pareil sur les deux.
     */
    public function scopeOrdreAffichage(Builder $query): Builder
    {
        return $query->orderByDesc('est_principal')
            ->orderByDesc('est_actif')
            ->orderBy('nom');
    }

    protected static function newFactory()
    {
        return \Modules\Catalogue\Database\Factories\ContactFournisseurFactory::new();
    }
}
