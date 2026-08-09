<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Mouvement;

/**
 * BR-02 — le BORDEREAU DE RÉCEPTION : la preuve signable de la livraison.
 *
 * C'est le pendant « entrée » du bon de commande, et surtout la pièce qu'on
 * fait signer AU LIVREUR. Sans signature contradictoire, une réclamation
 * « il manquait deux cartons » ne pèse rien face au fournisseur trois
 * semaines plus tard.
 *
 * Deux partis pris, tous deux dans le recueil :
 *
 *   - AUCUN MONTANT par défaut (`stock.afficher_couts_bordereau`) : c'est un
 *     document de quai, le livreur n'a pas à lire les prix négociés ;
 *   - le BC lié y figure : le magasinier et le livreur voient la même
 *     référence de commande, ce qui coupe court aux malentendus.
 */
class BordereauReceptionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            // Lecture : même permission que la fiche de l'entrée (SFD §5).
            new Middleware('permission:stock.entrees.index'),
        ];
    }

    public function __invoke(int $id)
    {
        $entree = Entree::query()
            ->with([
                'magasin',
                'fournisseur',
                'lignes.article',
                'lignes.equipement',
                'createur:id,name',
                'valideur:id,name',
                'bonCommande:id,numero',
            ])
            ->findOrFail($id);

        // Un bordereau atteste d'une réception FAITE : un brouillon n'atteste
        // de rien, et un livreur ne signe pas une intention.
        abort_unless($entree->estValide(), 404, 'Le bordereau n\'existe qu\'une fois le bon validé.');

        return Pdf::loadView('stock::pdf.bordereau_reception', [
            'entree' => $entree,
            'quantitatives' => $entree->lignes->filter(
                fn ($ligne) => $ligne->article_id !== null && $ligne->article?->nature !== 'equipement'
            ),
            'unites' => $this->unitesSerialisees($entree),
            'blFournisseurs' => $entree->documents()
                ->where('est_supprime', false)
                ->where('type', Document::TYPE_BL_FOURNISSEUR)
                ->get(),
            'afficherCouts' => (bool) config('stock.afficher_couts_bordereau', false),
            // Filigrane si un contre-mouvement a défait tout ou partie de
            // cette entrée : le document en circulation doit le dire.
            'contrePassation' => $this->contrePassation($entree),
            'qr' => $this->qrDuNumero($entree),
            'genereLe' => now(),
        ])->setPaper('a4')->stream($this->nomDeFichier($entree));
    }

    /**
     * Les unités sérialisées créées par cette entrée — l'annexe des numéros
     * de série. C'est ce que le magasinier vérifie carton par carton.
     */
    private function unitesSerialisees(Entree $entree)
    {
        return Mouvement::query()
            ->where('entree_id', $entree->id)
            ->whereNotNull('equipement_id')
            ->with('equipement:id,code_inventaire,numero_serie,modele,etat')
            ->get()
            ->map(fn (Mouvement $mouvement) => $mouvement->equipement)
            ->filter()
            ->values();
    }

    /**
     * Un contre-mouvement portant sur un mouvement de cette entrée : le
     * bordereau imprimé porte alors son filigrane et son renvoi.
     */
    private function contrePassation(Entree $entree): ?array
    {
        $origines = Mouvement::query()->where('entree_id', $entree->id)->pluck('id');

        if ($origines->isEmpty()) {
            return null;
        }

        $contre = Mouvement::query()
            ->whereIn('mouvement_origine_id', $origines)
            ->orderBy('id')
            ->get(['id', 'motif', 'created_at']);

        if ($contre->isEmpty()) {
            return null;
        }

        return [
            'nombre' => $contre->count(),
            'references' => $contre->pluck('id')->implode(', '),
            'motif' => $contre->first()->motif,
        ];
    }

    /**
     * QR du NUMÉRO SEUL, en SVG data URI.
     *
     * Même technique que le PDF du bon de commande : dompdf ne rend pas les
     * <svg> inline (zone blanche), mais rastérise une <img src="data:...">.
     * Le vectoriel garde le code net à l'impression — un PNG basse
     * définition devient illisible à la douchette.
     */
    private function qrDuNumero(Entree $entree): ?string
    {
        if ($entree->numero === null) {
            return null;
        }

        $writer = new Writer(
            new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd)
        );

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($entree->numero));
    }

    private function nomDeFichier(Entree $entree): string
    {
        return 'bordereau-reception-'.($entree->numero ?? $entree->id).'.pdf';
    }
}
