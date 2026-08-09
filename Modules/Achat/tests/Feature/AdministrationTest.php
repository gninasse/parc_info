<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Http\Controllers\AdministrationController;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Models\Parametre;
use Modules\Achat\Services\AchatParametres;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\Activity;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-17 — l'écran d'administration (A-08).
 *
 * Le critère du recueil : chaque paramètre modifié change le comportement
 * IMMÉDIATEMENT. Un écran qui enregistre sans effet est pire qu'une absence
 * d'écran — il fait croire que le réglage a été pris en compte.
 *
 * Les tests le vérifient donc en BOUT DE CHAÎNE : on change le paramètre,
 * puis on regarde l'écran qu'il pilote.
 */
class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    private User $administrateur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        $this->administrateur = $this->utilisateur([
            'achat.administration.manage',
            'achat.reliquats.index',
            'achat.bons_commande.index',
        ], 'admin-achat@example.com');
    }

    private function utilisateur(array $permissions, string $email): User
    {
        $existant = User::query()->where('email', $email)->first();

        if ($existant !== null) {
            return $existant;
        }

        $user = User::create([
            'name' => 'Utilisateur '.$email,
            'last_name' => 'Test',
            'user_name' => 'user_'.md5($email),
            'email' => $email,
            'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    private function modifier(string $cle, mixed $valeur)
    {
        return $this->actingAs($this->administrateur)
            ->patchJson(route('achat.parametres.modifier', $cle), ['valeur' => $valeur]);
    }

    // ═══ Accès ══════════════════════════════════════════════════════════════

    public function test_l_ecran_exige_la_permission_d_administration(): void
    {
        $sansDroit = $this->utilisateur(['achat.dashboard.view'], 'sans-admin@example.com');

        $this->actingAs($sansDroit)
            ->get(route('achat.administration'))
            ->assertForbidden()
            ->assertSee('achat.administration.manage');

        $this->actingAs($sansDroit)
            ->patchJson(route('achat.parametres.modifier', Parametre::SEUIL_ECART_PRIX_PCT), ['valeur' => 50])
            ->assertForbidden();
    }

    public function test_l_ecran_affiche_les_six_cartes_de_parametres(): void
    {
        $contenu = $this->actingAs($this->administrateur)
            ->get(route('achat.administration'))
            ->assertOk()
            ->getContent();

        foreach ([
            'prefixe_numerotation',
            'delai_alerte_reliquat_jours',
            'seuil_ecart_prix_pct',
            'taille_max_piece_mo',
            'motifs_observation',
        ] as $cle) {
            $this->assertStringContainsString('data-cle="'.$cle.'"', $contenu);
        }

        $this->assertStringContainsString('carte-regularisation', $contenu);
    }

    // ═══ Validation PAR CLÉ ═════════════════════════════════════════════════

    public function test_un_parametre_inconnu_est_un_404(): void
    {
        $this->modifier('parametre_imaginaire', 42)->assertNotFound();
    }

    public function test_le_prefixe_refuse_les_caracteres_hors_norme(): void
    {
        // Court, pour que ce soit bien le FORMAT qui soit jugé (et non la
        // longueur, dont la règle parlerait la première).
        $reponse = $this->modifier(Parametre::PREFIXE_NUMEROTATION, 'bc !')
            ->assertUnprocessable();

        // Le message dit CE QUI est accepté, pas seulement que c'est refusé.
        $this->assertStringContainsString('majuscules', $reponse->json('message'));

        // Et la longueur a son propre message, tout aussi explicite.
        $this->assertStringContainsString(
            '8 caractères',
            $this->modifier(Parametre::PREFIXE_NUMEROTATION, 'TROPLONGPREFIXE')
                ->assertUnprocessable()
                ->json('message')
        );

        $this->modifier(Parametre::PREFIXE_NUMEROTATION, 'BC')->assertOk();
    }

    public function test_le_seuil_d_ecart_refuse_zero_avec_son_explication(): void
    {
        $reponse = $this->modifier(Parametre::SEUIL_ECART_PRIX_PCT, 0)
            ->assertUnprocessable();

        $this->assertStringContainsString('se noierait', $reponse->json('message'));
    }

    public function test_le_delai_de_reliquat_refuse_au_dela_d_un_an(): void
    {
        $this->modifier(Parametre::DELAI_ALERTE_RELIQUAT_JOURS, 400)->assertUnprocessable();
        $this->modifier(Parametre::DELAI_ALERTE_RELIQUAT_JOURS, 0)->assertUnprocessable();
        $this->modifier(Parametre::DELAI_ALERTE_RELIQUAT_JOURS, 45)->assertOk();
    }

    public function test_les_motifs_refusent_une_liste_vide(): void
    {
        $this->modifier(Parametre::MOTIFS_OBSERVATION, [])->assertUnprocessable();
        $this->modifier(Parametre::MOTIFS_OBSERVATION, ['Urgence', 'Marché en cours'])->assertOk();

        $this->assertSame(
            ['Urgence', 'Marché en cours'],
            app(AchatParametres::class)->motifsObservation()
        );
    }

    // ═══ LE critère : l'effet est IMMÉDIAT, en bout de chaîne ═══════════════

    /**
     * Le délai pilote les badges d'âge de l'écran Reliquats : on le change,
     * et la COULEUR du badge change, sans redéploiement ni vidage de cache.
     */
    public function test_changer_le_delai_change_la_couleur_des_badges_de_reliquats(): void
    {
        $bon = BonCommande::factory()->valide()->create(['valide_le' => now()->subDays(40)]);
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => Article::factory()->consommable()->create()->id,
            'quantite' => 10,
            'quantite_livree' => 0,
        ]);

        $couleur = fn () => $this->actingAs($this->administrateur)
            ->getJson(route('achat.reliquats.data'))
            ->json('rows.0.age_couleur');

        $this->modifier(Parametre::DELAI_ALERTE_RELIQUAT_JOURS, 30)->assertOk();
        $this->assertSame('danger', $couleur(), '40 jours au-delà d\'un seuil de 30 : rouge.');

        $this->modifier(Parametre::DELAI_ALERTE_RELIQUAT_JOURS, 200)->assertOk();
        $this->assertSame('success', $couleur(), 'Les mêmes 40 jours sous un seuil de 200 : vert.');
    }

    /** Le seuil d'écart pilote les signaux de prix, sur-le-champ. */
    public function test_changer_le_seuil_d_ecart_change_le_diagnostic_de_prix(): void
    {
        $article = Article::factory()->consommable()->create();

        // Un bon payé 100 000, puis un second à 120 000 : +20 %.
        $premier = BonCommande::factory()->valide()->create();
        LigneCommande::factory()->create([
            'bon_commande_id' => $premier->id,
            'article_id' => $article->id,
            'quantite' => 1,
            'prix_unitaire_ht' => 100000,
        ]);

        $reference = app(\Modules\Achat\Services\ReferencePrixService::class);

        $this->modifier(Parametre::SEUIL_ECART_PRIX_PCT, 10)->assertOk();
        $this->assertTrue($reference->ecart($article->id, 120000)['depasse_seuil']);

        $this->modifier(Parametre::SEUIL_ECART_PRIX_PCT, 50)->assertOk();
        $this->assertFalse(
            $reference->ecart($article->id, 120000)['depasse_seuil'],
            'Sous un seuil de 50 %, un écart de 20 % ne doit plus être signalé.'
        );
    }

    /** La taille max pilote le dépôt des pièces : 422 immédiat au-delà. */
    public function test_changer_la_taille_max_change_le_refus_de_depot(): void
    {
        \Illuminate\Support\Facades\Storage::fake(\Modules\Achat\Services\DocumentsBonCommande::DISQUE);

        $deposant = $this->utilisateur([
            'achat.bons_commande.index',
            'achat.documents.store',
        ], 'deposant-admin@example.com');

        $bon = BonCommande::factory()->create();

        $deposer = fn () => $this->actingAs($deposant)
            ->postJson(route('achat.bons-commande.documents.store', $bon->id), [
                'type' => 'autre',
                'fichier' => \Illuminate\Http\UploadedFile::fake()->create('piece.pdf', 3072, 'application/pdf'), // 3 Mo
            ]);

        $this->modifier(Parametre::TAILLE_MAX_PIECE_MO, 2)->assertOk();
        $deposer()->assertUnprocessable();

        $this->modifier(Parametre::TAILLE_MAX_PIECE_MO, 10)->assertOk();
        $deposer()->assertOk();
    }

    /** Le préfixe pilote la numérotation : le prochain bon le porte. */
    public function test_changer_le_prefixe_change_le_numero_attribue(): void
    {
        $this->modifier(Parametre::PREFIXE_NUMEROTATION, 'CMD')->assertOk();

        $validateur = $this->utilisateur([
            'achat.bons_commande.index',
            'achat.bons_commande.valider',
        ], 'validateur-admin@example.com');

        $bon = BonCommande::factory()->create(['statut' => BonCommande::STATUT_SOUMIS]);
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => Article::factory()->consommable()->create()->id,
            'quantite' => 1,
            'prix_unitaire_ht' => 1000,
        ]);

        app(\Modules\Achat\Services\VisaService::class)->valider($bon->refresh(), $validateur);

        $this->assertStringStartsWith('CMD-', $bon->fresh()->numero);
    }

    public function test_l_apercu_du_numero_vient_du_serveur(): void
    {
        $reponse = $this->modifier(Parametre::PREFIXE_NUMEROTATION, 'ACH')->assertOk();

        $this->assertSame('ACH-'.now()->year.'-0042', $reponse->json('data.apercu'));
    }

    // ═══ Le journal : l'ancienne ET la nouvelle valeur ══════════════════════

    public function test_chaque_modification_journalise_les_deux_valeurs(): void
    {
        app(AchatParametres::class)->set(Parametre::SEUIL_ECART_PRIX_PCT, 20);

        $this->modifier(Parametre::SEUIL_ECART_PRIX_PCT, 35)->assertOk();

        $activite = Activity::query()
            ->forModule('achat')
            ->where('description', AdministrationController::EVENEMENT_PARAMETRE)
            ->latest('id')
            ->first();

        $this->assertNotNull($activite, 'Un comportement qui change sans trace est un incident en puissance.');
        $this->assertSame(Parametre::SEUIL_ECART_PRIX_PCT, $activite->properties->get('cle'));
        // Savoir que le seuil vaut 35 ne sert à rien si on ignore qu'il valait 20.
        $this->assertSame(20, $activite->properties->get('ancienne_valeur'));
        $this->assertSame(35, $activite->properties->get('nouvelle_valeur'));
        $this->assertSame($this->administrateur->id, $activite->causer_id);
    }

    public function test_le_journal_supporte_les_valeurs_composees(): void
    {
        $this->modifier(Parametre::MOTIFS_OBSERVATION, ['Urgence', 'Dotation'])->assertOk();

        $activite = Activity::query()
            ->forModule('achat')
            ->where('description', AdministrationController::EVENEMENT_PARAMETRE)
            ->latest('id')
            ->first();

        $this->assertStringContainsString('Urgence', $activite->properties->get('nouvelle_valeur'));
    }

    // ═══ La régularisation, vue de l'administration ════════════════════════

    public function test_l_ecran_affiche_l_etat_de_la_porte_et_la_dette(): void
    {
        app(AchatParametres::class)->set(Parametre::REGULARISATION_ACTIVE, false);
        \Modules\Stock\Database\Factories\ParcInfoDeTest::equipement();

        $contenu = $this->actingAs($this->administrateur)
            ->get(route('achat.administration'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Fermée', $contenu);
        $this->assertStringContainsString('Dette : 1 équipement(s) sans origine', $contenu);
        // Fermée : le bouton de réouverture est offert (SW-06).
        $this->assertStringContainsString('btn-rouvrir-regularisation', $contenu);
    }

    public function test_le_bouton_de_reouverture_est_absent_quand_la_porte_est_ouverte(): void
    {
        app(AchatParametres::class)->set(Parametre::REGULARISATION_ACTIVE, true);

        $contenu = $this->actingAs($this->administrateur)
            ->get(route('achat.administration'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('btn-rouvrir-regularisation', $contenu);
        $this->assertStringContainsString('Elle se fermera d\'elle-même', $contenu);
    }

    public function test_l_interrupteur_de_regularisation_se_pilote_aussi_par_le_patch(): void
    {
        $this->modifier(Parametre::REGULARISATION_ACTIVE, false)->assertOk();
        $this->assertFalse(app(AchatParametres::class)->regularisationActive());

        $this->modifier(Parametre::REGULARISATION_ACTIVE, true)->assertOk();
        $this->assertTrue(app(AchatParametres::class)->regularisationActive());
    }
}
