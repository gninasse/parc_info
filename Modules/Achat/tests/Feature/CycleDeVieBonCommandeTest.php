<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Exceptions\TransitionInterditeException;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Core\Models\User;
use Tests\TestCase;

/**
 * Cycle de vie du bon de commande (SFD §1.4) — la machine à états.
 *
 * Règles vérifiées : on ne modifie qu'un brouillon ; on n'annule qu'un bon
 * sans aucune réception ; on ne clôture qu'un partiel ; toute transition
 * illégale lève un 409 explicite plutôt que d'écrire un état incohérent.
 */
class CycleDeVieBonCommandeTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateur(): User
    {
        return User::create([
            'name' => 'Test', 'last_name' => 'User', 'user_name' => 'user_cycle',
            'email' => 'cycle@example.com', 'password' => bcrypt('password'),
        ]);
    }

    // ── Présentation ───────────────────────────────────────────────────────

    public function test_un_brouillon_s_affiche_avec_son_id_et_un_valide_avec_son_numero(): void
    {
        $brouillon = BonCommande::factory()->create();
        $this->assertSame('Brouillon #'.$brouillon->id, $brouillon->numero_affiche);

        $valide = BonCommande::factory()->valide()->create(['numero' => 'BC-2026-0041']);
        $this->assertSame('BC-2026-0041', $valide->numero_affiche);
    }

    /** ⚠ DESIGN.md : SOUMIS est JAUNE — « verrouillé, en cours d'officialisation ». */
    public function test_le_statut_soumis_est_jaune(): void
    {
        $bon = BonCommande::factory()->soumis()->create();

        $this->assertSame('warning', $bon->statut_couleur);
        $this->assertSame('Soumis', $bon->statut_label);
    }

    // ── Soumission ─────────────────────────────────────────────────────────

    public function test_un_brouillon_peut_etre_soumis(): void
    {
        $bon = BonCommande::factory()->create();
        $utilisateur = $this->utilisateur();

        $bon->soumettre($utilisateur->id);

        $bon->refresh();
        $this->assertSame(BonCommande::STATUT_SOUMIS, $bon->statut);
        $this->assertSame($utilisateur->id, $bon->soumis_par);
        $this->assertNotNull($bon->soumis_le);
        // La soumission n'attribue JAMAIS de numéro (SFD §1.4)
        $this->assertNull($bon->numero);
    }

    public function test_un_bon_deja_soumis_ne_peut_pas_etre_resoumis(): void
    {
        $bon = BonCommande::factory()->soumis()->create();

        $this->expectException(TransitionInterditeException::class);

        $bon->soumettre($this->utilisateur()->id);
    }

    public function test_un_bon_soumis_est_verrouille(): void
    {
        $bon = BonCommande::factory()->soumis()->create();

        $this->assertFalse($bon->estModifiable());
        $this->assertFalse($bon->estSupprimable());
        $this->assertNotNull($bon->diagnosticModification());
    }

    // ── Renvoi et reprise ──────────────────────────────────────────────────

    public function test_un_bon_soumis_revient_en_brouillon_et_perd_sa_trace_de_soumission(): void
    {
        $bon = BonCommande::factory()->soumis()->create(['soumis_par' => $this->utilisateur()->id]);

        $bon->renvoyerEnBrouillon();

        $bon->refresh();
        $this->assertSame(BonCommande::STATUT_BROUILLON, $bon->statut);
        $this->assertNull($bon->soumis_par);
        $this->assertNull($bon->soumis_le);
        $this->assertTrue($bon->estModifiable());
    }

    public function test_un_bon_valide_ne_peut_pas_revenir_en_brouillon(): void
    {
        $bon = BonCommande::factory()->valide()->create();

        $this->expectException(TransitionInterditeException::class);

        $bon->renvoyerEnBrouillon();
    }

    // ── Annulation (SFD §7.5) ──────────────────────────────────────────────

    public function test_un_bon_valide_sans_reception_peut_etre_annule(): void
    {
        $bon = BonCommande::factory()->valide()->create();
        LigneCommande::factory()->create(['bon_commande_id' => $bon->id, 'quantite' => 5]);

        $this->assertTrue($bon->estAnnulable());

        $bon->annuler('Commande passée en double');

        $bon->refresh();
        $this->assertSame(BonCommande::STATUT_ANNULE, $bon->statut);
        $this->assertSame('Commande passée en double', $bon->motif_annulation);
    }

    /** Le cœur de la règle : ce qui a été livré ne s'annule pas. */
    public function test_un_bon_deja_receptionne_ne_peut_plus_etre_annule(): void
    {
        $bon = BonCommande::factory()->partiel()->create();
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id, 'quantite' => 10, 'quantite_livree' => 4,
        ]);

        $this->assertFalse($bon->estAnnulable());

        $this->expectException(TransitionInterditeException::class);

        $bon->annuler('Tentative tardive');
    }

    public function test_un_brouillon_ne_s_annule_pas_il_se_supprime(): void
    {
        $bon = BonCommande::factory()->create();

        $this->assertFalse($bon->estAnnulable());
        $this->assertTrue($bon->estSupprimable());

        $this->expectException(TransitionInterditeException::class);

        $bon->annuler('Motif');
    }

    // ── Clôture (SFD §7.5) ─────────────────────────────────────────────────

    public function test_un_bon_partiel_peut_etre_cloture_avec_motif(): void
    {
        $bon = BonCommande::factory()->partiel()->create();

        $this->assertTrue($bon->estCloturable());

        $bon->cloturer('Fournisseur en rupture définitive');

        $bon->refresh();
        $this->assertSame(BonCommande::STATUT_CLOTURE, $bon->statut);
        $this->assertSame('Fournisseur en rupture définitive', $bon->motif_cloture);
        // Le numéro et les réceptions sont conservés
        $this->assertNotNull($bon->numero);
    }

    public function test_un_bon_valide_sans_reception_n_est_pas_cloturable(): void
    {
        $bon = BonCommande::factory()->valide()->create();

        $this->assertFalse($bon->estCloturable());

        $this->expectException(TransitionInterditeException::class);

        $bon->cloturer('Motif');
    }

    // ── Reliquat porté par les lignes (SFD §1.2) ───────────────────────────

    public function test_le_reste_d_une_ligne_est_la_difference_commandee_livree(): void
    {
        $ligne = LigneCommande::factory()->create(['quantite' => 10, 'quantite_livree' => 6]);

        $this->assertEqualsWithDelta(4, $ligne->reste, 0.001);
        $this->assertSame(60, $ligne->progression);
        $this->assertFalse($ligne->estSoldee());
    }

    public function test_une_ligne_entierement_livree_est_soldee(): void
    {
        $ligne = LigneCommande::factory()->create(['quantite' => 10, 'quantite_livree' => 10]);

        $this->assertEqualsWithDelta(0, $ligne->reste, 0.001);
        $this->assertSame(100, $ligne->progression);
        $this->assertTrue($ligne->estSoldee());
    }

    // ── Droits métier par statut (SPEC_UX §0.3) ────────────────────────────

    public function test_seul_un_brouillon_est_modifiable_et_supprimable(): void
    {
        $cas = [
            [BonCommande::factory()->create(), true],
            [BonCommande::factory()->soumis()->create(), false],
            [BonCommande::factory()->valide()->create(), false],
            [BonCommande::factory()->partiel()->create(), false],
            [BonCommande::factory()->livre()->create(), false],
            [BonCommande::factory()->cloture()->create(), false],
            [BonCommande::factory()->annule()->create(), false],
        ];

        foreach ($cas as [$bon, $attendu]) {
            $this->assertSame($attendu, $bon->estModifiable(), "Modifiable ? statut {$bon->statut}");
            $this->assertSame($attendu, $bon->estSupprimable(), "Supprimable ? statut {$bon->statut}");
        }
    }

    /**
     * ÉCART SIGNALÉ À LA MOA : le SFD §1.4 annonce « ANNULE — numéro jamais
     * attribué », mais §7.5 n'autorise l'annulation que depuis VALIDE, qui
     * porte déjà un numéro. Retirer ce numéro creuserait un trou dans la
     * séquence (contraire à IA-3) et effacerait la trace d'un document qui a
     * pu circuler. Le comportement retenu conserve donc le numéro ; ce test
     * documente l'arbitrage et échouera si la MOA tranche autrement.
     */
    public function test_un_bon_annule_conserve_le_numero_qu_il_avait_deja_recu(): void
    {
        $bon = BonCommande::factory()->valide()->create();
        $numeroInitial = $bon->numero;

        $bon->annuler('Commande passée en double');

        $bon->refresh();
        $this->assertSame(BonCommande::STATUT_ANNULE, $bon->statut);
        $this->assertSame($numeroInitial, $bon->numero, 'Le numéro engagé reste la trace du document.');
    }

    /** Un bon jamais validé, lui, n'a effectivement aucun numéro. */
    public function test_un_bon_annule_sans_validation_n_a_pas_de_numero(): void
    {
        $bon = BonCommande::factory()->annule()->create();

        $this->assertNull($bon->numero);
        $this->assertSame('Brouillon #'.$bon->id, $bon->numero_affiche);
    }

    /** Les régularisations sont exclues des statistiques par défaut (§7.6). */
    public function test_le_scope_hors_regularisation_ecarte_les_bons_d_interim(): void
    {
        BonCommande::factory()->valide()->count(2)->create();
        BonCommande::factory()->valide()->regularisation()->create();

        $this->assertSame(3, BonCommande::query()->engages()->count());
        $this->assertSame(2, BonCommande::query()->engages()->horsRegularisation()->count());
    }

    public function test_le_scope_a_valider_ne_retient_que_les_soumis(): void
    {
        BonCommande::factory()->soumis()->count(3)->create();
        BonCommande::factory()->create();
        BonCommande::factory()->valide()->create();

        $this->assertSame(3, BonCommande::query()->aValider()->count());
    }

    public function test_le_scope_receptionnables_couvre_valide_et_partiel(): void
    {
        BonCommande::factory()->valide()->create();
        BonCommande::factory()->partiel()->create();
        BonCommande::factory()->livre()->create();
        BonCommande::factory()->cloture()->create();

        $this->assertSame(2, BonCommande::query()->receptionnables()->count());
    }
}
