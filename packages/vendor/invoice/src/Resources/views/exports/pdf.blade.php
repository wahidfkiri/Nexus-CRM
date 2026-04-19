<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Export Factures</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 18px; margin-bottom: 8px; }
        p { color: #64748b; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Export des factures</h1>
    <p>Généré le {{ now()->format('d/m/Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>Numéro</th>
                <th>Client</th>
                <th>Statut</th>
                <th>Date</th>
                <th class="right">Total</th>
                <th class="right">Reste dû</th>
            </tr>
        </thead>
        <tbody>
        @foreach($invoices as $invoice)
            <tr>
                <td>{{ $invoice->number }}</td>
                <td>{{ $invoice->client?->company_name }}</td>
                <td>{{ $invoice->status_label ?? $invoice->status }}</td>
                <td>{{ optional($invoice->issue_date)->format('d/m/Y') }}</td>
                <td class="right">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $invoice->currency }}</td>
                <td class="right">{{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $invoice->currency }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
