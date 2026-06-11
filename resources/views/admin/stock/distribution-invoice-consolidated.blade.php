<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consolidated statement {{ $invoiceNo }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 16px;
            background: #e8e8e8;
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #000000;
            font-size: 12px;
        }
        .sheet {
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 28px 32px 24px;
        }
        .top-header { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .top-header td { vertical-align: top; padding: 0; }
        .logo-box { width: 72px; height: 72px; }
        .logo-box img { width: 72px; height: 72px; object-fit: contain; display: block; }
        .title-block { text-align: right; padding-top: 4px; }
        .title-block h1 { margin: 0; font-size: 26px; font-weight: 700; color: #214F91; }
        .title-block .invoice-number { margin: 8px 0 0 0; font-size: 12px; }
        .info-row { width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 12px; line-height: 1.55; }
        .info-row td { width: 50%; vertical-align: top; padding: 0 10px 0 0; }
        .info-row td:last-child { padding-right: 0; padding-left: 10px; }
        .company-name { font-weight: 700; font-size: 13px; margin-bottom: 6px; }
        .bill-heading { font-weight: 700; margin-bottom: 6px; }
        .items-wrap { border: 1px solid #c8c8c8; margin: 0 0 16px 0; }
        .items-table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .items-table th {
            background: #214F91;
            color: #fff;
            padding: 8px 6px;
            text-align: left;
            font-weight: 700;
        }
        .items-table th.num { text-align: right; }
        .items-table td { padding: 7px 6px; border-bottom: 1px solid #e0e0e0; vertical-align: top; }
        .items-table td.num { text-align: right; white-space: nowrap; }
        .items-table tr.total-row td { font-weight: 700; border-top: 2px solid #214F91; border-bottom: none; background: #f4f6f8; }
        .table-footer-bar { height: 10px; background: #214F91; width: 100%; }
        .total-section { text-align: right; margin: 10px 0 0 0; font-size: 14px; font-weight: 700; }
        .thank-you {
            background: #214F91;
            color: #ffffff;
            text-align: center;
            padding: 12px 10px;
            margin-top: 22px;
            font-weight: 700;
            font-size: 13px;
        }
    </style>
</head>
@php
    $companyName = 'OPTIC EDGE AFRICA';
    $iconPath = public_path('assets/app_icon.png');
    $iconDataUri = '';
    if (is_readable($iconPath)) {
        $iconDataUri = 'data:image/png;base64,' . base64_encode((string) file_get_contents($iconPath));
    }
@endphp
<body>
    <div class="sheet">
        <table class="top-header">
            <tr>
                <td class="logo-box">
                    @if ($iconDataUri !== '')
                        <img src="{{ $iconDataUri }}" alt="">
                    @endif
                </td>
                <td class="title-block">
                    <h1>CONSOLIDATED STATEMENT</h1>
                    <p class="invoice-number">Statement ref: {{ $invoiceNo }}</p>
                    <p class="invoice-number">Period: {{ $periodLabel }}</p>
                </td>
            </tr>
        </table>

        <table class="info-row" role="presentation">
            <tr>
                <td>
                    <div class="company-name">{{ $companyName }}</div>
                    <div><strong>Address:</strong> Dar es Salaam, Sinza Makaburini</div>
                    <div><strong>Email:</strong> info@opticedgeafrica.net</div>
                    <div><strong>Phone:</strong> 0677 - 609929</div>
                </td>
                <td>
                    <div class="bill-heading">Dealer</div>
                    <div><strong>Business name:</strong> {{ $dealerBusinessName }}</div>
                    <div><strong>Outstanding lines:</strong> {{ count($lineRows) }}</div>
                </td>
            </tr>
        </table>

        <p style="margin: 0 0 10px 0; font-size: 11px; color: #333;">
            The following distribution invoices in the selected period still have a balance due. This document is a single summary; original invoice IDs are shown for reference.
        </p>

        <div class="items-wrap">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Date</th>
                        <th>Product</th>
                        <th class="num">Qty</th>
                        <th class="num">Billed (TZS)</th>
                        <th class="num">Paid (TZS)</th>
                        <th class="num">Balance (TZS)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lineRows as $row)
                        <tr>
                            <td>{{ $row['invoice_number'] }}</td>
                            <td>{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d M Y') : '—' }}</td>
                            <td>{{ $row['product_name'] }}</td>
                            <td class="num">{{ number_format($row['quantity']) }}</td>
                            <td class="num">{{ number_format($row['total_sell'], 2) }}</td>
                            <td class="num">{{ number_format($row['paid'], 2) }}</td>
                            <td class="num">{{ number_format($row['outstanding'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="6" class="num">Total balance due</td>
                        <td class="num">{{ number_format($totalOutstanding, 2) }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="table-footer-bar"></div>
        </div>

        <div class="total-section">
            Total outstanding: {{ number_format($totalOutstanding, 2) }} TZS
        </div>

        <div class="thank-you">Thank you!</div>
    </div>
</body>
</html>
