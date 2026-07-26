<?php

namespace Modules\Achat\Services;

use Modules\Achat\Contracts\ParcInfoIntegrationInterface;
use Modules\Achat\Models\Parametre;
use Modules\Achat\Traits\GeneratesDocumentNumbers;

/**
 * Génération des codes inventaire (RG-INT-04 / RG-NUM-03).
 *
 * Le compteur est porté par achat_sequences et verrouillé pendant la
 * génération : deux intégrations concurrentes ne peuvent pas produire le même
 * code. L'unicité est en outre revérifiée dans le parc avant restitution.
 */
class CodeInventaireGeneratorService
{
    use GeneratesDocumentNumbers;

    /** Garde-fou contre une collision persistante due à un pattern trop étroit. */
    protected const MAX_TENTATIVES = 50;

    public function __construct(protected ParcInfoIntegrationInterface $parcInfo) {}

    public function generer(): string
    {
        $pattern = Parametre::getVal('pattern_code_inventaire', config('achat.code_inventaire_pattern'));
        $annee = date('Y');

        for ($tentative = 0; $tentative < self::MAX_TENTATIVES; $tentative++) {
            $sequence = $this->prochaineSequence();
            $code = $this->appliquerPattern($pattern, $annee, $sequence);

            if (! $this->parcInfo->existeCodeInventaire($code)) {
                return $code;
            }
        }

        throw new \RuntimeException(
            'Impossible de générer un code inventaire unique après '.self::MAX_TENTATIVES.' tentatives. '.
            'Vérifiez le pattern de génération dans le paramétrage du module.'
        );
    }

    /** Incrémente le compteur annuel et retourne sa nouvelle valeur. */
    protected function prochaineSequence(): int
    {
        // Le trait renvoie « CODEINV-2026-0042 » : seule la séquence nous importe.
        $numero = $this->genererNumero('code_inventaire', 'SEQ');

        return (int) substr($numero, strrpos($numero, '-') + 1);
    }

    protected function appliquerPattern(string $pattern, string $annee, int $sequence): string
    {
        $code = str_replace(['{YYYY}', '{YY}'], [$annee, substr($annee, -2)], $pattern);

        if (preg_match('/\{SEQUENCE:(\d+)\}/', $code, $correspondances)) {
            $remplissage = (int) $correspondances[1];

            return str_replace(
                $correspondances[0],
                str_pad((string) $sequence, $remplissage, '0', STR_PAD_LEFT),
                $code
            );
        }

        return str_replace('{SEQUENCE}', (string) $sequence, $code);
    }
}
