<?php

namespace Modules\Catalogue\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\Catalogue\Models\Categorie;
use Tests\TestCase;

class CategorieDataLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_codes_cat_sequentiels_generes_automatiquement(): void
    {
        $a = Categorie::create(['libelle' => 'Informatique']);
        $b = Categorie::create(['libelle' => 'Impression']);

        $this->assertSame('CAT-001', $a->code);
        $this->assertSame('CAT-002', $b->code);
    }

    public function test_chemin_parent_enfant(): void
    {
        $parent = Categorie::create(['libelle' => 'Impression']);
        $enfant = Categorie::create(['libelle' => 'Toners', 'parent_id' => $parent->id]);

        $this->assertSame('Impression', $parent->chemin);
        $this->assertSame('Impression > Toners', $enfant->chemin);
    }

    public function test_une_sous_categorie_ne_peut_pas_avoir_d_enfants(): void
    {
        $racine = Categorie::create(['libelle' => 'Racine']);
        $enfant = Categorie::create(['libelle' => 'Enfant', 'parent_id' => $racine->id]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Profondeur maximale');

        Categorie::create(['libelle' => 'Petit-enfant', 'parent_id' => $enfant->id]);
    }

    public function test_une_categorie_avec_enfants_ne_peut_pas_devenir_sous_categorie(): void
    {
        $racine = Categorie::create(['libelle' => 'Racine']);
        Categorie::create(['libelle' => 'Enfant', 'parent_id' => $racine->id]);
        $autre = Categorie::create(['libelle' => 'Autre racine']);

        try {
            $racine->update(['parent_id' => $autre->id]);
            $this->fail('Le rattachement aurait dû être refusé.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('sous-catégories', $e->getMessage());
        }

        $this->assertNull($racine->fresh()->parent_id);
    }

    public function test_une_categorie_ne_peut_pas_etre_son_propre_parent(): void
    {
        $categorie = Categorie::create(['libelle' => 'Boucle']);

        $this->expectException(InvalidArgumentException::class);

        $categorie->update(['parent_id' => $categorie->id]);
    }

    public function test_scope_actif(): void
    {
        Categorie::create(['libelle' => 'Active']);
        Categorie::create(['libelle' => 'Inactive', 'est_actif' => false]);

        $this->assertSame(['Active'], Categorie::actif()->pluck('libelle')->all());
    }
}
