<?php

namespace Modules\Stock\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Stock\Exceptions\TransitionInterditeException;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Inventaire;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Models\Transfert;
use Tests\TestCase;

/**
 * S3 — machines à états des documents : capacités par statut et TOUTES les
 * transitions interdites → TransitionInterditeException (transformée en 409
 * par les contrôleurs).
 */
class MachineEtatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_capacites_du_brouillon(): void
    {
        $entree = Entree::factory()->create();

        $this->assertTrue($entree->canEdit());
        $this->assertTrue($entree->canDelete());
        $this->assertTrue($entree->canValidate());
        $this->assertFalse($entree->verrouille());
    }

    public function test_capacites_en_phase_de_references(): void
    {
        $entree = Entree::factory()->enReferencement()->create();
        $sortie = Sortie::factory()->enPointage()->create();

        // I16 : quantités verrouillées, mais reprise et suppression possibles
        foreach ([$entree, $sortie] as $document) {
            $this->assertFalse($document->canEdit());
            $this->assertTrue($document->canDelete());
            $this->assertTrue($document->canValidate());
            $this->assertTrue($document->verrouille());
        }
    }

    public function test_capacites_du_document_valide(): void
    {
        $entree = Entree::factory()->validee()->create();

        $this->assertTrue($entree->estValide());
        $this->assertFalse($entree->canEdit());
        $this->assertFalse($entree->canDelete());
        $this->assertFalse($entree->canValidate());
        $this->assertFalse($entree->verrouille());
    }

    public function test_capacites_du_document_annule(): void
    {
        $entree = Entree::factory()->annulee()->create();

        $this->assertFalse($entree->canEdit());
        $this->assertFalse($entree->canDelete());
        $this->assertFalse($entree->canValidate());
    }

    public function test_transitions_nominales_du_triptyque_entree(): void
    {
        $entree = Entree::factory()->create();

        $entree->passerEnReferencement();
        $this->assertSame(Entree::STATUT_REFERENCEMENT, $entree->fresh()->statut);

        $entree->retourBrouillon();
        $this->assertSame(Entree::STATUT_BROUILLON, $entree->fresh()->statut);

        $entree->valider(validePar: null);
        $entree->refresh();
        $this->assertSame(Entree::STATUT_VALIDE, $entree->statut);
        $this->assertNotNull($entree->valide_le);
    }

    public function test_transitions_nominales_sortie_et_transfert(): void
    {
        $sortie = Sortie::factory()->create();
        $sortie->passerEnPointage();
        $this->assertSame(Sortie::STATUT_POINTAGE, $sortie->fresh()->statut);

        $transfert = Transfert::factory()->create();
        $transfert->passerEnPointage();
        $transfert->valider();
        $this->assertSame(Transfert::STATUT_VALIDE, $transfert->fresh()->statut);
    }

    /** @return array<string, array{0: callable}> */
    public static function transitionsInterdites(): array
    {
        return [
            'valider un VALIDE' => [fn () => Entree::factory()->validee()->create()->valider()],
            'annuler un VALIDE' => [fn () => Entree::factory()->validee()->create()->annuler()],
            'retour brouillon depuis BROUILLON' => [fn () => Entree::factory()->create()->retourBrouillon()],
            'retour brouillon depuis VALIDE' => [fn () => Entree::factory()->validee()->create()->retourBrouillon()],
            'référencement depuis VALIDE' => [fn () => Entree::factory()->validee()->create()->passerEnReferencement()],
            'référencement depuis RÉFÉRENCEMENT' => [fn () => Entree::factory()->enReferencement()->create()->passerEnReferencement()],
            'valider un ANNULE' => [fn () => Entree::factory()->annulee()->create()->valider()],
            'annuler un ANNULE' => [fn () => Entree::factory()->annulee()->create()->annuler()],
            'pointage depuis VALIDE (sortie)' => [fn () => Sortie::factory()->validee()->create()->passerEnPointage()],
            'pointage depuis POINTAGE (sortie)' => [fn () => Sortie::factory()->enPointage()->create()->passerEnPointage()],
            'annuler un VALIDE (sortie)' => [fn () => Sortie::factory()->validee()->create()->annuler()],
            'pointage depuis VALIDE (transfert)' => [fn () => Transfert::factory()->validee()->create()->passerEnPointage()],
        ];
    }

    /** @dataProvider transitionsInterdites */
    public function test_transition_interdite_leve_une_exception(callable $transition): void
    {
        $this->expectException(TransitionInterditeException::class);
        $transition();
    }

    public function test_statut_conserve_apres_transition_interdite(): void
    {
        $entree = Entree::factory()->validee()->create();

        try {
            $entree->annuler();
            $this->fail('La transition aurait dû être refusée.');
        } catch (TransitionInterditeException $e) {
            $this->assertSame(409, $e->status());
        }

        $this->assertSame(Entree::STATUT_VALIDE, $entree->fresh()->statut);
    }

    public function test_machine_a_etats_inventaire(): void
    {
        $inventaire = Inventaire::factory()->create();

        $this->assertTrue($inventaire->canEdit());
        $this->assertTrue($inventaire->canValidate());
        $this->assertFalse($inventaire->canDelete()); // jamais de suppression

        $inventaire->valider();
        $this->assertSame(Inventaire::STATUT_VALIDE, $inventaire->fresh()->statut);

        $this->expectException(TransitionInterditeException::class);
        $inventaire->annuler();
    }

    public function test_inventaire_annulation_nominale(): void
    {
        $inventaire = Inventaire::factory()->create();
        $inventaire->annuler();

        $this->assertSame(Inventaire::STATUT_ANNULE, $inventaire->fresh()->statut);

        $this->expectException(TransitionInterditeException::class);
        $inventaire->valider();
    }
}
