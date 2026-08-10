<?php

namespace Modules\Catalogue\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Catalogue\Http\Requests\StoreContactFournisseurRequest;
use Modules\Catalogue\Http\Requests\UpdateContactFournisseurRequest;
use Modules\Catalogue\Models\ContactFournisseur;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Catalogue\Services\ContactsFournisseurService;

/**
 * Les contacts d'un fournisseur (onglet de la fiche).
 *
 * Toutes les routes sont IMBRIQUÉES sous le fournisseur
 * (`/fournisseurs/{fournisseur}/contacts/...`), et c'est délibéré : le
 * rattachement est ainsi vérifié à chaque appel. Une route plate
 * `/contacts/{id}` obligerait à contrôler à la main, dans chaque méthode, que
 * le contact appartient bien au fournisseur affiché — et il suffirait d'un
 * oubli pour qu'un identifiant deviné laisse modifier le carnet d'adresses
 * d'un autre fournisseur.
 */
class ContactFournisseurController extends Controller implements HasMiddleware
{
    public function __construct(private readonly ContactsFournisseurService $contacts) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:catalogue.contacts.index', only: ['index', 'show']),
            new Middleware('permission:catalogue.contacts.store', only: ['store']),
            new Middleware('permission:catalogue.contacts.update', only: ['update', 'definirPrincipal']),
            new Middleware('permission:catalogue.contacts.destroy', only: ['destroy']),
        ];
    }

    /** La charge de la table de l'onglet Contacts. */
    public function index(Fournisseur $fournisseur): JsonResponse
    {
        $contacts = $fournisseur->contacts()
            ->ordreAffichage()
            ->get()
            ->map(fn (ContactFournisseur $contact) => [
                'id' => $contact->id,
                'nom' => $contact->nom,
                'prenom' => $contact->prenom,
                'nom_complet' => $contact->nom_complet,
                'fonction' => $contact->fonction,
                'telephone' => $contact->telephone,
                'email' => $contact->email,
                'notes' => $contact->notes,
                'est_principal' => $contact->est_principal,
                'est_actif' => $contact->est_actif,
            ]);

        return response()->json([
            'success' => true,
            'total' => $contacts->count(),
            'rows' => $contacts,
        ]);
    }

    public function store(StoreContactFournisseurRequest $request, Fournisseur $fournisseur): JsonResponse
    {
        $contact = $this->contacts->creer($fournisseur, $request->validated());

        return response()->json([
            'success' => true,
            'message' => "Le contact « {$contact->nom_complet} » a été ajouté.",
            'data' => ['id' => $contact->id],
        ]);
    }

    /**
     * Les données d'un contact, pour pré-remplir la modale d'édition.
     *
     * `scopeBindings` (déclaré à la route) garantit que le contact demandé
     * appartient au fournisseur de l'URL : sinon, 404.
     */
    public function show(Fournisseur $fournisseur, ContactFournisseur $contact): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $contact->only([
                'id', 'nom', 'prenom', 'fonction', 'telephone',
                'email', 'notes', 'est_principal', 'est_actif',
            ]),
        ]);
    }

    public function update(
        UpdateContactFournisseurRequest $request,
        Fournisseur $fournisseur,
        ContactFournisseur $contact
    ): JsonResponse {
        $contact = $this->contacts->modifier($contact, $request->validated());

        return response()->json([
            'success' => true,
            'message' => "Le contact « {$contact->nom_complet} » a été modifié.",
        ]);
    }

    public function destroy(Fournisseur $fournisseur, ContactFournisseur $contact): JsonResponse
    {
        $nom = $contact->nom_complet;

        $this->contacts->supprimer($contact);

        return response()->json([
            'success' => true,
            'message' => "Le contact « {$nom} » a été supprimé.",
        ]);
    }

    /** Désigne l'interlocuteur principal (et un seul). */
    public function definirPrincipal(Fournisseur $fournisseur, ContactFournisseur $contact): JsonResponse
    {
        $contact = $this->contacts->definirPrincipal($contact);

        return response()->json([
            'success' => true,
            'message' => "« {$contact->nom_complet} » est désormais le contact principal.",
        ]);
    }
}
