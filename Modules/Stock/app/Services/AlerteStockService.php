<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\User;
use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Contracts\GrhIntegrationInterface;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Notifications\StockEnRupture;
use Modules\Stock\Notifications\StockSousSeuil;

/**
 * F8 — Alerte les responsables d'un magasin quand une sortie fait passer un
 * article en rupture ou sous son seuil global (achat_articles.seuil_alerte).
 */
class AlerteStockService
{
    public function __construct(
        protected AchatIntegrationInterface $achat,
        protected GrhIntegrationInterface $grh,
    ) {}

    /** À appeler après la transaction d'une sortie ou d'un transfert sortant. */
    public function verifierApresSortie(int $articleId, int $magasinId): void
    {
        $stock = StockArticleMagasin::where('article_id', $articleId)
            ->where('magasin_id', $magasinId)
            ->first();

        if (! $stock) {
            return;
        }

        $article = $this->achat->article($articleId);
        $seuil = (int) ($article['seuil_alerte'] ?? 0);
        $quantite = $stock->quantite_actuelle;

        if ($quantite > $seuil && $quantite > 0) {
            return;
        }

        $magasin = Magasin::find($magasinId);
        $destinataires = $this->destinataires($magasin);

        if ($destinataires->isEmpty()) {
            return;
        }

        $libelle = $article['designation'] ?? "Article #{$articleId}";

        if ($quantite === 0) {
            Notification::send($destinataires, new StockEnRupture($libelle, $magasin->libelle, $articleId));
        } else {
            Notification::send($destinataires, new StockSousSeuil($libelle, $magasin->libelle, $articleId, $quantite, $seuil));
        }
    }

    /** Comptes utilisateurs des responsables en cours du magasin. */
    protected function destinataires(Magasin $magasin)
    {
        $employeIds = $magasin->responsables
            ->filter(fn ($responsable) => $responsable->estEnCours())
            ->pluck('employe_id')
            ->all();

        return User::whereIn('id', $this->grh->utilisateursDesEmployes($employeIds))->get();
    }
}
