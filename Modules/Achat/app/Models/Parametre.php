<?php

namespace Modules\Achat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Achat\Models\Concerns\JournaliseActiviteAchat;
use Modules\Core\Models\User;

/**
 * Paramètre métier du module (SFD §6.2), administré par l'écran A-08.
 * `config/` reste réservé aux constantes techniques (leçon AN-13/14) : tout
 * ce qu'un gestionnaire doit pouvoir changer sans développeur vit ici.
 *
 * Ce modèle porte le STOCKAGE (texte brut). L'interprétation — typage, cache,
 * bornes d'intérim — appartient à `Services\AchatParametres` : c'est lui que
 * le code applicatif doit appeler, afin qu'il n'existe qu'une seule façon de
 * lire un paramètre.
 */
class Parametre extends Model
{
    use HasFactory, JournaliseActiviteAchat;

    public const PREFIXE_NUMEROTATION = 'prefixe_numerotation';

    public const DELAI_ALERTE_RELIQUAT_JOURS = 'delai_alerte_reliquat_jours';

    public const SEUIL_ECART_PRIX_PCT = 'seuil_ecart_prix_pct';

    public const TAILLE_MAX_PIECE_MO = 'taille_max_piece_mo';

    public const REGULARISATION_ACTIVE = 'regularisation_active';

    public const INTERMEDE_DEBUT = 'intermede_debut';

    public const INTERMEDE_FIN = 'intermede_fin';

    public const MOTIFS_OBSERVATION = 'motifs_observation';

    protected $table = 'achat_parametres';

    protected $fillable = [
        'cle',
        'valeur',
        'updated_by',
    ];

    public function auteurModification(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Écriture brute. Les appelants applicatifs passent par
     * `AchatParametres::set()`, qui normalise selon le type déclaré et
     * invalide le cache — cette méthode est le point d'écriture bas niveau.
     */
    public static function definir(string $cle, ?string $valeur, ?int $parUtilisateur = null): self
    {
        $parametre = static::query()->firstOrNew(['cle' => $cle]);
        $parametre->fill(['valeur' => $valeur, 'updated_by' => $parUtilisateur])->save();

        return $parametre;
    }

    protected static function newFactory()
    {
        return \Modules\Achat\Database\Factories\ParametreFactory::new();
    }
}
