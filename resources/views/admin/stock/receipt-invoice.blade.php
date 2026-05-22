<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'RECEIPT' }} {{ $invoiceNo ?? '' }}</title>
    <style>
        @page { size: A4; margin: 8mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
        }
        .paper {
            max-width: 760px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 18px;
            box-shadow: none;
        }
        .center { text-align: center; }
        .brand { font-size: 38px; font-weight: 800; color: #1d4f9d; margin: 0; }
        .meta { color: #6b7280; font-size: 14px; margin-top: 3px; }
        .title { margin: 10px 0 5px; font-size: 44px; line-height: 1; color: #1d4f9d; font-weight: 800; }
        .order { font-size: 21px; color: #4b5563; }
        .sep { margin: 10px auto; width: 48%; border-top: 1px dashed #d1d5db; }
        .section-title { font-size: 24px; color: #1d4f9d; margin: 14px 0 8px; font-weight: 800; text-transform: uppercase; }
        .box {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            background: #f9fafb;
            page-break-inside: avoid;
        }
        .box p { margin: 3px 0; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; page-break-inside: avoid; }
        th, td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 15px; vertical-align: top; }
        th { text-align: left; font-weight: 700; background: #f3f4f6; }
        .right { text-align: right; }
        .mono { font-family: "Courier New", monospace; font-weight: 700; color: #1d4f9d; letter-spacing: 0.5px; font-size: 13px; }
        .summary {
            margin-top: 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #f9fafb;
            padding: 10px;
            page-break-inside: avoid;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 16px;
            line-height: 1.4;
        }
        .summary-row.total { font-weight: 800; color: #1d4f9d; border-top: 1px dashed #d1d5db; margin-top: 7px; padding-top: 7px; }
        .status {
            margin-top: 10px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            border-radius: 8px;
            padding: 10px;
            page-break-inside: avoid;
        }
        .status .label { text-align: center; font-size: 20px; font-weight: 800; margin-bottom: 6px; text-transform: uppercase; }
        .status-badge {
            display: inline-block;
            margin: 0 auto 8px;
            padding: 4px 18px;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .status-badge--paid   { background: #dcfce7; color: #166534; border: 1px solid #a7d6af; }
        .status-badge--partial { background: #fff7ed; color: #9a3412; border: 1px solid #fdba74; }
        .status-badge--pending { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
        .foot {
            margin-top: 10px;
            display: inline-block;
            padding: 10px 12px;
            background: #eaf2fb;
            border-radius: 8px;
            color: #1d4f9d;
            page-break-inside: avoid;
        }
        .foot .big { font-size: 22px; font-weight: 800; margin: 0 0 3px; }
        .foot .small { margin: 0; color: #6b7280; font-size: 12px; }
    </style>
</head>
@php
    $isCredit = isset($credit);
    $row = $isCredit ? $credit : $sale;
    $customer = $isCredit
        ? ($credit->customer_name ?? 'N/A')
        : ($sale->customer_name ?? 'N/A');
    $phone = $isCredit
        ? ($credit->customer_phone ?? null)
        : null;
    $productName = $isCredit
        ? ($credit->product ? (($credit->product->category?->name ?? '—') . ' – ' . $credit->product->name) : 'N/A')
        : ($sale->product ? (($sale->product->category?->name ?? '—') . ' – ' . $sale->product->name) : 'N/A');
    $qty = (int) ($row->quantity_sold ?? 1);
    $amount = $isCredit
        ? (float) ($credit->total_amount ?? 0)
        : (float) ($sale->total_selling_value ?? 0);
    $paid = $isCredit
        ? (float) ($credit->paid_amount ?? 0)
        : max(0, $amount - (float) ($sale->balance ?? 0));
    $remaining = max(0, $amount - $paid);
    $serial = $isCredit
        ? ($credit->productListItem?->imei_number ?? null)
        : ($sale->productListItem?->imei_number ?? null);
    $displayDue = $isCredit ? 0 : $remaining;
    $displayRemaining = $isCredit ? 0 : $remaining;

    // Determine payment status label
    if ($isCredit) {
        // Credit receipts should always print as PAID in the status badge.
        $paymentStatus = 'paid';
    } else {
        if ($remaining <= 0.0001) {
            $paymentStatus = 'paid';
        } elseif ($paid > 0.0001) {
            $paymentStatus = 'partial';
        } else {
            $paymentStatus = 'pending';
        }
    }
    $statusLabel = match($paymentStatus) {
        'paid'    => 'PAID',
        'partial' => 'PARTIAL PAYMENT',
        default   => 'PENDING',
    };
    $statusClass = match($paymentStatus) {
        'paid'    => 'status-badge--paid',
        'partial' => 'status-badge--partial',
        default   => 'status-badge--pending',
    };
    $statusLabelColor = match($paymentStatus) {
        'paid'    => '#246a35',
        'partial' => '#9a3412',
        default   => '#854d0e',
    };
@endphp
<body>
<div class="paper">
    <div class="center">
        <h1 class="brand">OPTIC EDGE AFRICA</h1>
        <div class="meta">TIN: 202-148-522</div>
        <div class="title">{{ $title ?? 'RECEIPT' }}</div>
        <div class="order">Order #{{ $invoiceNo ?? '—' }}</div>
        <div class="sep"></div>
    </div>

    <div class="section-title">Customer details</div>
    <div class="box">
        <p><strong>Name:</strong> {{ $customer }}</p>
        @if(!empty($phone))
            <p><strong>Phone:</strong> {{ $phone }}</p>
        @endif
    </div>

    <div class="section-title">Product details</div>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th style="width:80px;">Qty</th>
                <th class="right" style="width:180px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $productName }}</td>
                <td>{{ $qty }}</td>
                <td class="right">{{ number_format($amount, 2) }} TZS</td>
            </tr>
            @if(!empty($serial))
            <tr>
                <td colspan="3" class="mono">Imei number: {{ $serial }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row">
            <span>Product Amount:</span>
            <span>{{ number_format($amount, 2) }} TZS</span>
        </div>
        <div class="summary-row">
            <span>+VAT:</span>
            <span>0 TZS</span>
        </div>
        <div class="summary-row">
            <span>Sub Total:</span>
            <span>{{ number_format($amount, 2) }} TZS</span>
        </div>
        <div class="summary-row total">
            <span>AMOUNT DUE:</span>
            <span>{{ number_format($displayDue, 2) }} TZS</span>
        </div>
    </div>

    <div class="status">
        <div class="label" style="color: {{ $statusLabelColor }};">Payment Status</div>
        <div style="text-align:center; margin-bottom: 8px;">
            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
        </div>
        <div class="summary-row">
            <span>Paid:</span>
            <span>{{ number_format($paid, 2) }} TZS</span>
        </div>
        <div class="summary-row">
            <span>Remaining:</span>
            <span>{{ number_format($displayRemaining, 2) }} TZS</span>
        </div>
    </div>

    <div class="foot">
        <p class="big">Thank you for your business!</p>
        <p class="small">Generated on {{ ($invoiceDate ?? now())->format('Y-m-d') }}</p>
    </div>
</div>
</body>
</html>
