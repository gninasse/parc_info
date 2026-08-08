<?php

namespace Modules\Achat\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\Document;
use Modules\Core\Models\User;

/**
 * Le dossier documentaire du BC (SFD §6.2, A16, IA-13).
 *
 * Deux doctrines selon le moment :
 *
 *   - AVANT validation du bon : la pièce s'efface réellement (fichier + ligne),
 *     un brouillon n'engage rien ;
 *   - APRÈS validation : la suppression devient une PIERRE TOMBALE — le
 *     fichier physique est effacé, la ligne reste, motivée et signée. Une
 *     pièce gênante ne disparaît pas silencieusement.
 *
 * Le stockage est HORS racine web (disque `local`, jamais de symlink public),
 * sous un nom NEUTRE généré par Laravel : le nom d'origine, potentiellement
 * hostile, ne touche jamais le système de fichiers — il n'est restitué qu'en
 * en-tête de téléchargement.
 */
class DocumentsBonCommande
{
    public const EVENEMENT_DEPOT = 'depot_piece';

    public const EVENEMENT_SUPPRESSION = 'suppression_piece';

    public const EVENEMENT_PIERRE_TOMBALE = 'pierre_tombale_piece';

    /** Disque privé : aucun accès direct par URL. */
    public const DISQUE = 'local';

    public const DOSSIER = 'achat/documents';

    /** M-05 — dépose une pièce sur le bon. */
    public function deposer(BonCommande $bon, UploadedFile $fichier, string $type, User $auteur): Document
    {
        return DB::transaction(function () use ($bon, $fichier, $type, $auteur) {
            $chemin = $fichier->store(self::DOSSIER.'/'.$bon->id, self::DISQUE);

            $document = Document::create([
                'bon_commande_id' => $bon->id,
                'type' => $type,
                'chemin' => $chemin,
                'nom_original' => $fichier->getClientOriginalName(),
                'mime' => $fichier->getMimeType(), // détecté SERVEUR, pas déclaré client
                'taille' => $fichier->getSize(),
                'created_by' => $auteur->id,
            ]);

            activity('achat')
                ->performedOn($bon)
                ->causedBy($auteur)
                ->withProperties([
                    'document_id' => $document->id,
                    'type' => $type,
                    'nom_original' => $document->nom_original,
                ])
                ->log(self::EVENEMENT_DEPOT);

            return $document;
        });
    }

    /**
     * Suppression AVANT validation du bon : réelle, fichier et ligne (SW
     * simple côté écran). Le journal garde quand même l'acte.
     */
    public function supprimer(Document $document, User $auteur): void
    {
        DB::transaction(function () use ($document, $auteur) {
            $this->effacerFichier($document);

            activity('achat')
                ->performedOn($document->bonCommande)
                ->causedBy($auteur)
                ->withProperties([
                    'nom_original' => $document->nom_original,
                    'type' => $document->type,
                ])
                ->log(self::EVENEMENT_SUPPRESSION);

            $document->delete();
        });
    }

    /**
     * M-08 — suppression APRÈS validation : pierre tombale. Le fichier
     * physique est effacé, la ligne demeure — grisée, motivée, signée,
     * horodatée (A16) — et la chronologie du bon raconte l'acte.
     */
    public function poserPierreTombale(Document $document, User $auteur, string $motif): Document
    {
        return DB::transaction(function () use ($document, $auteur, $motif) {
            $this->effacerFichier($document);

            $document->forceFill([
                'chemin' => null, // le fichier n'existe plus, le chemin non plus
                'est_supprime' => true,
                'supprime_par' => $auteur->id,
                'supprime_le' => now(),
                'motif_suppression' => $motif,
            ])->save();

            activity('achat')
                ->performedOn($document->bonCommande)
                ->causedBy($auteur)
                ->withProperties([
                    'document_id' => $document->id,
                    'nom_original' => $document->nom_original,
                    'motif' => $motif,
                ])
                ->log(self::EVENEMENT_PIERRE_TOMBALE);

            return $document->refresh();
        });
    }

    private function effacerFichier(Document $document): void
    {
        if ($document->chemin !== null) {
            Storage::disk(self::DISQUE)->delete($document->chemin);
        }
    }
}
