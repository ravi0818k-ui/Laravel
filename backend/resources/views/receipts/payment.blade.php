<?php
    $tenant = $payment->tenant;
    $user = $tenant?->user;
    $pg = $tenant?->pgLocation;
    $room = $tenant?->currentRoom;
    $bed = $tenant?->currentBed;
    $rent = $payment->monthlyRent;

    if ($rent) {
        $description = 'Rent for ' . \Carbon\Carbon::parse($rent->billing_month)->format('F Y');
    } else {
        $description = match ($payment->transaction_reference) {
            'first_rent' => 'First Month Rent',
            'first_security' => 'Security Deposit',
            'first_both' => 'First Month Rent + Security Deposit',
            default => 'Payment',
        };
    }
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 0; }
    .container { padding: 30px; }
    .header { border-bottom: 2px solid #d97706; padding-bottom: 12px; margin-bottom: 20px; }
    .pg-name { font-size: 20px; font-weight: bold; color: #d97706; margin: 0; }
    .pg-details { font-size: 10px; color: #555; margin-top: 4px; }
    .title-row { display: table; width: 100%; margin-bottom: 20px; }
    .title-left, .title-right { display: table-cell; vertical-align: top; }
    .title-right { text-align: right; }
    h2 { margin: 0 0 4px 0; font-size: 16px; }
    .meta { font-size: 10px; color: #555; }
    .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #d97706; margin-bottom: 6px; letter-spacing: 0.5px; }
    .box { border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 16px; }
    table.details { width: 100%; border-collapse: collapse; }
    table.details td { padding: 6px 0; border-bottom: 1px solid #eee; font-size: 11px; }
    table.details td.label { color: #666; width: 60%; }
    table.details td.value { text-align: right; font-weight: bold; }
    table.details tr:last-child td { border-bottom: none; }
    .total-row td { font-size: 13px; padding-top: 10px; }
    .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <p class="pg-name">{{ $pg->name ?? 'PG A1' }}</p>
        <p class="pg-details">
            {{ $pg->address ?? '' }}{{ $pg->city ? ', ' . $pg->city : '' }}{{ $pg->state ? ', ' . $pg->state : '' }} {{ $pg->pincode ?? '' }}<br>
            @if($pg?->contact_mobile) Contact: {{ $pg->contact_mobile }} @endif
            @if($pg?->contact_email) &nbsp;|&nbsp; {{ $pg->contact_email }} @endif
        </p>
    </div>

    <div class="title-row">
        <div class="title-left">
            <h2>Payment Receipt</h2>
            <p class="meta">Receipt No: {{ $payment->receipt_number }}</p>
        </div>
        <div class="title-right">
            <p class="meta">Issued: {{ optional($payment->verified_at)->format('d M Y, h:i A') }}</p>
            <p class="meta">Payment Date: {{ optional($payment->payment_date)->format('d M Y') }}</p>
        </div>
    </div>

    <div class="box">
        <p class="section-title">Billed To</p>
        <table class="details">
            <tr><td class="label">Tenant Name</td><td class="value">{{ $user->name ?? '—' }}</td></tr>
            <tr><td class="label">Tenant ID</td><td class="value">{{ $tenant->tenant_id ?? '—' }}</td></tr>
            <tr><td class="label">Mobile</td><td class="value">{{ $user->mobile ?? '—' }}</td></tr>
            <tr><td class="label">Room / Bed</td><td class="value">{{ $room?->room_number ?? '—' }} / {{ $bed?->bed_number ?? '—' }}</td></tr>
        </table>
    </div>

    <div class="box">
        <p class="section-title">Payment Details</p>
        <table class="details">
            <tr><td class="label">Description</td><td class="value">{{ $description }}</td></tr>
            @if($rent)
            <tr><td class="label">Billing Month</td><td class="value">{{ \Carbon\Carbon::parse($rent->billing_month)->format('F Y') }}</td></tr>
            <tr><td class="label">Base Rent</td><td class="value">₹{{ number_format($rent->base_rent, 2) }}</td></tr>
            @if($rent->additional_charge > 0)
            <tr><td class="label">Additional Charges</td><td class="value">₹{{ number_format($rent->additional_charge, 2) }}</td></tr>
            @endif
            <tr><td class="label">Total Due (Month)</td><td class="value">₹{{ number_format($rent->total_amount, 2) }}</td></tr>
            @endif
            <tr><td class="label">Payment Method</td><td class="value">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td></tr>
            @if($payment->transaction_reference)
            <tr><td class="label">Transaction Reference</td><td class="value">{{ $payment->transaction_reference }}</td></tr>
            @endif
            <tr><td class="label">Claimed Amount</td><td class="value">₹{{ number_format($payment->claimed_amount, 2) }}</td></tr>
            <tr class="total-row"><td class="label">Amount Verified &amp; Received</td><td class="value">₹{{ number_format($payment->verified_amount, 2) }}</td></tr>
            @if($rent)
            <tr><td class="label">Remaining Due (Month)</td><td class="value">₹{{ number_format($rent->due_amount, 2) }}</td></tr>
            @endif
        </table>
    </div>

    <div class="box">
        <p class="section-title">Verification</p>
        <table class="details">
            <tr><td class="label">Verified By</td><td class="value">{{ $payment->verifiedByUser->name ?? '—' }}</td></tr>
            <tr><td class="label">Verified At</td><td class="value">{{ optional($payment->verified_at)->format('d M Y, h:i A') }}</td></tr>
        </table>
    </div>

    <div class="footer">
        This is a system-generated receipt and does not require a signature.
    </div>
</div>
</body>
</html>
