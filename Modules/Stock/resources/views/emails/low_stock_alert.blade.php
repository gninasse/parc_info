<!DOCTYPE html>
<html>
<head>
    <title>Alerte Stock Critique</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <h2 style="color: #d9534f;">Alerte : Niveau de stock critique</h2>
    <p>Bonjour,</p>
    <p>Le niveau de stock de l'article suivant est passé en dessous ou égal à son seuil d'alerte :</p>
    <table style="border-collapse: collapse; width: 100%; max-width: 600px; margin-bottom: 20px;">
        <tr style="background-color: #f2f2f2;">
            <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Article</th>
            <td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">{{ $stock->article?->designation }} (<code>{{ $stock->article?->code_article }}</code>)</td>
        </tr>
        <tr>
            <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Magasin</th>
            <td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">{{ $stock->magasin?->nom }} ({{ $stock->magasin?->code }})</td>
        </tr>
        <tr style="background-color: #f2f2f2;">
            <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Quantité actuelle</th>
            <td style="border: 1px solid #dddddd; text-align: left; padding: 8px; font-weight: bold; color: #d9534f;">{{ $stock->quantite_actuelle }}</td>
        </tr>
        <tr>
            <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Seuil d'alerte</th>
            <td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">{{ $stock->seuil_alerte }}</td>
        </tr>
    </table>
    <p>Veuillez faire le nécessaire pour réapprovisionner cet article.</p>
    <hr style="border: 0; border-top: 1px solid #ccc; margin-top: 30px;">
    <p style="font-size: 0.8em; color: #777;">Ceci est un message automatique envoyé par la Gestion des Stocks de ParcInfo.</p>
</body>
</html>
