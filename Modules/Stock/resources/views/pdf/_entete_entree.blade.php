{{--
    En-tête commun aux deux modèles d'impression du bon d'entrée.
    Paramètres : $entree, $titre (libellé du document), $sousTitre (optionnel)
--}}
<div class="entete">
    <div class="organisme">CHU-YO — Centre Hospitalier Universitaire Yalgado Ouédraogo</div>
    <h1>{{ $titre }} {{ $entree->numero }}</h1>
    @isset($sousTitre)
        <div class="sous-titre">{{ $sousTitre }}</div>
    @endisset
</div>

<table class="meta">
    <tr>
        <td>
            <div class="libelle">Magasin</div>
            {{ $entree->magasin?->libelle }} ({{ $entree->magasin?->code }})
        </td>
        <td>
            <div class="libelle">Nature</div>
            {{ $entree->nature === 'retour' ? 'Retour' : 'Livraison' }}
        </td>
        <td>
            <div class="libelle">Date de livraison</div>
            {{ $entree->date_document?->format('d/m/Y') }}
        </td>
        <td>
            <div class="libelle">Date de validation</div>
            {{ $entree->valide_le?->format('d/m/Y H:i') }}
        </td>
    </tr>
    <tr>
        <td>
            <div class="libelle">Fournisseur</div>
            {{ $entree->fournisseur?->raison_sociale ?? '—' }}
        </td>
        <td colspan="3">
            <div class="libelle">Référence externe</div>
            {{ $entree->reference_externe ?? '—' }}
        </td>
    </tr>
</table>
