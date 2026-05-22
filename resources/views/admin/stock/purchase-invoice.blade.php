<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Invoice {{ $invoiceNo }}</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 14px;
            background: #d1d5db;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            font-size: 14px;
        }
        .sheet {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #9ca3af;
            background: #ffffff;
            padding: 28px 32px;
        }
        .header-row {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            border-bottom: 2px solid #fa8900;
            padding-bottom: 16px;
        }
        .logo-box {
            width: 100px;
            height: 80px;
            background: #fa8900;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .logo-box svg {
            width: 60px;
            height: 60px;
            fill: #ffffff;
        }
        .header-text h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            color: #1d4e9e;
            letter-spacing: 0.05em;
        }
        .header-text p {
            margin: 4px 0 0 0;
            font-size: 12px;
            color: #666;
        }
        .invoice-number {
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
        }
        .content-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        .block {
            font-size: 13px;
            line-height: 1.6;
        }
        .block-title {
            font-weight: 700;
            color: #1d4e9e;
            margin-bottom: 8px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .block-line {
            margin-bottom: 4px;
        }
        .block-line strong {
            font-weight: 700;
            display: inline-block;
            min-width: 80px;
        }
        .items-wrap {
            margin: 24px 0;
            border: 1px solid #d1d5db;
            background: #ffffff;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .items-table thead th {
            background: #1d4e9e;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            text-align: left;
            padding: 10px 12px;
            border: none;
        }
        .items-table thead th.num {
            text-align: right;
        }
        .items-table tbody td {
            font-size: 13px;
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            word-break: break-word;
        }
        .items-table tbody td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .items-table tbody tr:last-child td {
            border-bottom: 2px solid #1d4e9e;
        }
        .item-col { width: 40%; }
        .qty-col { width: 15%; }
        .unit-col { width: 22%; }
        .total-col { width: 23%; }
        .total-section {
            text-align: right;
            margin-top: 20px;
            padding-top: 12px;
            border-top: 2px solid #1d4e9e;
        }
        .total-line {
            font-size: 14px;
            margin-bottom: 8px;
        }
        .total-amount {
            font-size: 18px;
            font-weight: 700;
            color: #1d4e9e;
        }
        .thank-you {
            background: #1d4e9e;
            color: #ffffff;
            text-align: center;
            padding: 12px;
            margin-top: 24px;
            font-weight: 700;
            font-size: 14px;
        }
    </style>
</head>
@php
    $companyName = 'OPTIC EDGE AFRICA';
    $formattedDate = $purchase->date ? \Carbon\Carbon::parse($purchase->date)->format('d M Y') : '';
    $distributorName = $purchase->distributor_name ?? $purchase->vendor?->name ?? 'N/A';
    $branchName = $purchase->branch?->name ?? 'Main Branch';
    $productName = $purchase->product
        ? (($purchase->product->category?->name ?? 'N/A') . ' - ' . $purchase->product->name)
        : 'N/A';
    $qty = (int) ($purchase->quantity ?? 0);
    $unitPrice = (float) ($purchase->unit_price ?? 0);
    $total = (float) ($purchase->total_amount ?? ($qty * $unitPrice));
@endphp
<body>
    <div class="sheet">
        <div class="header-row">
            <div class="logo-box">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
            </div>
            <div class="header-text">
                <h1>INVOICE</h1>
                <p>Purchase Order Invoice</p>
            </div>
        </div>

        <div class="invoice-number">Invoice Number: {{ $invoiceNo }}</div>

        <div class="content-row">
            <div class="block">
                <div class="block-title">Bill From</div>
                <div class="block-line"><strong>Supplier</strong> {{ $distributorName }}</div>
                <div class="block-line"><strong>Date</strong> {{ $formattedDate }}</div>
                <div class="block-line"><strong>Branch</strong> {{ $branchName }}</div>
            </div>
            <div class="block">
                <div class="block-title">Bill To</div>
                <div class="block-line"><strong>{{ $companyName }}</strong></div>
                <div class="block-line">Dar es Salaam, Sinza Makaburini</div>
                <div class="block-line">info@opticedgeafrica.net</div>
                <div class="block-line">0677 - 609929</div>
                <div class="block-line">TIN: 202-148-522</div>
            </div>
        </div>

        <div class="items-wrap">
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="item-col">Item Description</th>
                        <th class="qty-col num">Quantity</th>
                        <th class="unit-col num">Unit Price (TZS)</th>
                        <th class="total-col num">Total (TZS)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $productName }}</td>
                        <td class="num">{{ number_format($qty) }}</td>
                        <td class="num">{{ number_format($unitPrice, 2) }}</td>
                        <td class="num">{{ number_format($total, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <div class="total-line"><span>Total Amount</span> <strong>: {{ number_format($total, 2) }}</strong></div>
        </div>

        <div class="thank-you">Thank you!</div>
    </div>
</body>
</html>
