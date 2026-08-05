<?php

namespace Modules\Stock\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Models\Transfert;

/**
 * Fabrique des « états » du module Stock (SFD §5 — stock.rapports.*).
 *
 * Chaque état est décrit par un catalogue (code, titre, intention, filtres
 * pertinents) et produit un tableau normalisé : colonnes typées + lignes +
 * ligne de totaux. Ce format unique alimente indifféremment l'écran, le CSV,
 * le XLSX et le PDF, ce qui garantit que l'exporté est exactement l'affiché
 * (exigence d'audit S9).
 *
 * Tout le SQL est portable SQLite / PostgreSQL (COALESCE, CASE, pas de
 * fonction propriétaire hors du regroupement mensuel, isolé dans sqlMois()).
 */
class RapportService
{
    public const TYPE_TEXTE = 'texte';

    public const TYPE_NOMBRE = 'nombre';

    public const TYPE_DECIMAL = 'decimal';

    public const TYPE_MONTANT = 'montant';

    public const TYPE_DATE = 'date';

    public const TYPE_DATETIME = 'datetime';

    public const TYPE_BADGE = 'badge';

    /**
     * Catalogue des états (UX §7). L'ordre est celui de la page d'accueil des
     * rapports : d'abord la photo du stock, puis les registres de flux, puis
     * les analyses.
     *
     * @return array<string, array{titre: string, intention: string, icone: string, famille: string, filtres: list<string>}>
     */
    public static function catalogue(): array
    {
        return [
            'valorisation' => [
                'titre' => 'Valorisation du stock',
                'intention' => "Photo à l'instant T : quantités et valeur immobilisée par magasin et par catégorie.",
                'icone' => 'bi-cash-stack',
                'famille' => 'Photo du stock',
                'filtres' => ['magasin_id', 'nature', 'categorie_id'],
            ],
            'alertes' => [
                'titre' => 'Articles sous seuil et ruptures',
                'intention' => 'Liste de réapprovisionnement : tout ce qui est en rupture ou sous le seuil effectif.',
                'icone' => 'bi-exclamation-triangle',
                'famille' => 'Photo du stock',
                'filtres' => ['magasin_id', 'nature', 'categorie_id'],
            ],
            'equipements' => [
                'titre' => 'Équipements en stock',
                'intention' => 'Unités sérialisées présentes en magasin, avec leur ancienneté de rattachement.',
                'icone' => 'bi-pc-display',
                'famille' => 'Photo du stock',
                'filtres' => ['magasin_id'],
            ],
            'mouvements' => [
                'titre' => 'Journal des mouvements',
                'intention' => 'Piste d\'audit complète : chaque écriture du journal, dans l\'ordre chronologique.',
                'icone' => 'bi-clock-history',
                'famille' => 'Registres de flux',
                'filtres' => ['periode', 'magasin_id', 'type', 'nature', 'categorie_id'],
            ],
            'entrees' => [
                'titre' => 'Registre des entrées',
                'intention' => 'Réceptions de la période, par fournisseur, avec la valeur reçue.',
                'icone' => 'bi-box-arrow-in-down',
                'famille' => 'Registres de flux',
                'filtres' => ['periode', 'magasin_id', 'statut_document'],
            ],
            'sorties' => [
                'titre' => 'Registre des sorties',
                'intention' => 'Délivrances de la période, par bénéficiaire et par motif.',
                'icone' => 'bi-box-arrow-up',
                'famille' => 'Registres de flux',
                'filtres' => ['periode', 'magasin_id', 'statut_document', 'motif_type'],
            ],
            'transferts' => [
                'titre' => 'Registre des transferts',
                'intention' => 'Flux inter-magasins de la période, source vers cible.',
                'icone' => 'bi-arrow-left-right',
                'famille' => 'Registres de flux',
                'filtres' => ['periode', 'magasin_id', 'statut_document'],
            ],
            'consommation' => [
                'titre' => 'Consommation par bénéficiaire',
                'intention' => 'Qui consomme quoi : quantités et valeur sorties par bénéficiaire sur la période.',
                'icone' => 'bi-people',
                'famille' => 'Analyses',
                'filtres' => ['periode', 'magasin_id', 'nature', 'categorie_id'],
            ],
            'rotation' => [
                'titre' => 'Rotation des articles',
                'intention' => 'Entrées, sorties, stock final et couverture estimée en jours par article.',
                'icone' => 'bi-arrow-repeat',
                'famille' => 'Analyses',
                'filtres' => ['periode', 'magasin_id', 'nature', 'categorie_id'],
            ],
            'dormants' => [
                'titre' => 'Articles dormants',
                'intention' => 'Références en stock sans aucune sortie sur la période : capital immobilisé.',
                'icone' => 'bi-hourglass-bottom',
                'famille' => 'Analyses',
                'filtres' => ['periode', 'magasin_id', 'nature', 'categorie_id'],
            ],
        ];
    }

    public static function existe(string $code): bool
    {
        return array_key_exists($code, self::catalogue());
    }

    /**
     * Construit un état complet.
     *
     * @param  array<string, mixed>  $filtres
     * @return array{code: string, titre: string, intention: string, colonnes: list<array>, lignes: Collection, totaux: array<string, float|int|string>, resume: list<array{libelle: string, valeur: string}>}
     */
    public function construire(string $code, array $filtres): array
    {
        $definition = self::catalogue()[$code] ?? throw new \InvalidArgumentException("État inconnu : {$code}");

        $etat = match ($code) {
            'valorisation' => $this->valorisation($filtres),
            'alertes' => $this->alertes($filtres),
            'equipements' => $this->equipements($filtres),
            'mouvements' => $this->mouvements($filtres),
            'entrees' => $this->entrees($filtres),
            'sorties' => $this->sorties($filtres),
            'transferts' => $this->transferts($filtres),
            'consommation' => $this->consommation($filtres),
            'rotation' => $this->rotation($filtres),
            'dormants' => $this->dormants($filtres),
        };

        return array_merge([
            'code' => $code,
            'titre' => $definition['titre'],
            'intention' => $definition['intention'],
            'totaux' => [],
            'resume' => [],
        ], $etat);
    }

    // ── États : photo du stock ─────────────────────────────────────────────

    private function valorisation(array $filtres): array
    {
        $lignes = $this->requeteNiveaux($filtres)
            ->join('catalogue_categories', 'catalogue_categories.id', '=', 'catalogue_articles.categorie_id')
            ->join('stock_magasins', 'stock_magasins.id', '=', 'stock_niveaux.magasin_id')
            ->where('stock_niveaux.quantite', '>', 0)
            ->groupBy('stock_magasins.libelle', 'catalogue_categories.libelle', 'catalogue_articles.nature')
            ->selectRaw('stock_magasins.libelle AS magasin')
            ->selectRaw('catalogue_categories.libelle AS categorie')
            ->selectRaw('catalogue_articles.nature AS nature')
            ->selectRaw('COUNT(*) AS nb_references')
            ->selectRaw('COALESCE(SUM(stock_niveaux.quantite), 0) AS quantite')
            ->selectRaw('COALESCE(SUM('.Niveau::sqlValeur().'), 0) AS valeur')
            ->orderBy('stock_magasins.libelle')
            ->orderByRaw('COALESCE(SUM('.Niveau::sqlValeur().'), 0) DESC')
            ->get()
            ->map(fn ($ligne) => [
                'magasin' => $ligne->magasin,
                'categorie' => $ligne->categorie,
                'nature' => Article::NATURE_LABELS[$ligne->nature] ?? $ligne->nature,
                'nb_references' => (int) $ligne->nb_references,
                'quantite' => (float) $ligne->quantite,
                'valeur' => (float) $ligne->valeur,
            ]);

        $valeurTotale = (float) $lignes->sum('valeur');

        return [
            'colonnes' => [
                ['cle' => 'magasin', 'libelle' => 'Magasin', 'type' => self::TYPE_TEXTE],
                ['cle' => 'categorie', 'libelle' => 'Catégorie', 'type' => self::TYPE_TEXTE],
                ['cle' => 'nature', 'libelle' => 'Nature', 'type' => self::TYPE_BADGE],
                ['cle' => 'nb_references', 'libelle' => 'Références', 'type' => self::TYPE_NOMBRE],
                ['cle' => 'quantite', 'libelle' => 'Quantité', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'valeur', 'libelle' => 'Valeur (FCFA)', 'type' => self::TYPE_MONTANT],
            ],
            'lignes' => $lignes,
            'totaux' => [
                'magasin' => 'TOTAL',
                'nb_references' => (int) $lignes->sum('nb_references'),
                'quantite' => (float) $lignes->sum('quantite'),
                'valeur' => $valeurTotale,
            ],
            'resume' => [
                ['libelle' => 'Valeur immobilisée', 'valeur' => $this->montant($valeurTotale).' FCFA'],
                ['libelle' => 'Références valorisées', 'valeur' => (string) $lignes->sum('nb_references')],
                ['libelle' => 'Regroupements', 'valeur' => (string) $lignes->count()],
            ],
        ];
    }

    private function alertes(array $filtres): array
    {
        $lignes = $this->requeteNiveaux($filtres)
            ->join('stock_magasins', 'stock_magasins.id', '=', 'stock_niveaux.magasin_id')
            ->whereRaw(Niveau::sqlStatutAlerte().' <> ?', [Niveau::STATUT_OK])
            ->selectRaw('stock_magasins.libelle AS magasin')
            ->selectRaw('catalogue_articles.code AS article_code, catalogue_articles.nom AS article_nom')
            ->selectRaw('catalogue_articles.nature AS nature, catalogue_articles.unite_stock AS unite')
            ->selectRaw('stock_niveaux.quantite AS quantite')
            ->selectRaw('COALESCE(stock_niveaux.seuil, catalogue_articles.seuil_defaut) AS seuil')
            ->selectRaw(Niveau::sqlStatutAlerte().' AS statut')
            ->selectRaw(Niveau::sqlValeur().' AS valeur')
            ->orderByRaw('CASE WHEN stock_niveaux.quantite <= 0 THEN 0 ELSE 1 END')
            ->orderBy('stock_niveaux.quantite')
            ->get()
            ->map(fn ($ligne) => [
                'article' => $ligne->article_code.' — '.$ligne->article_nom,
                'nature' => Article::NATURE_LABELS[$ligne->nature] ?? $ligne->nature,
                'magasin' => $ligne->magasin,
                'quantite' => (float) $ligne->quantite,
                'unite' => $ligne->unite,
                'seuil' => $ligne->seuil === null ? null : (float) $ligne->seuil,
                'manque' => max(0, (float) ($ligne->seuil ?? 0) - (float) $ligne->quantite),
                'statut' => $ligne->statut === Niveau::STATUT_RUPTURE ? '⛔ Rupture' : '⚠ Sous seuil',
                'valeur' => (float) $ligne->valeur,
            ]);

        return [
            'colonnes' => [
                ['cle' => 'article', 'libelle' => 'Article', 'type' => self::TYPE_TEXTE],
                ['cle' => 'nature', 'libelle' => 'Nature', 'type' => self::TYPE_BADGE],
                ['cle' => 'magasin', 'libelle' => 'Magasin', 'type' => self::TYPE_TEXTE],
                ['cle' => 'quantite', 'libelle' => 'Stock', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'unite', 'libelle' => 'Unité', 'type' => self::TYPE_TEXTE],
                ['cle' => 'seuil', 'libelle' => 'Seuil', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'manque', 'libelle' => 'À réapprovisionner', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'statut', 'libelle' => 'Statut', 'type' => self::TYPE_BADGE],
            ],
            'lignes' => $lignes,
            'totaux' => [
                'article' => 'TOTAL',
                'quantite' => (float) $lignes->sum('quantite'),
                'manque' => (float) $lignes->sum('manque'),
            ],
            'resume' => [
                ['libelle' => 'Ruptures', 'valeur' => (string) $lignes->where('statut', '⛔ Rupture')->count()],
                ['libelle' => 'Sous seuil', 'valeur' => (string) $lignes->where('statut', '⚠ Sous seuil')->count()],
                ['libelle' => 'Quantité à réapprovisionner', 'valeur' => $this->decimal((float) $lignes->sum('manque'))],
            ],
        ];
    }

    private function equipements(array $filtres): array
    {
        $query = DB::table('stock_equipements_magasins')
            ->join('stock_magasins', 'stock_magasins.id', '=', 'stock_equipements_magasins.magasin_id')
            ->join('parc_info_equipements', 'parc_info_equipements.id', '=', 'stock_equipements_magasins.equipement_id')
            ->leftJoin('parc_info_marques', 'parc_info_marques.id', '=', 'parc_info_equipements.marque_id');

        if (! empty($filtres['magasin_id'])) {
            $query->where('stock_equipements_magasins.magasin_id', (int) $filtres['magasin_id']);
        }

        $lignes = $query
            ->selectRaw('stock_magasins.libelle AS magasin')
            ->selectRaw('parc_info_equipements.code_inventaire AS code_inventaire')
            ->selectRaw('parc_info_equipements.numero_serie AS numero_serie')
            ->selectRaw('parc_info_equipements.modele AS modele')
            ->selectRaw('parc_info_marques.libelle AS marque')
            ->selectRaw('parc_info_equipements.etat AS etat')
            ->selectRaw('parc_info_equipements.valeur_achat AS valeur_achat')
            ->selectRaw('stock_equipements_magasins.date_rattachement AS date_rattachement')
            ->orderBy('stock_magasins.libelle')
            ->orderBy('stock_equipements_magasins.date_rattachement')
            ->get()
            ->map(fn ($ligne) => [
                'magasin' => $ligne->magasin,
                'code_inventaire' => $ligne->code_inventaire,
                'numero_serie' => $ligne->numero_serie ?? '—',
                'designation' => trim(($ligne->marque ?? '').' '.($ligne->modele ?? '')) ?: '—',
                'etat' => ucfirst((string) $ligne->etat),
                'valeur_achat' => (float) ($ligne->valeur_achat ?? 0),
                'date_rattachement' => $ligne->date_rattachement,
                'anciennete_jours' => $ligne->date_rattachement
                    ? Carbon::parse($ligne->date_rattachement)->diffInDays(now())
                    : 0,
            ]);

        return [
            'colonnes' => [
                ['cle' => 'magasin', 'libelle' => 'Magasin', 'type' => self::TYPE_TEXTE],
                ['cle' => 'code_inventaire', 'libelle' => 'N° inventaire', 'type' => self::TYPE_TEXTE],
                ['cle' => 'numero_serie', 'libelle' => 'N° série', 'type' => self::TYPE_TEXTE],
                ['cle' => 'designation', 'libelle' => 'Désignation', 'type' => self::TYPE_TEXTE],
                ['cle' => 'etat', 'libelle' => 'État', 'type' => self::TYPE_BADGE],
                ['cle' => 'valeur_achat', 'libelle' => 'Valeur achat (FCFA)', 'type' => self::TYPE_MONTANT],
                ['cle' => 'date_rattachement', 'libelle' => 'En stock depuis le', 'type' => self::TYPE_DATETIME],
                ['cle' => 'anciennete_jours', 'libelle' => 'Ancienneté (j)', 'type' => self::TYPE_NOMBRE],
            ],
            'lignes' => $lignes,
            'totaux' => [
                'magasin' => 'TOTAL',
                'valeur_achat' => (float) $lignes->sum('valeur_achat'),
            ],
            'resume' => [
                ['libelle' => 'Unités en stock', 'valeur' => (string) $lignes->count()],
                ['libelle' => 'Valeur d\'achat cumulée', 'valeur' => $this->montant((float) $lignes->sum('valeur_achat')).' FCFA'],
                ['libelle' => 'Ancienneté moyenne', 'valeur' => $lignes->isEmpty() ? '—' : round((float) $lignes->avg('anciennete_jours')).' jours'],
            ],
        ];
    }

    // ── États : registres de flux ──────────────────────────────────────────

    private function mouvements(array $filtres): array
    {
        [$debut, $fin] = $this->periode($filtres);

        $query = DB::table('stock_mouvements')
            ->join('stock_magasins', 'stock_magasins.id', '=', 'stock_mouvements.magasin_id')
            ->leftJoin('catalogue_articles', 'catalogue_articles.id', '=', 'stock_mouvements.article_id')
            ->leftJoin('parc_info_equipements', 'parc_info_equipements.id', '=', 'stock_mouvements.equipement_id')
            ->leftJoin('stock_entrees', 'stock_entrees.id', '=', 'stock_mouvements.entree_id')
            ->leftJoin('stock_sorties', 'stock_sorties.id', '=', 'stock_mouvements.sortie_id')
            ->leftJoin('stock_transferts', 'stock_transferts.id', '=', 'stock_mouvements.transfert_id')
            ->leftJoin('stock_inventaires', 'stock_inventaires.id', '=', 'stock_mouvements.inventaire_id')
            ->leftJoin('users', 'users.id', '=', 'stock_mouvements.created_by')
            ->whereBetween('stock_mouvements.created_at', [$debut, $fin]);

        $this->appliquerFiltresArticle($query, $filtres);

        if (! empty($filtres['magasin_id'])) {
            $query->where('stock_mouvements.magasin_id', (int) $filtres['magasin_id']);
        }

        if (! empty($filtres['type'])) {
            $query->where('stock_mouvements.type', $filtres['type']);
        }

        $lignes = $query
            ->selectRaw('stock_mouvements.created_at AS date')
            ->selectRaw('stock_mouvements.type AS type')
            ->selectRaw('stock_mouvements.sens AS sens')
            ->selectRaw('stock_mouvements.quantite AS quantite')
            ->selectRaw('stock_mouvements.cout_unitaire AS cout_unitaire')
            ->selectRaw('stock_magasins.libelle AS magasin')
            ->selectRaw('catalogue_articles.code AS article_code, catalogue_articles.nom AS article_nom')
            ->selectRaw('parc_info_equipements.code_inventaire AS equipement_code, parc_info_equipements.modele AS equipement_modele')
            ->selectRaw('COALESCE(stock_entrees.numero, stock_sorties.numero, stock_transferts.numero, stock_inventaires.numero) AS document')
            ->selectRaw('stock_sorties.beneficiaire_libelle AS beneficiaire')
            ->selectRaw('users.name AS auteur')
            ->selectRaw('stock_mouvements.mouvement_origine_id AS origine')
            ->orderBy('stock_mouvements.created_at')
            ->orderBy('stock_mouvements.id')
            ->get()
            ->map(fn ($ligne) => [
                'date' => $ligne->date,
                'type' => Mouvement::TYPE_LABELS[$ligne->type] ?? $ligne->type,
                'document' => $ligne->document ?? ($ligne->origine ? 'Contre-mouvement' : '—'),
                'article' => $ligne->article_code
                    ? $ligne->article_code.' — '.$ligne->article_nom
                    : trim(($ligne->equipement_code ?? '').' — '.($ligne->equipement_modele ?? '')),
                'magasin' => $ligne->magasin,
                'quantite_signee' => ((int) $ligne->sens >= 0 ? '+' : '−').$this->decimal((float) $ligne->quantite),
                'valeur' => (float) $ligne->quantite * (float) ($ligne->cout_unitaire ?? 0),
                'beneficiaire' => $ligne->beneficiaire ?? '—',
                'auteur' => $ligne->auteur ?? '—',
                '_entree' => (int) $ligne->sens > 0 ? (float) $ligne->quantite : 0,
                '_sortie' => (int) $ligne->sens < 0 ? (float) $ligne->quantite : 0,
            ]);

        return [
            'colonnes' => [
                ['cle' => 'date', 'libelle' => 'Date', 'type' => self::TYPE_DATETIME],
                ['cle' => 'type', 'libelle' => 'Type', 'type' => self::TYPE_BADGE],
                ['cle' => 'document', 'libelle' => 'Document', 'type' => self::TYPE_TEXTE],
                ['cle' => 'article', 'libelle' => 'Article / Unité', 'type' => self::TYPE_TEXTE],
                ['cle' => 'magasin', 'libelle' => 'Magasin', 'type' => self::TYPE_TEXTE],
                ['cle' => 'quantite_signee', 'libelle' => 'Quantité', 'type' => self::TYPE_TEXTE],
                ['cle' => 'valeur', 'libelle' => 'Valeur (FCFA)', 'type' => self::TYPE_MONTANT],
                ['cle' => 'beneficiaire', 'libelle' => 'Bénéficiaire', 'type' => self::TYPE_TEXTE],
                ['cle' => 'auteur', 'libelle' => 'Saisi par', 'type' => self::TYPE_TEXTE],
            ],
            'lignes' => $lignes->map(fn (array $l) => collect($l)->except(['_entree', '_sortie'])->all()),
            'totaux' => [
                'document' => 'TOTAL',
                'valeur' => (float) $lignes->sum('valeur'),
            ],
            'resume' => [
                ['libelle' => 'Écritures', 'valeur' => (string) $lignes->count()],
                ['libelle' => 'Quantités entrées', 'valeur' => $this->decimal((float) $lignes->sum('_entree'))],
                ['libelle' => 'Quantités sorties', 'valeur' => $this->decimal((float) $lignes->sum('_sortie'))],
                ['libelle' => 'Valeur mouvementée', 'valeur' => $this->montant((float) $lignes->sum('valeur')).' FCFA'],
            ],
        ];
    }

    private function entrees(array $filtres): array
    {
        [$debut, $fin] = $this->periode($filtres);

        $query = DB::table('stock_entrees')
            ->join('stock_magasins', 'stock_magasins.id', '=', 'stock_entrees.magasin_id')
            ->leftJoin('catalogue_fournisseurs', 'catalogue_fournisseurs.id', '=', 'stock_entrees.fournisseur_id')
            ->leftJoin('users', 'users.id', '=', 'stock_entrees.created_by')
            ->leftJoin(
                DB::raw('(SELECT entree_id, COUNT(*) AS nb_lignes, SUM(quantite) AS quantite,
                          SUM(quantite * COALESCE(cout_unitaire, 0)) AS valeur
                          FROM stock_lignes_entrees GROUP BY entree_id) AS agr'),
                'agr.entree_id', '=', 'stock_entrees.id'
            )
            ->whereBetween('stock_entrees.date_document', [$debut->toDateString(), $fin->toDateString()]);

        if (! empty($filtres['magasin_id'])) {
            $query->where('stock_entrees.magasin_id', (int) $filtres['magasin_id']);
        }

        if (! empty($filtres['statut_document'])) {
            $query->where('stock_entrees.statut', $filtres['statut_document']);
        }

        $lignes = $query
            ->selectRaw('stock_entrees.numero AS numero, stock_entrees.date_document AS date_document')
            ->selectRaw('stock_entrees.statut AS statut, stock_entrees.nature AS nature')
            ->selectRaw('stock_entrees.reference_externe AS reference')
            ->selectRaw('stock_magasins.libelle AS magasin')
            ->selectRaw('catalogue_fournisseurs.raison_sociale AS fournisseur')
            ->selectRaw('COALESCE(agr.nb_lignes, 0) AS nb_lignes, COALESCE(agr.quantite, 0) AS quantite, COALESCE(agr.valeur, 0) AS valeur')
            ->selectRaw('users.name AS auteur')
            ->orderByDesc('stock_entrees.date_document')
            ->orderByDesc('stock_entrees.id')
            ->get()
            ->map(fn ($ligne) => [
                'numero' => $ligne->numero ?? '(non validé)',
                'date_document' => $ligne->date_document,
                'magasin' => $ligne->magasin,
                'nature' => $ligne->nature === Entree::NATURE_RETOUR ? 'Retour' : 'Livraison',
                'fournisseur' => $ligne->fournisseur ?? '—',
                'reference' => $ligne->reference ?? '—',
                'nb_lignes' => (int) $ligne->nb_lignes,
                'quantite' => (float) $ligne->quantite,
                'valeur' => (float) $ligne->valeur,
                'statut' => Entree::STATUT_LABELS[$ligne->statut] ?? $ligne->statut,
                'auteur' => $ligne->auteur ?? '—',
                '_valide' => $ligne->statut === Entree::STATUT_VALIDE,
            ]);

        return [
            'colonnes' => [
                ['cle' => 'numero', 'libelle' => 'N° bon', 'type' => self::TYPE_TEXTE],
                ['cle' => 'date_document', 'libelle' => 'Date', 'type' => self::TYPE_DATE],
                ['cle' => 'magasin', 'libelle' => 'Magasin', 'type' => self::TYPE_TEXTE],
                ['cle' => 'nature', 'libelle' => 'Nature', 'type' => self::TYPE_BADGE],
                ['cle' => 'fournisseur', 'libelle' => 'Fournisseur', 'type' => self::TYPE_TEXTE],
                ['cle' => 'reference', 'libelle' => 'Réf. externe', 'type' => self::TYPE_TEXTE],
                ['cle' => 'nb_lignes', 'libelle' => 'Lignes', 'type' => self::TYPE_NOMBRE],
                ['cle' => 'quantite', 'libelle' => 'Quantité', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'valeur', 'libelle' => 'Valeur (FCFA)', 'type' => self::TYPE_MONTANT],
                ['cle' => 'statut', 'libelle' => 'Statut', 'type' => self::TYPE_BADGE],
                ['cle' => 'auteur', 'libelle' => 'Saisi par', 'type' => self::TYPE_TEXTE],
            ],
            'lignes' => $lignes->map(fn (array $l) => collect($l)->except(['_valide'])->all()),
            'totaux' => [
                'numero' => 'TOTAL',
                'nb_lignes' => (int) $lignes->sum('nb_lignes'),
                'quantite' => (float) $lignes->sum('quantite'),
                'valeur' => (float) $lignes->sum('valeur'),
            ],
            'resume' => [
                ['libelle' => 'Bons', 'valeur' => (string) $lignes->count()],
                ['libelle' => 'Dont validés', 'valeur' => (string) $lignes->where('_valide', true)->count()],
                ['libelle' => 'Valeur reçue', 'valeur' => $this->montant((float) $lignes->sum('valeur')).' FCFA'],
            ],
        ];
    }

    private function sorties(array $filtres): array
    {
        [$debut, $fin] = $this->periode($filtres);

        $query = DB::table('stock_sorties')
            ->join('stock_magasins', 'stock_magasins.id', '=', 'stock_sorties.magasin_id')
            ->leftJoin('users', 'users.id', '=', 'stock_sorties.created_by')
            ->leftJoin(
                DB::raw('(SELECT sortie_id, COUNT(*) AS nb_lignes, SUM(quantite) AS quantite
                          FROM stock_lignes_sorties GROUP BY sortie_id) AS agr'),
                'agr.sortie_id', '=', 'stock_sorties.id'
            )
            ->leftJoin(
                DB::raw('(SELECT sortie_id, SUM(quantite * COALESCE(cout_unitaire, 0)) AS valeur, COUNT(*) AS nb_ecritures
                          FROM stock_mouvements WHERE sortie_id IS NOT NULL GROUP BY sortie_id) AS mvt'),
                'mvt.sortie_id', '=', 'stock_sorties.id'
            )
            ->whereBetween('stock_sorties.date_document', [$debut->toDateString(), $fin->toDateString()]);

        if (! empty($filtres['magasin_id'])) {
            $query->where('stock_sorties.magasin_id', (int) $filtres['magasin_id']);
        }

        if (! empty($filtres['statut_document'])) {
            $query->where('stock_sorties.statut', $filtres['statut_document']);
        }

        if (! empty($filtres['motif_type'])) {
            $query->where('stock_sorties.motif_type', $filtres['motif_type']);
        }

        $motifs = config('stock.motifs_sortie', []);

        $lignes = $query
            ->selectRaw('stock_sorties.numero AS numero, stock_sorties.date_document AS date_document')
            ->selectRaw('stock_sorties.statut AS statut, stock_sorties.motif_type AS motif_type')
            ->selectRaw('stock_sorties.beneficiaire_type AS beneficiaire_type, stock_sorties.beneficiaire_libelle AS beneficiaire')
            ->selectRaw('stock_sorties.remis_a_nom AS remis_a')
            ->selectRaw('stock_magasins.libelle AS magasin')
            ->selectRaw('COALESCE(agr.nb_lignes, 0) AS nb_lignes, COALESCE(agr.quantite, 0) AS quantite')
            ->selectRaw('COALESCE(mvt.valeur, 0) AS valeur')
            ->selectRaw('users.name AS auteur')
            ->orderByDesc('stock_sorties.date_document')
            ->orderByDesc('stock_sorties.id')
            ->get()
            ->map(fn ($ligne) => [
                'numero' => $ligne->numero ?? '(non validé)',
                'date_document' => $ligne->date_document,
                'magasin' => $ligne->magasin,
                'beneficiaire' => $ligne->beneficiaire ?? ucfirst((string) $ligne->beneficiaire_type),
                'motif' => $motifs[$ligne->motif_type] ?? $ligne->motif_type,
                'nb_lignes' => (int) $ligne->nb_lignes,
                'quantite' => (float) $ligne->quantite,
                'valeur' => (float) $ligne->valeur,
                'remis_a' => $ligne->remis_a ?? '—',
                'statut' => Sortie::STATUT_LABELS[$ligne->statut] ?? $ligne->statut,
                'auteur' => $ligne->auteur ?? '—',
                '_valide' => $ligne->statut === Sortie::STATUT_VALIDE,
            ]);

        return [
            'colonnes' => [
                ['cle' => 'numero', 'libelle' => 'N° bon', 'type' => self::TYPE_TEXTE],
                ['cle' => 'date_document', 'libelle' => 'Date', 'type' => self::TYPE_DATE],
                ['cle' => 'magasin', 'libelle' => 'Magasin', 'type' => self::TYPE_TEXTE],
                ['cle' => 'beneficiaire', 'libelle' => 'Bénéficiaire', 'type' => self::TYPE_TEXTE],
                ['cle' => 'motif', 'libelle' => 'Motif', 'type' => self::TYPE_BADGE],
                ['cle' => 'nb_lignes', 'libelle' => 'Lignes', 'type' => self::TYPE_NOMBRE],
                ['cle' => 'quantite', 'libelle' => 'Quantité', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'valeur', 'libelle' => 'Valeur (FCFA)', 'type' => self::TYPE_MONTANT],
                ['cle' => 'remis_a', 'libelle' => 'Remis à', 'type' => self::TYPE_TEXTE],
                ['cle' => 'statut', 'libelle' => 'Statut', 'type' => self::TYPE_BADGE],
                ['cle' => 'auteur', 'libelle' => 'Saisi par', 'type' => self::TYPE_TEXTE],
            ],
            'lignes' => $lignes->map(fn (array $l) => collect($l)->except(['_valide'])->all()),
            'totaux' => [
                'numero' => 'TOTAL',
                'nb_lignes' => (int) $lignes->sum('nb_lignes'),
                'quantite' => (float) $lignes->sum('quantite'),
                'valeur' => (float) $lignes->sum('valeur'),
            ],
            'resume' => [
                ['libelle' => 'Bons', 'valeur' => (string) $lignes->count()],
                ['libelle' => 'Dont validés', 'valeur' => (string) $lignes->where('_valide', true)->count()],
                ['libelle' => 'Valeur délivrée', 'valeur' => $this->montant((float) $lignes->sum('valeur')).' FCFA'],
            ],
        ];
    }

    private function transferts(array $filtres): array
    {
        [$debut, $fin] = $this->periode($filtres);

        $query = DB::table('stock_transferts')
            ->join('stock_magasins AS source', 'source.id', '=', 'stock_transferts.magasin_source_id')
            ->join('stock_magasins AS cible', 'cible.id', '=', 'stock_transferts.magasin_cible_id')
            ->leftJoin('users', 'users.id', '=', 'stock_transferts.created_by')
            ->leftJoin(
                DB::raw('(SELECT transfert_id, COUNT(*) AS nb_lignes, SUM(quantite) AS quantite
                          FROM stock_lignes_transferts GROUP BY transfert_id) AS agr'),
                'agr.transfert_id', '=', 'stock_transferts.id'
            )
            ->whereBetween('stock_transferts.date_document', [$debut->toDateString(), $fin->toDateString()]);

        if (! empty($filtres['magasin_id'])) {
            $magasinId = (int) $filtres['magasin_id'];
            $query->where(fn ($q) => $q->where('stock_transferts.magasin_source_id', $magasinId)
                ->orWhere('stock_transferts.magasin_cible_id', $magasinId));
        }

        if (! empty($filtres['statut_document'])) {
            $query->where('stock_transferts.statut', $filtres['statut_document']);
        }

        $lignes = $query
            ->selectRaw('stock_transferts.numero AS numero, stock_transferts.date_document AS date_document')
            ->selectRaw('stock_transferts.statut AS statut, stock_transferts.transporte_par_nom AS transporteur')
            ->selectRaw('source.libelle AS source, cible.libelle AS cible')
            ->selectRaw('COALESCE(agr.nb_lignes, 0) AS nb_lignes, COALESCE(agr.quantite, 0) AS quantite')
            ->selectRaw('users.name AS auteur')
            ->orderByDesc('stock_transferts.date_document')
            ->orderByDesc('stock_transferts.id')
            ->get()
            ->map(fn ($ligne) => [
                'numero' => $ligne->numero ?? '(non validé)',
                'date_document' => $ligne->date_document,
                'source' => $ligne->source,
                'cible' => $ligne->cible,
                'nb_lignes' => (int) $ligne->nb_lignes,
                'quantite' => (float) $ligne->quantite,
                'transporteur' => $ligne->transporteur ?? '—',
                'statut' => Transfert::STATUT_LABELS[$ligne->statut] ?? $ligne->statut,
                'auteur' => $ligne->auteur ?? '—',
                '_valide' => $ligne->statut === Transfert::STATUT_VALIDE,
            ]);

        return [
            'colonnes' => [
                ['cle' => 'numero', 'libelle' => 'N° bon', 'type' => self::TYPE_TEXTE],
                ['cle' => 'date_document', 'libelle' => 'Date', 'type' => self::TYPE_DATE],
                ['cle' => 'source', 'libelle' => 'Magasin source', 'type' => self::TYPE_TEXTE],
                ['cle' => 'cible', 'libelle' => 'Magasin cible', 'type' => self::TYPE_TEXTE],
                ['cle' => 'nb_lignes', 'libelle' => 'Lignes', 'type' => self::TYPE_NOMBRE],
                ['cle' => 'quantite', 'libelle' => 'Quantité', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'transporteur', 'libelle' => 'Transporté par', 'type' => self::TYPE_TEXTE],
                ['cle' => 'statut', 'libelle' => 'Statut', 'type' => self::TYPE_BADGE],
                ['cle' => 'auteur', 'libelle' => 'Saisi par', 'type' => self::TYPE_TEXTE],
            ],
            'lignes' => $lignes->map(fn (array $l) => collect($l)->except(['_valide'])->all()),
            'totaux' => [
                'numero' => 'TOTAL',
                'nb_lignes' => (int) $lignes->sum('nb_lignes'),
                'quantite' => (float) $lignes->sum('quantite'),
            ],
            'resume' => [
                ['libelle' => 'Bons', 'valeur' => (string) $lignes->count()],
                ['libelle' => 'Dont validés', 'valeur' => (string) $lignes->where('_valide', true)->count()],
                ['libelle' => 'Quantités transférées', 'valeur' => $this->decimal((float) $lignes->sum('quantite'))],
            ],
        ];
    }

    // ── États : analyses ───────────────────────────────────────────────────

    private function consommation(array $filtres): array
    {
        [$debut, $fin] = $this->periode($filtres);

        $query = DB::table('stock_mouvements')
            ->join('stock_sorties', 'stock_sorties.id', '=', 'stock_mouvements.sortie_id')
            ->join('stock_magasins', 'stock_magasins.id', '=', 'stock_mouvements.magasin_id')
            ->leftJoin('catalogue_articles', 'catalogue_articles.id', '=', 'stock_mouvements.article_id')
            ->where('stock_mouvements.type', Mouvement::TYPE_SORTIE)
            ->whereBetween('stock_mouvements.created_at', [$debut, $fin]);

        $this->appliquerFiltresArticle($query, $filtres);

        if (! empty($filtres['magasin_id'])) {
            $query->where('stock_mouvements.magasin_id', (int) $filtres['magasin_id']);
        }

        $lignes = $query
            ->groupBy('stock_sorties.beneficiaire_libelle', 'stock_sorties.beneficiaire_type', 'stock_magasins.libelle')
            ->selectRaw('stock_sorties.beneficiaire_libelle AS beneficiaire, stock_sorties.beneficiaire_type AS type')
            ->selectRaw('stock_magasins.libelle AS magasin')
            ->selectRaw('COUNT(DISTINCT stock_mouvements.sortie_id) AS nb_bons')
            ->selectRaw('COUNT(DISTINCT stock_mouvements.article_id) AS nb_references')
            ->selectRaw('COALESCE(SUM(stock_mouvements.quantite), 0) AS quantite')
            ->selectRaw('COALESCE(SUM(stock_mouvements.quantite * COALESCE(stock_mouvements.cout_unitaire, 0)), 0) AS valeur')
            ->orderByRaw('COALESCE(SUM(stock_mouvements.quantite * COALESCE(stock_mouvements.cout_unitaire, 0)), 0) DESC')
            ->get()
            ->map(fn ($ligne) => [
                'beneficiaire' => $ligne->beneficiaire ?? ucfirst((string) $ligne->type),
                'type' => ucfirst(str_replace('_', ' ', (string) $ligne->type)),
                'magasin' => $ligne->magasin,
                'nb_bons' => (int) $ligne->nb_bons,
                'nb_references' => (int) $ligne->nb_references,
                'quantite' => (float) $ligne->quantite,
                'valeur' => (float) $ligne->valeur,
            ]);

        $total = (float) $lignes->sum('valeur');

        return [
            'colonnes' => [
                ['cle' => 'beneficiaire', 'libelle' => 'Bénéficiaire', 'type' => self::TYPE_TEXTE],
                ['cle' => 'type', 'libelle' => 'Type', 'type' => self::TYPE_BADGE],
                ['cle' => 'magasin', 'libelle' => 'Magasin', 'type' => self::TYPE_TEXTE],
                ['cle' => 'nb_bons', 'libelle' => 'Bons', 'type' => self::TYPE_NOMBRE],
                ['cle' => 'nb_references', 'libelle' => 'Références', 'type' => self::TYPE_NOMBRE],
                ['cle' => 'quantite', 'libelle' => 'Quantité', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'valeur', 'libelle' => 'Valeur (FCFA)', 'type' => self::TYPE_MONTANT],
            ],
            'lignes' => $lignes,
            'totaux' => [
                'beneficiaire' => 'TOTAL',
                'nb_bons' => (int) $lignes->sum('nb_bons'),
                'quantite' => (float) $lignes->sum('quantite'),
                'valeur' => $total,
            ],
            'resume' => [
                ['libelle' => 'Bénéficiaires servis', 'valeur' => (string) $lignes->count()],
                ['libelle' => 'Valeur consommée', 'valeur' => $this->montant($total).' FCFA'],
                ['libelle' => 'Premier consommateur', 'valeur' => $lignes->first()['beneficiaire'] ?? '—'],
            ],
        ];
    }

    private function rotation(array $filtres): array
    {
        [$debut, $fin] = $this->periode($filtres);
        $jours = max(1, (int) $debut->diffInDays($fin));

        $flux = DB::table('stock_mouvements')
            ->join('catalogue_articles', 'catalogue_articles.id', '=', 'stock_mouvements.article_id')
            ->whereNotNull('stock_mouvements.article_id')
            ->whereBetween('stock_mouvements.created_at', [$debut, $fin]);

        $this->appliquerFiltresArticle($flux, $filtres);

        if (! empty($filtres['magasin_id'])) {
            $flux->where('stock_mouvements.magasin_id', (int) $filtres['magasin_id']);
        }

        $flux = $flux
            ->groupBy('stock_mouvements.article_id')
            ->selectRaw('stock_mouvements.article_id AS article_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN stock_mouvements.sens > 0 THEN stock_mouvements.quantite ELSE 0 END), 0) AS entrees')
            ->selectRaw('COALESCE(SUM(CASE WHEN stock_mouvements.sens < 0 THEN stock_mouvements.quantite ELSE 0 END), 0) AS sorties')
            ->selectRaw('COALESCE(SUM(CASE WHEN stock_mouvements.sens < 0 THEN stock_mouvements.quantite * COALESCE(stock_mouvements.cout_unitaire, 0) ELSE 0 END), 0) AS valeur_sortie')
            ->get()
            ->keyBy('article_id');

        $stocks = $this->requeteNiveaux($filtres)
            ->groupBy('stock_niveaux.article_id', 'catalogue_articles.code', 'catalogue_articles.nom', 'catalogue_articles.nature', 'catalogue_articles.unite_stock')
            ->selectRaw('stock_niveaux.article_id AS article_id, catalogue_articles.code AS code, catalogue_articles.nom AS nom')
            ->selectRaw('catalogue_articles.nature AS nature, catalogue_articles.unite_stock AS unite')
            ->selectRaw('COALESCE(SUM(stock_niveaux.quantite), 0) AS stock')
            ->get()
            ->keyBy('article_id');

        $articleIds = $stocks->keys()->merge($flux->keys())->unique();

        $catalogue = Article::query()
            ->whereIn('id', $articleIds)
            ->get(['id', 'code', 'nom', 'nature', 'unite_stock'])
            ->keyBy('id');

        $lignes = $articleIds
            ->map(function ($articleId) use ($flux, $stocks, $catalogue, $jours) {
                $article = $catalogue[$articleId] ?? null;
                if ($article === null) {
                    return null;
                }

                $stock = (float) ($stocks[$articleId]->stock ?? 0);
                $sorties = (float) ($flux[$articleId]->sorties ?? 0);
                $entrees = (float) ($flux[$articleId]->entrees ?? 0);
                $consoJour = $sorties / $jours;

                return [
                    'article' => $article->code.' — '.$article->nom,
                    'nature' => Article::NATURE_LABELS[$article->nature] ?? $article->nature,
                    'unite' => $article->unite_stock,
                    'entrees' => $entrees,
                    'sorties' => $sorties,
                    'stock' => $stock,
                    'conso_jour' => round($consoJour, 2),
                    'couverture_jours' => $consoJour > 0 ? (int) floor($stock / $consoJour) : null,
                    'taux_rotation' => $stock > 0 ? round($sorties / $stock, 2) : null,
                    'valeur_sortie' => (float) ($flux[$articleId]->valeur_sortie ?? 0),
                ];
            })
            ->filter()
            ->sortByDesc('sorties')
            ->values();

        return [
            'colonnes' => [
                ['cle' => 'article', 'libelle' => 'Article', 'type' => self::TYPE_TEXTE],
                ['cle' => 'nature', 'libelle' => 'Nature', 'type' => self::TYPE_BADGE],
                ['cle' => 'entrees', 'libelle' => 'Entrées', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'sorties', 'libelle' => 'Sorties', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'stock', 'libelle' => 'Stock actuel', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'unite', 'libelle' => 'Unité', 'type' => self::TYPE_TEXTE],
                ['cle' => 'conso_jour', 'libelle' => 'Conso / jour', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'couverture_jours', 'libelle' => 'Couverture (j)', 'type' => self::TYPE_NOMBRE],
                ['cle' => 'taux_rotation', 'libelle' => 'Taux de rotation', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'valeur_sortie', 'libelle' => 'Valeur sortie (FCFA)', 'type' => self::TYPE_MONTANT],
            ],
            'lignes' => $lignes,
            'totaux' => [
                'article' => 'TOTAL',
                'entrees' => (float) $lignes->sum('entrees'),
                'sorties' => (float) $lignes->sum('sorties'),
                'stock' => (float) $lignes->sum('stock'),
                'valeur_sortie' => (float) $lignes->sum('valeur_sortie'),
            ],
            'resume' => [
                ['libelle' => 'Articles suivis', 'valeur' => (string) $lignes->count()],
                ['libelle' => 'Durée analysée', 'valeur' => $jours.' jours'],
                ['libelle' => 'Article le plus consommé', 'valeur' => $lignes->first()['article'] ?? '—'],
                ['libelle' => 'Couverture < 15 j', 'valeur' => (string) $lignes->filter(fn ($l) => $l['couverture_jours'] !== null && $l['couverture_jours'] < 15)->count()],
            ],
        ];
    }

    private function dormants(array $filtres): array
    {
        [$debut, $fin] = $this->periode($filtres);

        $sortis = DB::table('stock_mouvements')
            ->whereNotNull('article_id')
            ->where('sens', '<', 0)
            ->whereBetween('created_at', [$debut, $fin])
            ->distinct()
            ->pluck('article_id');

        $dernierMouvement = DB::table('stock_mouvements')
            ->whereNotNull('article_id')
            ->groupBy('article_id')
            ->selectRaw('article_id, MAX(created_at) AS dernier')
            ->pluck('dernier', 'article_id');

        $lignes = $this->requeteNiveaux($filtres)
            ->join('stock_magasins', 'stock_magasins.id', '=', 'stock_niveaux.magasin_id')
            ->where('stock_niveaux.quantite', '>', 0)
            ->when($sortis->isNotEmpty(), fn ($q) => $q->whereNotIn('stock_niveaux.article_id', $sortis->all()))
            ->selectRaw('stock_niveaux.article_id AS article_id')
            ->selectRaw('catalogue_articles.code AS code, catalogue_articles.nom AS nom, catalogue_articles.nature AS nature')
            ->selectRaw('catalogue_articles.unite_stock AS unite')
            ->selectRaw('stock_magasins.libelle AS magasin')
            ->selectRaw('stock_niveaux.quantite AS quantite')
            ->selectRaw(Niveau::sqlValeur().' AS valeur')
            ->orderByRaw(Niveau::sqlValeur().' DESC')
            ->get()
            ->map(function ($ligne) use ($dernierMouvement) {
                $dernier = $dernierMouvement[$ligne->article_id] ?? null;

                return [
                    'article' => $ligne->code.' — '.$ligne->nom,
                    'nature' => Article::NATURE_LABELS[$ligne->nature] ?? $ligne->nature,
                    'magasin' => $ligne->magasin,
                    'quantite' => (float) $ligne->quantite,
                    'unite' => $ligne->unite,
                    'valeur' => (float) $ligne->valeur,
                    'dernier_mouvement' => $dernier,
                    'immobilise_depuis' => $dernier ? Carbon::parse($dernier)->diffInDays(now()) : null,
                ];
            });

        return [
            'colonnes' => [
                ['cle' => 'article', 'libelle' => 'Article', 'type' => self::TYPE_TEXTE],
                ['cle' => 'nature', 'libelle' => 'Nature', 'type' => self::TYPE_BADGE],
                ['cle' => 'magasin', 'libelle' => 'Magasin', 'type' => self::TYPE_TEXTE],
                ['cle' => 'quantite', 'libelle' => 'Stock', 'type' => self::TYPE_DECIMAL],
                ['cle' => 'unite', 'libelle' => 'Unité', 'type' => self::TYPE_TEXTE],
                ['cle' => 'valeur', 'libelle' => 'Valeur immobilisée (FCFA)', 'type' => self::TYPE_MONTANT],
                ['cle' => 'dernier_mouvement', 'libelle' => 'Dernier mouvement', 'type' => self::TYPE_DATETIME],
                ['cle' => 'immobilise_depuis', 'libelle' => 'Immobilisé (j)', 'type' => self::TYPE_NOMBRE],
            ],
            'lignes' => $lignes,
            'totaux' => [
                'article' => 'TOTAL',
                'quantite' => (float) $lignes->sum('quantite'),
                'valeur' => (float) $lignes->sum('valeur'),
            ],
            'resume' => [
                ['libelle' => 'Références dormantes', 'valeur' => (string) $lignes->count()],
                ['libelle' => 'Capital immobilisé', 'valeur' => $this->montant((float) $lignes->sum('valeur')).' FCFA'],
                ['libelle' => 'Période observée', 'valeur' => $debut->format('d/m/Y').' → '.$fin->format('d/m/Y')],
            ],
        ];
    }

    // ── Outils ─────────────────────────────────────────────────────────────

    /** Base commune des niveaux + filtres article (jointure catalogue requise). */
    private function requeteNiveaux(array $filtres)
    {
        $query = DB::table('stock_niveaux')
            ->join('catalogue_articles', 'catalogue_articles.id', '=', 'stock_niveaux.article_id');

        if (! empty($filtres['magasin_id'])) {
            $query->where('stock_niveaux.magasin_id', (int) $filtres['magasin_id']);
        }

        $this->appliquerFiltresArticle($query, $filtres);

        return $query;
    }

    /** Nature + catégorie (catégorie parente incluse — cohérent avec l'état des stocks). */
    private function appliquerFiltresArticle($query, array $filtres): void
    {
        if (! empty($filtres['nature'])) {
            $query->where('catalogue_articles.nature', $filtres['nature']);
        }

        if (! empty($filtres['categorie_id'])) {
            $categorieId = (int) $filtres['categorie_id'];
            $ids = DB::table('catalogue_categories')->where('parent_id', $categorieId)->pluck('id')->push($categorieId);
            $query->whereIn('catalogue_articles.categorie_id', $ids->all());
        }
    }

    /**
     * Période demandée, bornée à la journée entière. Défaut : les 30 derniers
     * jours, choix conservateur qui donne un état lisible sans filtre.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function periode(array $filtres): array
    {
        $debut = ! empty($filtres['date_debut'])
            ? Carbon::parse($filtres['date_debut'])->startOfDay()
            : now()->subDays(30)->startOfDay();

        $fin = ! empty($filtres['date_fin'])
            ? Carbon::parse($filtres['date_fin'])->endOfDay()
            : now()->endOfDay();

        return $debut->greaterThan($fin) ? [$fin->copy()->startOfDay(), $debut->copy()->endOfDay()] : [$debut, $fin];
    }

    /** Filtres actifs en clair — imprimés en tête de chaque export (amendement UX n°18). */
    public function libellesFiltres(string $code, array $filtres): array
    {
        $definition = self::catalogue()[$code];
        $libelles = [];

        if (in_array('periode', $definition['filtres'], true)) {
            [$debut, $fin] = $this->periode($filtres);
            $libelles['Période'] = $debut->format('d/m/Y').' → '.$fin->format('d/m/Y');
        }

        if (! empty($filtres['magasin_id'])) {
            $libelles['Magasin'] = DB::table('stock_magasins')->where('id', (int) $filtres['magasin_id'])->value('libelle') ?? '?';
        }

        if (! empty($filtres['nature'])) {
            $libelles['Nature'] = Article::NATURE_LABELS[$filtres['nature']] ?? $filtres['nature'];
        }

        if (! empty($filtres['categorie_id'])) {
            $libelles['Catégorie'] = DB::table('catalogue_categories')->where('id', (int) $filtres['categorie_id'])->value('libelle') ?? '?';
        }

        if (! empty($filtres['type'])) {
            $libelles['Type de mouvement'] = Mouvement::TYPE_LABELS[$filtres['type']] ?? $filtres['type'];
        }

        if (! empty($filtres['statut_document'])) {
            $libelles['Statut'] = $filtres['statut_document'];
        }

        if (! empty($filtres['motif_type'])) {
            $libelles['Motif'] = config('stock.motifs_sortie.'.$filtres['motif_type'], $filtres['motif_type']);
        }

        return $libelles === [] ? ['Filtres' => 'aucun (état complet)'] : $libelles;
    }

    /** Aplatit une ligne pour l'export : mêmes libellés que l'écran. */
    public function lignesExport(array $etat): Collection
    {
        $colonnes = collect($etat['colonnes']);

        return collect($etat['lignes'])->map(function (array $ligne) use ($colonnes) {
            $exportee = [];
            foreach ($colonnes as $colonne) {
                $valeur = $ligne[$colonne['cle']] ?? null;
                $exportee[$colonne['libelle']] = $this->formaterExport($valeur, $colonne['type']);
            }

            return $exportee;
        });
    }

    /** Formatage tolérant : une valeur hors type (libellé de totaux) est rendue telle quelle. */
    private function formaterExport(mixed $valeur, string $type): mixed
    {
        if ($valeur === null || $valeur === '') {
            return in_array($type, [self::TYPE_MONTANT, self::TYPE_DECIMAL, self::TYPE_NOMBRE], true) ? '' : '—';
        }

        if (in_array($type, [self::TYPE_DATE, self::TYPE_DATETIME], true)) {
            try {
                return Carbon::parse($valeur)->format($type === self::TYPE_DATE ? 'd/m/Y' : 'd/m/Y H:i');
            } catch (\Throwable) {
                return $valeur;
            }
        }

        return $valeur;
    }

    private function montant(float $valeur): string
    {
        return number_format($valeur, 0, ',', ' ');
    }

    private function decimal(float $valeur): string
    {
        return rtrim(rtrim(number_format($valeur, 2, ',', ' '), '0'), ',');
    }
}
