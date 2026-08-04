{{-- Styles communs aux gabarits PDF de bons (S7/S9) : A4, lisible N&B laser. --}}
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
    .entete { border-bottom: 2px solid #111; padding-bottom: 6px; margin-bottom: 10px; }
    .entete h1 { font-size: 15px; margin: 0; }
    .entete .organisme { font-size: 11px; font-weight: bold; }
    .entete .sous-titre { font-size: 9px; color: #444; text-transform: uppercase; letter-spacing: .5px; }
    .meta { width: 100%; margin-bottom: 10px; }
    .meta td { padding: 2px 6px 2px 0; vertical-align: top; }
    .libelle { color: #444; text-transform: uppercase; font-size: 8px; }
    table.donnees { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.donnees th, table.donnees td { border: 0.5px solid #555; padding: 3px 5px; }
    table.donnees th { background: #e8e8e8; text-align: left; text-transform: uppercase; font-size: 8.5px; }
    table.donnees tfoot td { background: #f2f2f2; font-weight: bold; }
    .num { text-align: right; }
    .centre { text-align: center; }
    .mono { font-family: DejaVu Sans Mono, monospace; }
    /* Hachures : distinguer les sections sans dépendre de la couleur (S7) */
    .section-equipements th { background: repeating-linear-gradient(45deg, #f2f2f2, #f2f2f2 3px, #ddd 3px, #ddd 6px); }
    .observation { border: 0.5px solid #555; padding: 6px; margin-bottom: 12px; }
    .signature { width: 100%; margin-top: 18px; border-collapse: collapse; }
    .signature td { width: 50%; vertical-align: top; padding: 4px; }
    .cadre-signature { border: 1px solid #111; height: 70px; padding: 4px; }
    .titre-cadre { font-weight: bold; font-size: 9px; text-transform: uppercase; margin-bottom: 2px; }
    .vide { text-align: center; font-style: italic; color: #444; }
</style>
