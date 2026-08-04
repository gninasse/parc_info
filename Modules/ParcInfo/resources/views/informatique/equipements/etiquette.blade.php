<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étiquette - {{ $equipement->code_inventaire }}</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Libre+Barcode+128&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .no-print-area {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }

        .btn-print {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-print:hover {
            background-color: #1d4ed8;
        }

        /* Label styling (standard thermal printer size: 80mm x 50mm) */
        .label-container {
            width: 320px;
            height: 200px;
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .label-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 5px;
        }

        .brand {
            font-size: 12px;
            font-weight: 700;
            color: #1e3a8a;
        }

        .category {
            font-size: 10px;
            font-weight: 600;
            color: #4b5563;
            text-transform: uppercase;
            background-color: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .label-body {
            display: flex;
            gap: 15px;
            align-items: center;
            margin: 10px 0;
            flex-grow: 1;
        }

        .qr-code-box {
            width: 80px;
            height: 80px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #f3f4f6;
            border-radius: 4px;
        }

        .info-box {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex-grow: 1;
        }

        .model-name {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 175px;
        }

        .info-item {
            font-size: 10px;
            color: #4b5563;
        }

        .info-value {
            font-weight: 600;
            color: #111827;
        }

        .label-footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }

        /* Google Font Barcode */
        .barcode {
            font-family: 'Libre Barcode 128', sans-serif;
            font-size: 38px;
            line-height: 1;
            margin: 0;
            padding: 0;
            color: black;
            text-align: center;
            user-select: none;
        }

        .inventory-code {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #111827;
            text-align: center;
        }

        @media print {
            body {
                background-color: white;
                margin: 0;
                padding: 0;
                display: block;
                min-height: auto;
            }

            .no-print-area {
                display: none;
            }

            .label-container {
                border: none;
                box-shadow: none;
                margin: 0;
                width: 80mm;
                height: 50mm;
                page-break-inside: avoid;
            }
        }
    </style>
    <!-- Include QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>

<body>

    <div class="no-print-area">
        <button onclick="window.print()" class="btn-print">
            <svg style="width: 16px; height: 16px; fill: currentColor;" viewBox="0 0 512 512">
                <path
                    d="M128 0C92.7 0 64 28.7 64 64v96h64V64c0-17.7 14.3-32 32-32h192c17.7 0 32 14.3 32 32v96h64V64c0-35.3-28.7-64-64-64H128zM400 224a24 24 0 1 0 0-48 24 24 0 1 0 0 48zM384 320V224H128v96H384zm64-96c0-17.7-14.3-32-32-32H96c-17.7 0-32 14.3-32 32v128h64v-64h256v64h64V224zM96 448c0 17.7 14.3 32 32 32h256c17.7 0 32-14.3 32-32v-32H96v32z" />
            </svg>
            Imprimer l'étiquette
        </button>
    </div>

    <div class="label-container">
        <div class="label-header">
            <span class="brand">CHU-YALGADO</span>
            <span class="category">{{ $equipement->categorie->libelle }}</span>
        </div>
        <div class="label-body">
            <div class="qr-code-box" id="qrcode"></div>
            <div class="info-box">
                <div class="model-name" title="{{ $equipement->marque?->libelle }} {{ $equipement->modele }}">
                    {{ $equipement->marque?->libelle }} {{ $equipement->modele }}
                </div>
                <div class="info-item">S/N: <span class="info-value">{{ $equipement->numero_serie ?: 'N/A' }}</span>
                </div>
                @if($equipement->date_mise_en_service)
                    <div class="info-item">Mise en service: <span
                            class="info-value">{{ $equipement->date_mise_en_service->format('d/m/Y') }}</span></div>
                @else
                    <div class="info-item">Acquis le: <span
                            class="info-value">{{ $equipement->date_acquisition?->format('d/m/Y') ?: 'N/A' }}</span></div>
                @endif
                <div class="info-item">Statut: <span
                        class="info-value">{{ str_replace('_', ' ', $equipement->statut) }}</span></div>
            </div>
        </div>
        <div class="label-footer">
            <div class="barcode">{{ $equipement->code_inventaire }}</div>
            <div class="inventory-code">{{ $equipement->code_inventaire }}</div>
        </div>
    </div>

    <script>
        {{-- Str::plural : même pluralisation que l'enregistrement des routes (ORDI → ORDIS, pas ORDIs) --}}
        const qrContent = "{{ route('parc-info.' . \Illuminate\Support\Str::plural($equipement->categorie->code) . '.show', $equipement->id) }}";
        new QRCode(document.getElementById("qrcode"), {
            text: qrContent,
            width: 80,
            height: 80,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        window.addEventListener('load', () => {
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>

</html>