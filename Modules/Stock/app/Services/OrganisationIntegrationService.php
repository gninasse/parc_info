<?php

namespace Modules\Stock\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\PosteTravail;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Unite;
use Modules\Stock\Contracts\OrganisationIntegrationInterface;

/**
 * Seul point de contact du module Stock avec les modèles Organisation.
 */
class OrganisationIntegrationService implements OrganisationIntegrationInterface
{
    /** @var array<string, class-string<Model>> */
    private const MODELES = [
        'DIRECTION' => Direction::class,
        'SERVICE' => Service::class,
        'UNITE' => Unite::class,
        'POSTE' => PosteTravail::class,
    ];

    public function cibles(string $typeCible): array
    {
        $modele = self::MODELES[$typeCible] ?? null;

        if (! $modele) {
            return [];
        }

        return $modele::query()
            ->orderBy('libelle')
            ->get(['id', 'libelle'])
            ->map(fn (Model $cible) => ['id' => $cible->id, 'libelle' => $cible->libelle])
            ->all();
    }

    public function cibleExiste(string $typeCible, int $cibleId): bool
    {
        $modele = self::MODELES[$typeCible] ?? null;

        return $modele ? $modele::query()->whereKey($cibleId)->exists() : false;
    }

    public function libelleCible(string $typeCible, int $cibleId): ?string
    {
        $modele = self::MODELES[$typeCible] ?? null;

        return $modele ? $modele::query()->whereKey($cibleId)->value('libelle') : null;
    }
}
