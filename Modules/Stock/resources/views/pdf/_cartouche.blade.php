{{--
    Cartouche d'audit normalisé (S9 / amendement UX n°19) — pied de CHAQUE PDF.
    Paramètres : $creePar, $creeLe, $validePar (optionnel), $valideLe (optionnel), $numero (optionnel)
--}}
<table style="width:100%; margin-top: 18px; border-top: 1px solid #333; font-size: 9px; color: #333;">
    <tr>
        <td style="padding-top: 4px;">
            @isset($numero)<strong>{{ $numero }}</strong> — @endisset
            Généré par {{ $creePar ?? '—' }} le {{ ($creeLe ?? now())->format('d/m/Y à H:i') }}
            @isset($validePar) · Validé par {{ $validePar }}@isset($valideLe) le {{ $valideLe->format('d/m/Y à H:i') }}@endisset @endisset
        </td>
        <td style="padding-top: 4px; text-align: right; font-style: italic;">
            Document non modifiable — corrections par contre-mouvement
        </td>
    </tr>
</table>
