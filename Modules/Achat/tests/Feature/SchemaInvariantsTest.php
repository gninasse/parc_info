<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\Document;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Models\ReceptionLicences;
use Modules\Achat\Models\TamponLicence;
use Modules\Catalogue\Models\Article;
use Tests\TestCase;

/**
 * Invariants portés par le SCHÉMA (SFD §6.2/§9.4) : ce que la base refuse,
 * indépendamment de l'applicatif — le « double filet ». Ces tests protègent
 * IA-2 (valeurs figées), IA-3 (numérotation), IA-4 (plafond de réception),
 * IA-13 (pierre tombale).
 */
class SchemaInvariantsTest extends TestCase
{
    use RefreshDatabase;

    private function bonValide(): BonCommande
    {
        return BonCommande::factory()->valide()->create();
    }

    // ── IA-4 : le plafond de réception est gardé par la base ───────────────

    public function test_une_ligne_ne_peut_pas_etre_livree_au_dela_du_commande(): void
    {
        $ligne = LigneCommande::factory()->create(['quantite' => 10]);

        $this->expectException(QueryException::class);

        // Écriture directe : même en contournant l'applicatif, la base refuse.
        DB::table('achat_lignes_commande')
            ->where('id', $ligne->id)
            ->update(['quantite_livree' => 11]);
    }

    public function test_une_quantite_livree_negative_est_refusee(): void
    {
        $ligne = LigneCommande::factory()->create(['quantite' => 10]);

        $this->expectException(QueryException::class);

        DB::table('achat_lignes_commande')
            ->where('id', $ligne->id)
            ->update(['quantite_livree' => -1]);
    }

    public function test_une_ligne_de_quantite_nulle_est_refusee(): void
    {
        $this->expectException(QueryException::class);

        LigneCommande::factory()->create(['quantite' => 0]);
    }

    public function test_un_taux_de_tva_hors_bornes_est_refuse(): void
    {
        $this->expectException(QueryException::class);

        LigneCommande::factory()->create(['taux_tva' => 120]);
    }

    // ── IA-3 : un numéro n'existe que pour un bon engagé ───────────────────

    public function test_un_brouillon_ne_peut_pas_porter_de_numero(): void
    {
        $bon = BonCommande::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('achat_bons_commande')
            ->where('id', $bon->id)
            ->update(['numero' => 'BC-2026-0001']);
    }

    public function test_un_bon_valide_sans_numero_est_refuse(): void
    {
        $bon = BonCommande::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('achat_bons_commande')
            ->where('id', $bon->id)
            ->update(['statut' => BonCommande::STATUT_VALIDE]);
    }

    public function test_un_numero_est_unique(): void
    {
        $this->bonValide()->forceFill(['numero' => 'BC-2026-0042'])->save();

        $this->expectException(QueryException::class);

        BonCommande::factory()->valide()->create(['numero' => 'BC-2026-0042']);
    }

    // ── IA-13 : la pierre tombale est toujours motivée ─────────────────────

    public function test_une_piece_supprimee_sans_motif_est_refusee(): void
    {
        $document = Document::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('achat_documents')
            ->where('id', $document->id)
            ->update(['est_supprime' => true, 'supprime_le' => now()]);
    }

    public function test_une_pierre_tombale_complete_est_acceptee(): void
    {
        $document = Document::factory()->pierreTombale('Facture erronée')->create();

        $this->assertTrue($document->est_supprime);
        $this->assertNull($document->chemin);
        $this->assertStringContainsString('Facture erronée', $document->libelle_pierre_tombale);
    }

    // ── Unicité du rattachement de régularisation (A15) ────────────────────

    public function test_un_equipement_n_a_qu_une_seule_commande_d_origine(): void
    {
        $equipement = \Modules\Achat\Tests\ParcInfoDeTest::equipement();
        $premier = BonCommande::factory()->regularisation()->create();
        $second = BonCommande::factory()->regularisation()->create();

        DB::table('achat_regularisation_rattachements')->insert([
            'bon_commande_id' => $premier->id,
            'equipement_id' => $equipement->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('achat_regularisation_rattachements')->insert([
            'bon_commande_id' => $second->id,
            'equipement_id' => $equipement->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ── Tampon des licences ────────────────────────────────────────────────

    public function test_une_cle_de_licence_est_unique_dans_une_session(): void
    {
        $reception = ReceptionLicences::factory()->create();

        TamponLicence::factory()->create(['reception_id' => $reception->id, 'cle' => 'AAAA-1111']);

        $this->expectException(QueryException::class);

        TamponLicence::factory()->create(['reception_id' => $reception->id, 'cle' => 'AAAA-1111']);
    }

    public function test_le_tampon_disparait_avec_sa_session(): void
    {
        $reception = ReceptionLicences::factory()->create();
        TamponLicence::factory()->count(3)->create(['reception_id' => $reception->id]);

        $reception->delete();

        $this->assertSame(0, TamponLicence::where('reception_id', $reception->id)->count());
    }

    // ── Cascade des lignes, restriction des référentiels ───────────────────

    public function test_supprimer_un_brouillon_supprime_ses_lignes(): void
    {
        $bon = BonCommande::factory()->create();
        LigneCommande::factory()->count(3)->create(['bon_commande_id' => $bon->id]);

        $bon->delete();

        $this->assertSame(0, LigneCommande::where('bon_commande_id', $bon->id)->count());
    }

    public function test_un_article_reference_par_une_ligne_ne_peut_pas_etre_supprime(): void
    {
        $article = Article::factory()->create();
        LigneCommande::factory()->create(['article_id' => $article->id]);

        $this->expectException(QueryException::class);

        $article->delete();
    }
}
