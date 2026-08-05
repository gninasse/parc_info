<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Achat\Models\Parametre;

/**
 * Accès typé et mis en cache aux paramètres métier (SFD §6.2, écran A-08).
 *
 * Un paramètre est stocké en texte : sans ce service, chaque appelant
 * réinterpréterait « 1 », « 30 » ou une date à sa façon, et une faute de
 * frappe en base se propagerait silencieusement. Le TYPE est donc déclaré ici,
 * une fois, et la lecture est garantie : un booléen revient booléen, un entier
 * revient entier, une date revient Carbon ou null.
 *
 * Le cache évite de requêter la base à chaque lecture (les paramètres sont lus
 * sur presque tous les écrans) ; il est invalidé à chaque écriture, jamais
 * laissé expirer seul sur une valeur périmée.
 */
class AchatParametres
{
    private const PREFIXE_CACHE = 'achat.parametre.';

    private const DUREE_CACHE_MINUTES = 60;

    /** Type de chaque clé — la source unique d'interprétation. */
    private const TYPES = [
        Parametre::PREFIXE_NUMEROTATION => 'string',
        Parametre::DELAI_ALERTE_RELIQUAT_JOURS => 'int',
        Parametre::SEUIL_ECART_PRIX_PCT => 'int',
        Parametre::TAILLE_MAX_PIECE_MO => 'int',
        Parametre::REGULARISATION_ACTIVE => 'bool',
        Parametre::INTERMEDE_DEBUT => 'date',
        Parametre::INTERMEDE_FIN => 'date',
        Parametre::MOTIFS_OBSERVATION => 'json',
    ];

    /** Lecture typée, avec repli sur la valeur par défaut de config. */
    public function get(string $cle): mixed
    {
        $brut = Cache::remember(
            self::PREFIXE_CACHE.$cle,
            now()->addMinutes(self::DUREE_CACHE_MINUTES),
            fn () => Parametre::query()->where('cle', $cle)->value('valeur')
                ?? config('achat.parametres_defaut.'.$cle)
        );

        return $this->typer($cle, $brut);
    }

    /**
     * Écriture typée : la valeur est normalisée en texte selon le type
     * déclaré, puis le cache de cette clé est invalidé immédiatement.
     */
    public function set(string $cle, mixed $valeur, ?int $parUtilisateur = null): mixed
    {
        Parametre::definir($cle, $this->normaliser($cle, $valeur), $parUtilisateur ?? auth()->id());

        Cache::forget(self::PREFIXE_CACHE.$cle);

        return $this->get($cle);
    }

    // ── Accès nommés : lisibles à l'appel, impossibles à mal typer ─────────

    public function prefixeNumerotation(): string
    {
        return (string) $this->get(Parametre::PREFIXE_NUMEROTATION);
    }

    public function delaiAlerteReliquatJours(): int
    {
        return (int) $this->get(Parametre::DELAI_ALERTE_RELIQUAT_JOURS);
    }

    public function seuilEcartPrixPct(): int
    {
        return (int) $this->get(Parametre::SEUIL_ECART_PRIX_PCT);
    }

    public function tailleMaxPieceMo(): int
    {
        return (int) $this->get(Parametre::TAILLE_MAX_PIECE_MO);
    }

    /** Taille maximale en kilo-octets — l'unité attendue par la règle `max:`. */
    public function tailleMaxPieceKo(): int
    {
        return $this->tailleMaxPieceMo() * 1024;
    }

    public function regularisationActive(): bool
    {
        return (bool) $this->get(Parametre::REGULARISATION_ACTIVE);
    }

    public function intermedeDebut(): ?Carbon
    {
        return $this->get(Parametre::INTERMEDE_DEBUT);
    }

    public function intermedeFin(): ?Carbon
    {
        return $this->get(Parametre::INTERMEDE_FIN);
    }

    /** @return array<string, string> */
    public function motifsObservation(): array
    {
        $motifs = $this->get(Parametre::MOTIFS_OBSERVATION);

        return is_array($motifs) ? $motifs : [];
    }

    /**
     * Une date est-elle dans les bornes de l'intérim (A15) ?
     *
     * Une borne vide n'est pas une contrainte : `intermede_fin` reste ouverte
     * tant que la mise en service n'est pas prononcée. Le CHECK en base ne
     * pouvait pas porter cette règle (les bornes sont modifiables), c'est donc
     * ici qu'elle vit — signalé comme écart n°3 du README.
     */
    public function dateDansIntermede(Carbon|string|null $date): bool
    {
        if ($date === null) {
            return false;
        }

        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $debut = $this->intermedeDebut();
        $fin = $this->intermedeFin();

        if ($debut !== null && $date->lt($debut->startOfDay())) {
            return false;
        }

        if ($fin !== null && $date->gt($fin->endOfDay())) {
            return false;
        }

        return true;
    }

    /** Vide le cache de toutes les clés (après un seeder, un import…). */
    public function oublierTout(): void
    {
        foreach (array_keys(self::TYPES) as $cle) {
            Cache::forget(self::PREFIXE_CACHE.$cle);
        }
    }

    /** @return list<string> */
    public static function cles(): array
    {
        return array_keys(self::TYPES);
    }

    public static function typeDe(string $cle): string
    {
        return self::TYPES[$cle] ?? 'string';
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    private function typer(string $cle, mixed $brut): mixed
    {
        if ($brut === null) {
            return self::typeDe($cle) === 'json' ? [] : null;
        }

        return match (self::typeDe($cle)) {
            'int' => is_numeric($brut) ? (int) $brut : 0,
            'bool' => in_array((string) $brut, ['1', 'true', 'on', 'oui'], true),
            // Une borne vide signifie « pas de borne », pas « le 1er janvier 1970 »
            'date' => ($brut === '' || $brut === null) ? null : Carbon::parse($brut),
            'json' => is_array($brut) ? $brut : (json_decode((string) $brut, true) ?? []),
            default => (string) $brut,
        };
    }

    private function normaliser(string $cle, mixed $valeur): ?string
    {
        return match (self::typeDe($cle)) {
            'bool' => $valeur ? '1' : '0',
            'int' => (string) (int) $valeur,
            'date' => $valeur === null || $valeur === ''
                ? ''
                : ($valeur instanceof Carbon ? $valeur : Carbon::parse($valeur))->toDateString(),
            'json' => is_array($valeur) ? json_encode($valeur, JSON_UNESCAPED_UNICODE) : (string) $valeur,
            default => $valeur === null ? null : (string) $valeur,
        };
    }
}
