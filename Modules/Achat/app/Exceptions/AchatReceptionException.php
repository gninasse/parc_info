<?php

namespace Modules\Achat\Exceptions;

/**
 * Échec d'intégration d'une réception (API_Inter_Modules §5.1).
 *
 * Portée par ligne : l'écran Stock doit pouvoir dire au magasinier QUELLE
 * ligne dépasse et de combien, pas seulement « erreur ». Le message par défaut
 * propose la sortie métier (bon complémentaire ou refus à la livraison),
 * parce qu'un refus sans issue ne rend service à personne.
 */
class AchatReceptionException extends AchatException
{
    /** @var array<string, string> ligne_id => message */
    private array $erreursParLigne = [];

    /** @return array<string, string> */
    public function erreursParLigne(): array
    {
        return $this->erreursParLigne;
    }

    /** Le bon n'est pas (ou plus) livrable : statut incompatible. */
    public static function bonNonLivrable(string $document, string $statutLabel): self
    {
        return new self("Le bon {$document} n'est pas livrable (statut : {$statutLabel}).");
    }

    /**
     * Dépassement du reste à livrer, ligne à ligne (IA-4).
     *
     * @param  array<int, array{designation: string, reste: float, demande: float}>  $depassements
     */
    public static function plafondDepasse(array $depassements): self
    {
        $messages = [];

        foreach ($depassements as $ligneId => $detail) {
            $reste = rtrim(rtrim(number_format($detail['reste'], 2, ',', ' '), '0'), ',');
            $demande = rtrim(rtrim(number_format($detail['demande'], 2, ',', ' '), '0'), ',');

            $messages[(string) $ligneId] = sprintf(
                'Ligne %s : reste à livrer %s, or %s sont annoncés — pour l\'excédent, '
                .'créez un bon de commande complémentaire ou refusez à la livraison.',
                $detail['designation'],
                $reste,
                $demande
            );
        }

        $exception = new self(
            count($messages) === 1
                ? reset($messages)
                : count($messages).' lignes dépassent le reste à livrer de la commande.'
        );

        $exception->erreursParLigne = $messages;

        return $exception;
    }

    /** Une ligne reçue ne correspond à aucune ligne du bon de commande. */
    public static function ligneHorsCommande(string $designation, string $document): self
    {
        return new self(
            "L'article « {$designation} » n'est pas sur la commande {$document} : "
            .'créez un bon d\'entrée séparé pour cette livraison.'
        );
    }
}
