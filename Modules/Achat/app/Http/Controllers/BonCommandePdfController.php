<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Services\CalculMontantsService;
use Modules\Achat\Services\MontantEnLettres;

/**
 * D-07 — Le PDF du bon de commande : l'objet juridique imprimable (SPEC_UX §17).
 *
 * Deux règles structurent ce contrôleur :
 *
 *   - IA-1 : les montants imprimés sont CEUX DE LA BASE, dénormalisés à la
 *     validation — le gabarit ne calcule rien, il affiche ;
 *   - un bon ANNULÉ n'a pas de PDF « propre » : il est sans effet, mais s'il a
 *     circulé avant l'annulation, la version filigranée ANNULÉ reste
 *     produisible pour lever toute ambiguïté chez le fournisseur.
 *
 * Le brouillon a une PRÉVISUALISATION filigranée « BROUILLON — SANS VALEUR »,
 * jamais proposée dans les listes : elle sert à relire avant soumission, pas à
 * circuler.
 */
class BonCommandePdfController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly CalculMontantsService $montants,
        private readonly MontantEnLettres $enLettres,
    ) {}

    public static function middleware(): array
    {
        // SFD §5 : « index » couvre listes, fiches et PDF en lecture.
        return [
            new Middleware('permission:achat.bons_commande.index', only: ['pdf']),
        ];
    }

    public function pdf(Request $request, int $id)
    {
        $bon = BonCommande::query()
            ->with(['lignes', 'fournisseur', 'serviceDemandeur', 'createur:id,name', 'validateur:id,name'])
            ->findOrFail($id);

        return Pdf::loadView('achat::pdf.bon_commande', [
            'bon' => $bon,
            'filigrane' => $this->filigrane($bon),
            'qrSvg' => $this->qrDuNumero($bon),
            'decomposition' => $this->montants->decompositionParTaux($bon->lignes),
            'montantEnLettres' => $this->enLettres->enFcfa((float) $bon->montant_ttc),
        ])
            ->setPaper('a4')
            ->stream($this->nomDeFichier($bon));
    }

    /**
     * Filigrane selon le statut (SPEC_UX §17) : il qualifie la valeur
     * juridique du document, il n'est jamais décoratif.
     */
    private function filigrane(BonCommande $bon): ?string
    {
        return match (true) {
            // Un document sans numéro n'engage rien : la mention doit
            // interdire toute confusion avec un bon officiel.
            $bon->numero === null => 'BROUILLON — SANS VALEUR',
            $bon->statut === BonCommande::STATUT_ANNULE => 'ANNULÉ',
            (bool) $bon->est_regularisation => 'RÉGULARISATION',
            default => null,
        };
    }

    /**
     * QR code du NUMÉRO SEUL (SPEC_UX §17) : c'est ce que la douchette du
     * magasin lira pour ouvrir la modale M-02 côté Stock. Pas d'URL, pas de
     * JSON — un numéro, rien d'autre : tout scanner sait le restituer.
     *
     * Rendu en SVG vectoriel, embarqué en data URI : dompdf ne rend PAS les
     * balises <svg> inline (vérifié : zone blanche sur le PDF produit), mais
     * il rastérise correctement une <img src="data:image/svg+xml">. Le
     * vectoriel garde le QR net à l'impression — un PNG basse définition
     * devient illisible à la douchette.
     */
    private function qrDuNumero(BonCommande $bon): ?string
    {
        if ($bon->numero === null) {
            return null; // un brouillon n'a rien à scanner
        }

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(220, 1),
                new SvgImageBackEnd
            )
        );

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($bon->numero));
    }

    private function nomDeFichier(BonCommande $bon): string
    {
        $base = $bon->numero ?? 'brouillon-'.$bon->id;

        return "bon-commande-{$base}.pdf";
    }
}
