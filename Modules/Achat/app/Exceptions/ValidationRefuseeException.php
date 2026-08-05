<?php

namespace Modules\Achat\Exceptions;

/**
 * Validation refusée : un référentiel a été désactivé au Catalogue entre la
 * soumission et le visa (SFD §7.2).
 *
 * 422 explicite : le validateur doit savoir QUOI faire — réactiver la fiche au
 * Catalogue ou renvoyer le bon en brouillon — pas seulement que c'est refusé.
 */
class ValidationRefuseeException extends AchatException
{
    public static function fournisseurDesactive(string $raisonSociale): self
    {
        return new self(
            "Le fournisseur « {$raisonSociale} » a été désactivé au Catalogue depuis la soumission. "
            .'Réactivez la fiche ou renvoyez le bon en brouillon.'
        );
    }

    /**
     * @param  list<string>  $noms
     */
    public static function articlesDesactives(array $noms): self
    {
        $liste = implode(' », « ', $noms);

        return new self(
            count($noms) === 1
                ? "L'article « {$liste} » a été désactivé au Catalogue depuis la soumission. "
                    .'Réactivez la fiche ou renvoyez le bon en brouillon pour retirer la ligne.'
                : count($noms)." articles ont été désactivés au Catalogue depuis la soumission (« {$liste} »). "
                    .'Réactivez les fiches ou renvoyez le bon en brouillon.'
        );
    }
}
