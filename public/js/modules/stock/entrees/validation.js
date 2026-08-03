/**
 * validation.js — SW-VALIDER-ENT : délègue au module partagé (une seule
 * implémentation pour entrées, sorties et transferts).
 */
import { validerDocument } from '../shared/valider-document.js';

export function validerEntree(entreeId) {
    validerDocument({
        routeValider: route('stock.entrees.valider', entreeId),
        type: 'entree',
    });
}
