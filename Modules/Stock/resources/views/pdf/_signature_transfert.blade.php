{{--
    Double cadre de signature du transfert (UX §5), commun aux deux modèles :
    « Départ — magasinier source / transporteur » · « Arrivée — magasinier cible ».
--}}
<table class="signature">
    <tr>
        <td>
            <div class="titre-cadre">Départ — magasinier source / transporteur</div>
            <div class="cadre-signature">
                {{ $transfert->createur?->name }}
                @if($transfert->transporte_par_nom) / {{ $transfert->transporte_par_nom }} @endif
            </div>
        </td>
        <td>
            <div class="titre-cadre">Arrivée — magasinier cible</div>
            <div class="cadre-signature"></div>
        </td>
    </tr>
</table>
