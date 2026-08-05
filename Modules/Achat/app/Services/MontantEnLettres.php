<?php

namespace Modules\Achat\Services;

/**
 * Montant en toutes lettres pour le PDF du bon de commande (SPEC_UX §17).
 *
 * « Arrêté le présent bon de commande à la somme de … » : la somme en lettres
 * est une exigence des documents d'engagement — elle rend la falsification
 * d'un chiffre inopérante, puisque lettres et chiffres devraient être altérés
 * ensemble et de façon cohérente.
 *
 * Français des grands nombres : « quatre-vingts » perd son s devant un autre
 * nombre, « cent » et « vingt » s'accordent en fin de nombre, « mille » est
 * invariable. Le FCFA n'a pas de subdivision en usage : les centimes sont
 * ignorés à l'affichage en lettres (le tableau chiffré, lui, les porte).
 */
class MontantEnLettres
{
    private const UNITES = [
        '', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
        'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize',
        'dix-sept', 'dix-huit', 'dix-neuf',
    ];

    private const DIZAINES = [
        '', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', '', 'quatre-vingt', '',
    ];

    public function enFcfa(float $montant): string
    {
        $entier = (int) floor(abs($montant));

        if ($entier === 0) {
            return 'zéro franc CFA';
        }

        $lettres = $this->nombre($entier);
        $francs = $entier > 1 ? 'francs CFA' : 'franc CFA';

        return "{$lettres} {$francs}";
    }

    private function nombre(int $n): string
    {
        return match (true) {
            $n < 20 => self::UNITES[$n],
            $n < 100 => $this->dizaines($n),
            $n < 1000 => $this->centaines($n),
            $n < 1000000 => $this->bloc($n, 1000, 'mille', invariable: true),
            $n < 1000000000 => $this->bloc($n, 1000000, 'million'),
            default => $this->bloc($n, 1000000000, 'milliard'),
        };
    }

    private function dizaines(int $n): string
    {
        $dizaine = intdiv($n, 10);
        $unite = $n % 10;

        // 70-79 et 90-99 : soixante-dix…, quatre-vingt-dix…
        if ($dizaine === 7 || $dizaine === 9) {
            $base = self::DIZAINES[$dizaine - 1];
            $reste = self::UNITES[10 + $unite];

            // « soixante et onze », mais « quatre-vingt-onze »
            return $dizaine === 7 && $unite === 1
                ? "{$base} et onze"
                : "{$base}-{$reste}";
        }

        $base = self::DIZAINES[$dizaine];

        if ($unite === 0) {
            // « quatre-vingts » prend un s en fin de nombre
            return $dizaine === 8 ? $base.'s' : $base;
        }

        // « vingt et un », « trente et un »… mais « quatre-vingt-un »
        if ($unite === 1 && $dizaine !== 8) {
            return "{$base} et un";
        }

        return "{$base}-".self::UNITES[$unite];
    }

    private function centaines(int $n): string
    {
        $centaine = intdiv($n, 100);
        $reste = $n % 100;

        $base = $centaine === 1 ? 'cent' : self::UNITES[$centaine].' cent';

        if ($reste === 0) {
            // « deux cents », mais « deux cent trois »
            return $centaine > 1 ? $base.'s' : $base;
        }

        return "{$base} ".$this->nombre($reste);
    }

    private function bloc(int $n, int $diviseur, string $nom, bool $invariable = false): string
    {
        $quotient = intdiv($n, $diviseur);
        $reste = $n % $diviseur;

        $prefixe = match (true) {
            $quotient === 1 && $invariable => $nom,           // « mille », jamais « un mille »
            $quotient === 1 => "un {$nom}",
            $invariable => $this->nombre($quotient)." {$nom}", // « mille » invariable
            default => $this->nombre($quotient)." {$nom}s",
        };

        return $reste === 0 ? $prefixe : "{$prefixe} ".$this->nombre($reste);
    }
}
