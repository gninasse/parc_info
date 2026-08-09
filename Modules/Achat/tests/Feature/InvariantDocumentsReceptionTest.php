<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\IntegrationReception;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Magasin;
use Tests\TestCase;

/**
 * BR-05 — IA-16 et la matrice de permissions CROISÉE.
 *
 * Le lot BR a ouvert une porte entre deux modules : Achat sert désormais des
 * fichiers qui appartiennent à Stock. C'est utile (un acheteur consulte le BL
 * de sa commande sans accès magasin) et c'est exactement le genre de commodité
 * qui devient une faille quand personne ne la surveille.
 *
 * D'où l'invariant IA-16, énoncé dans le recueil :
 *
 *     « Un document de réception n'est JAMAIS accessible sans la permission
 *       du MODULE CONSULTÉ. »
 *
 * Autrement dit : les droits Stock n'ouvrent pas les routes d'Achat, les
 * droits d'Achat n'ouvrent pas celles de Stock, et dans les deux cas le
 * rattachement de la pièce au dossier consulté est vérifié. Ces tests
 * balaient la matrice complète, chaque profil contre chaque route.
 *
 * Ils sont volontairement redondants avec DocumentsReceptionTest : celui-là
 * teste la fonctionnalité, celui-ci tient la frontière. Une régression de
 * sécurité doit faire tomber un test dont le nom dit « sécurité ».
 */
class InvariantDocumentsReceptionTest extends TestCase
{
    use RefreshDatabase;

    private Magasin $magasin;

    private BonCommande $bon;

    private Entree $entree;

    private Document $piece;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        Storage::fake(Document::DISQUE);

        $this->magasin = Magasin::factory()->create();
        $this->bon = BonCommande::factory()->valide()->create();

        $this->entree = Entree::factory()->validee()->create([
            'magasin_id' => $this->magasin->id,
            'bon_commande_id' => $this->bon->id,
            'fournisseur_id' => $this->bon->fournisseur_id,
        ]);

        IntegrationReception::create([
            'bon_commande_id' => $this->bon->id,
            'entree_id' => $this->entree->id,
            'sens' => IntegrationReception::SENS_RECEPTION,
            'reference' => $this->entree->numero,
            'detail' => [],
        ]);

        Storage::disk(Document::DISQUE)->put('stock/documents/entrees/bl.pdf', '%PDF-fictif');

        $this->piece = $this->entree->documents()->create([
            'type' => Document::TYPE_BL_FOURNISSEUR,
            'nom_original' => 'bl-fournisseur.pdf',
            'chemin' => 'stock/documents/entrees/bl.pdf',
            'mime' => 'application/pdf',
            'taille' => 11,
        ]);
    }

    private function profil(string $cle, array $permissions): User
    {
        $user = User::create([
            'name' => 'Profil '.$cle,
            'last_name' => 'Test',
            'user_name' => 'profil_'.$cle,
            'email' => $cle.'@example.com',
            'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    /** Les quatre routes que le lot BR a ouvertes ou touchées. */
    private function routes(): array
    {
        return [
            'proxy Achat — pièce' => route('achat.bons-commande.receptions.documents', [
                $this->bon->id, $this->entree->id, $this->piece->id,
            ]),
            'proxy Achat — bordereau' => route('achat.bons-commande.receptions.bordereau', [
                $this->bon->id, $this->entree->id,
            ]),
            'Stock — pièce' => route('stock.documents.download', [
                'entrees', $this->entree->id, $this->piece->id,
            ]),
            'Stock — bordereau' => route('stock.entrees.bordereau-reception', $this->entree->id),
        ];
    }

    /**
     * LA matrice. Pour chaque profil, ce qui doit s'ouvrir et ce qui doit se
     * fermer — un tableau qu'un relecteur peut vérifier des yeux.
     */
    public static function matrice(): array
    {
        return [
            'acheteur pur (aucun droit Stock)' => [
                ['achat.bons_commande.index', 'achat.documents.view'],
                ['proxy Achat — pièce' => 200, 'proxy Achat — bordereau' => 200,
                    'Stock — pièce' => 403, 'Stock — bordereau' => 403],
            ],
            'acheteur sans droit sur les pièces' => [
                ['achat.bons_commande.index'],
                ['proxy Achat — pièce' => 403, 'proxy Achat — bordereau' => 403,
                    'Stock — pièce' => 403, 'Stock — bordereau' => 403],
            ],
            'magasinier pur (aucun droit Achat)' => [
                ['stock.entrees.index'],
                ['proxy Achat — pièce' => 403, 'proxy Achat — bordereau' => 403,
                    'Stock — pièce' => 200, 'Stock — bordereau' => 200],
            ],
            'les deux casquettes' => [
                ['achat.bons_commande.index', 'achat.documents.view', 'stock.entrees.index'],
                ['proxy Achat — pièce' => 200, 'proxy Achat — bordereau' => 200,
                    'Stock — pièce' => 200, 'Stock — bordereau' => 200],
            ],
            'aucun droit' => [
                [],
                ['proxy Achat — pièce' => 403, 'proxy Achat — bordereau' => 403,
                    'Stock — pièce' => 403, 'Stock — bordereau' => 403],
            ],
        ];
    }

    /**
     * @dataProvider matrice
     */
    public function test_ia16_matrice_croisee_des_permissions(array $permissions, array $attendus): void
    {
        $utilisateur = $this->profil('m'.substr(md5(implode(',', $permissions)), 0, 8), $permissions);
        $routes = $this->routes();

        foreach ($attendus as $libelle => $statutAttendu) {
            $this->actingAs($utilisateur)
                ->get($routes[$libelle])
                ->assertStatus($statutAttendu, sprintf(
                    '%s : attendu %d avec les permissions [%s].',
                    $libelle,
                    $statutAttendu,
                    implode(', ', $permissions) ?: 'aucune'
                ));
        }
    }

    /**
     * Le second verrou d'IA-16 : la permission ne suffit pas, le RATTACHEMENT
     * doit être établi. Un acheteur pleinement habilité ne lit pas les pièces
     * d'une commande qui n'est pas la sienne — sinon la route deviendrait un
     * moyen de parcourir tout le magasin, permission en poche.
     */
    public function test_ia16_la_permission_ne_suffit_pas_sans_rattachement(): void
    {
        $acheteur = $this->profil('rattachement', [
            'achat.bons_commande.index',
            'achat.documents.view',
        ]);

        // Une entrée d'un AUTRE bon, avec sa propre pièce.
        $autreBon = BonCommande::factory()->valide()->create();
        $autreEntree = Entree::factory()->validee()->create([
            'magasin_id' => $this->magasin->id,
            'bon_commande_id' => $autreBon->id,
        ]);
        $autrePiece = $autreEntree->documents()->create([
            'type' => Document::TYPE_BL_FOURNISSEUR,
            'nom_original' => 'bl-autre.pdf',
            'chemin' => 'stock/documents/entrees/bl.pdf',
            'mime' => 'application/pdf',
            'taille' => 11,
        ]);

        // Identifiants tous VRAIS, seul le rattachement manque.
        $this->actingAs($acheteur)
            ->get(route('achat.bons-commande.receptions.documents', [
                $this->bon->id, $autreEntree->id, $autrePiece->id,
            ]))
            ->assertNotFound();

        $this->actingAs($acheteur)
            ->get(route('achat.bons-commande.receptions.bordereau', [
                $this->bon->id, $autreEntree->id,
            ]))
            ->assertNotFound();

        // Et sur SON dossier, tout s'ouvre : le refus vient bien du
        // rattachement, pas d'un blocage général.
        $this->actingAs($acheteur)
            ->get(route('achat.bons-commande.receptions.documents', [
                $this->bon->id, $this->entree->id, $this->piece->id,
            ]))
            ->assertOk();
    }

    /**
     * Aucune URL du disque n'existe : les fichiers vivent hors racine web et
     * ne sont servis que par les routes contrôlées. Un chemin qui fuiterait
     * rendrait toute la matrice ci-dessus décorative.
     */
    public function test_ia16_aucun_fichier_n_est_atteignable_hors_des_routes(): void
    {
        $this->assertStringNotContainsString(
            'public',
            config('filesystems.disks.'.Document::DISQUE.'.root'),
            'Le disque des pièces ne doit pas être exposé par le serveur web.'
        );

        // La fiche du bon ne rend AUCUN chemin de stockage, seulement des
        // routes d'Achat.
        $acheteur = $this->profil('fuite', ['achat.bons_commande.index', 'achat.documents.view']);

        $html = $this->actingAs($acheteur)
            ->get(route('achat.bons-commande.show', $this->bon->id))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString($this->piece->chemin, $html);
        $this->assertStringNotContainsString('/storage/', $html);
    }

    /** Les routes du lot BR sont toutes déclarées sous `auth`. */
    public function test_les_routes_de_reception_exigent_une_session(): void
    {
        foreach ($this->routes() as $libelle => $url) {
            $this->get($url)->assertRedirect(route('login'), "{$libelle} doit renvoyer au login.");
        }
    }

    /** Aucune des routes BR n'accepte d'écriture : ce sont des lectures. */
    public function test_les_routes_de_reception_sont_en_lecture_seule(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (! str_contains((string) $route->getName(), 'receptions.')) {
                continue;
            }

            $this->assertSame(
                ['GET', 'HEAD'],
                $route->methods(),
                "La route {$route->getName()} ne doit servir qu'à lire."
            );
        }
    }
}
