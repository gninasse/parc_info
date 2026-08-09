<?php

namespace Modules\Achat\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;

/**
 * A-06 — les reliquats : ce que les fournisseurs DOIVENT encore livrer.
 *
 * L'écran répond à une question de gestion précise (celle d'Issouf) :
 * « combien d'argent est engagé sans contrepartie livrée, et depuis quand ».
 * Chaque ligne est une LIGNE DE COMMANDE non soldée d'un bon encore ouvert
 * (VALIDÉ ou PARTIEL) : un bon clôturé ou annulé n'attend plus rien, et un
 * brouillon n'engage rien.
 *
 * L'ÂGE se compte depuis la validation du bon — c'est la date à laquelle
 * l'établissement s'est engagé, donc celle à partir de laquelle le retard
 * se mesure. Les seuils viennent des paramètres (A-08), jamais du code.
 */
class ReliquatsService
{
    /** Requête de base : lignes non soldées des bons encore ouverts. */
    public function requete(Request $request): Builder
    {
        $query = LigneCommande::query()
            ->join('achat_bons_commande', 'achat_bons_commande.id', '=', 'achat_lignes_commande.bon_commande_id')
            ->leftJoin('catalogue_fournisseurs', 'catalogue_fournisseurs.id', '=', 'achat_bons_commande.fournisseur_id')
            ->whereIn('achat_bons_commande.statut', BonCommande::STATUTS_RECEPTIONNABLES)
            ->whereColumn('achat_lignes_commande.quantite_livree', '<', 'achat_lignes_commande.quantite')
            ->select('achat_lignes_commande.*')
            ->with(['bonCommande:id,numero,statut,fournisseur_id,fournisseur_libelle,valide_le,date_document', 'bonCommande.fournisseur:id,raison_sociale']);

        $this->appliquerFiltres($query, $request);

        return $query;
    }

    private function appliquerFiltres(Builder $query, Request $request): void
    {
        if ($request->filled('fournisseur_id')) {
            $query->where('achat_bons_commande.fournisseur_id', (int) $request->input('fournisseur_id'));
        }

        // Pilules d'âge : « > 30 j », « > 60 j », « > N j (paramètre) ».
        if ($request->filled('age_min')) {
            $query->whereNotNull('achat_bons_commande.valide_le')
                ->whereDate('achat_bons_commande.valide_le', '<=', now()->subDays((int) $request->input('age_min')));
        }

        // Recherche « tous formats » : n° de BC, article, fournisseur.
        if ($request->filled('search')) {
            $terme = mb_strtolower(trim($request->input('search')));

            $query->where(function ($sous) use ($terme) {
                $sous->whereRaw('LOWER(achat_bons_commande.numero) LIKE ?', ["%{$terme}%"])
                    ->orWhereRaw('LOWER(achat_lignes_commande.designation) LIKE ?', ["%{$terme}%"])
                    ->orWhereRaw('LOWER(COALESCE(catalogue_fournisseurs.raison_sociale, achat_bons_commande.fournisseur_libelle)) LIKE ?', ["%{$terme}%"]);
            });
        }
    }

    /**
     * Une ligne du tableau A-06.
     *
     * @return array<string, mixed>
     */
    public function presenter(LigneCommande $ligne, int $seuilParametre): array
    {
        $bon = $ligne->bonCommande;
        $age = $bon?->valide_le !== null ? (int) $bon->valide_le->diffInDays(now()) : null;

        return [
            'ligne_id' => $ligne->id,
            'bon_commande_id' => $bon?->id,
            'numero' => $bon?->numero ?? '—',
            'statut' => $bon?->statut,
            'fournisseur' => $bon?->fournisseur_libelle ?? $bon?->fournisseur?->raison_sociale ?? '—',
            'article' => $ligne->designation,
            'nature' => $ligne->nature,
            'quantite' => (float) $ligne->quantite,
            'quantite_livree' => (float) $ligne->quantite_livree,
            'reste' => $ligne->reste,
            // Le montant qui compte : ce qui reste à recevoir, pas le total.
            'montant_reste_ht' => round($ligne->reste * (float) $ligne->prix_unitaire_ht, 2),
            'age_jours' => $age,
            'age_couleur' => $this->couleurAge($age, $seuilParametre),
            'derniere_reception' => $this->derniereReception($ligne),
            'url_bon' => $bon !== null && \Illuminate\Support\Facades\Route::has('achat.bons-commande.show')
                ? route('achat.bons-commande.show', $bon->id)
                : null,
            // Drapeau serveur : la clôture porte sur le BON, pas sur la ligne.
            'peut_cloturer' => $bon?->statut === BonCommande::STATUT_PARTIEL
                && auth()->user()?->can('achat.bons_commande.cloturer'),
        ];
    }

    /**
     * Badge d'âge : vert avant la moitié du délai, orange jusqu'au seuil,
     * rouge au-delà. Le seuil est PARAMÉTRÉ (A-08) : un établissement qui
     * tolère 90 jours ne doit pas voir du rouge à 30.
     */
    private function couleurAge(?int $age, int $seuil): string
    {
        if ($age === null) {
            return 'secondary';
        }

        return match (true) {
            $age >= $seuil => 'danger',
            $age >= (int) ceil($seuil / 2) => 'warning',
            default => 'success',
        };
    }

    /**
     * Date de la dernière réception ayant TOUCHÉ cette ligne — le détail des
     * intégrations porte les articles reçus.
     */
    private function derniereReception(LigneCommande $ligne): ?string
    {
        $integrations = \Modules\Achat\Models\IntegrationReception::query()
            ->receptions()
            ->where('bon_commande_id', $ligne->bon_commande_id)
            ->orderByDesc('created_at')
            ->get(['detail', 'created_at']);

        foreach ($integrations as $integration) {
            foreach ($integration->detail ?? [] as $detail) {
                if ((int) ($detail['ligne_id'] ?? 0) === $ligne->id) {
                    return $integration->created_at?->format('d/m/Y');
                }
            }
        }

        return null;
    }

    /**
     * Le chiffre du pied de page : l'engagé NON LIVRÉ du filtre courant, en
     * TTC (c'est ce que l'établissement devra payer).
     *
     * Calculé par la BASE sur le jeu filtré entier — jamais sur la page
     * affichée (IA-1, UX2-05).
     */
    public function engageNonLivre(Builder $query): float
    {
        /*
         * `/ 100.0` et non `/ 100` : SQLite comme PostgreSQL font une DIVISION
         * ENTIÈRE quand les deux opérandes sont entiers — un taux de 18 %
         * stocké « 18 » donnerait 18/100 = 0, donc un total HT présenté comme
         * du TTC. Le litéral décimal force l'arithmétique flottante partout.
         *
         * `select(...)` REMPLACE la projection de la requête de liste (qui
         * sélectionne les colonnes de la ligne) : l'ajouter donnerait, sur
         * PostgreSQL, « column must appear in the GROUP BY clause ». SQLite
         * l'aurait accepté en silence — c'est exactement le genre d'écart que
         * la suite bi-SGBD existe pour attraper.
         */
        $total = (clone $query)
            ->select(\Illuminate\Support\Facades\DB::raw(
                'COALESCE(SUM((achat_lignes_commande.quantite - achat_lignes_commande.quantite_livree)'
                .' * achat_lignes_commande.prix_unitaire_ht'
                .' * (1 + achat_lignes_commande.taux_tva / 100.0)), 0) AS total'
            ))
            // Les relations chargées par `with()` déclencheraient une seconde
            // requête sur des colonnes absentes de cette projection.
            ->withoutEagerLoads()
            ->value('total');

        return round((float) $total, 2);
    }
}
