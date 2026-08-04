{{--
    En-tête commun aux deux modèles d'impression du bon de sortie.
    Paramètres : $sortie, $titre, $sousTitre (optionnel)
--}}
<div class="entete">
    <div class="organisme">CHU-YO — Centre Hospitalier Universitaire Yalgado Ouédraogo</div>
    <h1>{{ $titre }} {{ $sortie->numero }}</h1>
    @isset($sousTitre)
        <div class="sous-titre">{{ $sousTitre }}</div>
    @endisset
</div>

<table class="meta">
    <tr>
        <td>
            <div class="libelle">Magasin</div>
            {{ $sortie->magasin?->libelle }} ({{ $sortie->magasin?->code }})
        </td>
        <td>
            <div class="libelle">Bénéficiaire</div>
            {{ $sortie->beneficiaire_libelle ?? $sortie->beneficiaire_type }}
        </td>
        <td>
            <div class="libelle">Remis à</div>
            {{ $sortie->remis_a_nom ?? '—' }}
            @if($sortie->remisAEmploye) ({{ trim($sortie->remisAEmploye->prenom.' '.$sortie->remisAEmploye->nom) }}) @endif
        </td>
    </tr>
    <tr>
        <td>
            <div class="libelle">Motif</div>
            {{ config('stock.motifs_sortie')[$sortie->motif_type] ?? $sortie->motif_type }}
            @if($sortie->motif_texte) — {{ $sortie->motif_texte }} @endif
        </td>
        <td>
            <div class="libelle">Date du bon</div>
            {{ $sortie->date_document?->format('d/m/Y') }}
            @if($sortie->remise_reelle_le)
                <div class="libelle" style="margin-top:3px;">Remise réelle</div>
                {{ $sortie->remise_reelle_le->format('d/m/Y H:i') }}
            @endif
        </td>
        <td>
            <div class="libelle">Validé le</div>
            {{ $sortie->valide_le?->format('d/m/Y H:i') }}
        </td>
    </tr>
</table>
