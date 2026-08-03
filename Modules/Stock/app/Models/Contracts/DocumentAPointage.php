<?php

namespace Modules\Stock\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Document dont la phase 2 est un POINTAGE d'unités (sortie, transfert —
 * D14/D17) : PointageService et l'écran de pointage travaillent contre ce
 * contrat, jamais contre un type concret (exigence « zéro duplication »).
 */
interface DocumentAPointage
{
    /** Magasin dont les unités sont pointées (source des transferts). */
    public function magasinSourceId(): int;

    public function lignes(): HasMany;

    /** Colonne du tampon portant la FK de ligne : ligne_sortie_id | ligne_transfert_id. */
    public function colonneTamponLigne(): string;

    public function passerEnPointage(): void;

    public function retourBrouillon(): void;
}
