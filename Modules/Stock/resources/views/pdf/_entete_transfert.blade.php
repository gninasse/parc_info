{{--
    En-tête commun aux deux modèles d'impression du bon de transfert.
    Paramètres : $transfert, $titre, $sousTitre (optionnel)
--}}
<div class="entete">
    <div class="organisme">CHU-YO — Centre Hospitalier Universitaire Yalgado Ouédraogo</div>
    <h1>{{ $titre }} {{ $transfert->numero }}</h1>
    @isset($sousTitre)
        <div class="sous-titre">{{ $sousTitre }}</div>
    @endisset
</div>

<table class="meta">
    <tr>
        <td>
            <div class="libelle">Magasin source</div>
            {{ $transfert->magasinSource?->libelle }} ({{ $transfert->magasinSource?->code }})
        </td>
        <td>
            <div class="libelle">Magasin cible</div>
            {{ $transfert->magasinCible?->libelle }} ({{ $transfert->magasinCible?->code }})
        </td>
        <td>
            <div class="libelle">Transporté par</div>
            {{ $transfert->transporte_par_nom ?? '—' }}
            @if($transfert->transporteParEmploye) ({{ trim($transfert->transporteParEmploye->prenom.' '.$transfert->transporteParEmploye->nom) }}) @endif
        </td>
        <td>
            <div class="libelle">Daté du</div>
            {{ $transfert->date_document?->format('d/m/Y') }}
            <div class="libelle" style="margin-top:3px;">Validé le</div>
            {{ $transfert->valide_le?->format('d/m/Y H:i') }}
        </td>
    </tr>
</table>
