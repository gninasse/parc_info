<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Models\Concerns\JournaliseActiviteStock;

/**
 * Tampon généralisé (D13/D14/D17) : références en cours de saisie/pointage.
 * Purgé à la validation, vidé au retour brouillon — l'unicité globale de
 * numero_serie et d'equipement_id y refuse les doublons entre bons non validés.
 */
class TamponEquipement extends Model
{
    use HasFactory, JournaliseActiviteStock;

    protected $table = 'stock_tampon_equipements';

    /** Référentiel ParcInfo des états (EquipementDynamiqueController). */
    public const ETATS = ['bon', 'passable', 'mauvais', 'avarie'];

    protected $fillable = [
        'ligne_entree_id',
        'ligne_sortie_id',
        'ligne_transfert_id',
        'numero_serie',
        'equipement_id',
        'etat',
    ];

    public function ligneEntree(): BelongsTo
    {
        return $this->belongsTo(LigneEntree::class, 'ligne_entree_id');
    }

    public function ligneSortie(): BelongsTo
    {
        return $this->belongsTo(LigneSortie::class, 'ligne_sortie_id');
    }

    public function ligneTransfert(): BelongsTo
    {
        return $this->belongsTo(LigneTransfert::class, 'ligne_transfert_id');
    }

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class);
    }

    protected static function newFactory()
    {
        return \Modules\Stock\Database\Factories\TamponEquipementFactory::new();
    }
}
