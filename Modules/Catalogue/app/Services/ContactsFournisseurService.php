<?php

namespace Modules\Catalogue\Services;

use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\ContactFournisseur;
use Modules\Catalogue\Models\Fournisseur;

/**
 * Écritures sur les contacts d'un fournisseur.
 *
 * Tout passe par ce service plutôt que par le contrôleur, pour une raison
 * précise : la règle « UN SEUL contact principal par fournisseur » ne peut
 * pas être tenue par la base.
 *
 * Un index unique partiel (`UNIQUE ... WHERE est_principal`) ferait l'affaire
 * sous PostgreSQL, mais ne s'écrit pas de la même façon sous SQLite, et le
 * projet doit tourner sur les deux. La règle est donc applicative — et si
 * elle est applicative, elle doit vivre à UN SEUL endroit, sous transaction,
 * sans quoi deux écrans finiront par la faire diverger.
 */
class ContactsFournisseurService
{
    /**
     * Crée un contact.
     *
     * Le premier contact d'un fournisseur devient automatiquement le
     * principal : sans cela, on obtiendrait des fournisseurs pourvus de
     * contacts mais sans interlocuteur désigné, et la fiche n'aurait rien à
     * mettre en avant.
     */
    public function creer(Fournisseur $fournisseur, array $donnees): ContactFournisseur
    {
        return DB::transaction(function () use ($fournisseur, $donnees) {
            $estPremier = ! $fournisseur->contacts()->exists();

            $contact = new ContactFournisseur($donnees);
            $contact->fournisseur_id = $fournisseur->id;
            $contact->est_principal = $estPremier || (bool) ($donnees['est_principal'] ?? false);
            $contact->save();

            if ($contact->est_principal) {
                $this->demarquerLesAutres($fournisseur, $contact->id);
            }

            return $contact;
        });
    }

    public function modifier(ContactFournisseur $contact, array $donnees): ContactFournisseur
    {
        return DB::transaction(function () use ($contact, $donnees) {
            $contact->fill($donnees);

            // Un contact désactivé ne peut pas rester l'interlocuteur
            // principal : la fiche mettrait en avant quelqu'un qu'on a
            // justement cessé d'appeler.
            if (! $contact->est_actif) {
                $contact->est_principal = false;
            }

            $contact->save();

            if ($contact->est_principal) {
                $this->demarquerLesAutres($contact->fournisseur, $contact->id);
            }

            return $contact;
        });
    }

    /**
     * Supprime un contact.
     *
     * Si le principal disparaît, le plus ancien contact actif restant prend
     * sa place. Laisser le fournisseur sans principal obligerait l'utilisateur
     * à y penser lui-même, et personne n'y pense.
     */
    public function supprimer(ContactFournisseur $contact): void
    {
        DB::transaction(function () use ($contact) {
            $etaitPrincipal = $contact->est_principal;
            $fournisseur = $contact->fournisseur;

            $contact->delete();

            if (! $etaitPrincipal || $fournisseur === null) {
                return;
            }

            $remplacant = $fournisseur->contacts()
                ->where('est_actif', true)
                ->orderBy('id')
                ->first();

            $remplacant?->forceFill(['est_principal' => true])->save();
        });
    }

    /** Désigne un contact comme principal (et un seul). */
    public function definirPrincipal(ContactFournisseur $contact): ContactFournisseur
    {
        return DB::transaction(function () use ($contact) {
            $contact->forceFill([
                'est_principal' => true,
                // Désigner un contact inactif comme principal serait
                // contradictoire : on le réactive plutôt que de refuser, car
                // c'est manifestement l'intention de l'utilisateur.
                'est_actif' => true,
            ])->save();

            $this->demarquerLesAutres($contact->fournisseur, $contact->id);

            return $contact;
        });
    }

    /**
     * Retire la marque « principal » à tous les AUTRES contacts.
     *
     * Une mise à jour d'ensemble, et non une boucle : entre les deux, un
     * enregistrement pourrait s'intercaler et deux contacts se retrouveraient
     * principaux.
     */
    private function demarquerLesAutres(?Fournisseur $fournisseur, int $sauf): void
    {
        if ($fournisseur === null) {
            return;
        }

        $fournisseur->contacts()
            ->whereKeyNot($sauf)
            ->where('est_principal', true)
            ->update(['est_principal' => false]);
    }
}
