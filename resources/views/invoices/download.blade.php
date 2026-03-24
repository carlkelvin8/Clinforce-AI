<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice #{{ $invoice->id }}</title>
<style>
  body { font-family: Arial, sans-serif; color: #1e293b; margin: 0; padding: 40px; background: #fff; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
  .brand { font-size: 24px; font-weight: bold; color: #2563eb; }
  .invoice-meta { text-align: right; font-size: 13px; color: #64748b; }
  .invoice-meta h2 { font-size: 20px; color: #1e293b; margin: 0 0 4px; }
  table { width: 100%; border-collapse: collapse; margin-top: 24px; }
  th { background: #f1f5f9; text-align: left; padding: 10px 14px; font-size: 12px; text-transform: uppercase; color: #64748b; }
  td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
  .total-row td { font-weight: bold; font-size: 16px; border-bottom: none; }
  .status { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
  .status-paid { background: #dcfce7; color: #16a34a; }
  .status-pending { background: #fef9c3; color: #ca8a04; }
  .footer { margin-top: 48px; font-size: 12px; color: #94a3b8; text-align: center; }
  @media print { body { padding: 20px; } }
</style>
</head>
<body>
<div class="header">
  <div>
    <div class="brand">Clinforce</div>
    <div style="font-size:13px;color:#64748b;margin-top:4px;">Healthcare Talent Platform</div>
  </div>
  <div class="invoice-meta">
    <h2>Invoice #{{ $invoice->id }}</h2>
    <div>Issued: {{ \Carbon\Carbon::parse($invoice->issued_at)->format('M d, Y') }}</div>
    <div>Status: <span class="status {{ $invoice->status === 'paid' ? 'status-paid' : 'status-pending' }}">{{ strtoupper($invoice->status) }}</span></div>
  </div>
</div>

<div style="display:flex;gap:40px;margin-bottom:32px;">
  <div>
    <div style="font-size:11px;text-transform:uppercase;color:#94a3b8;margin-bottom:4px;">Billed To</div>
    <div style="font-weight:600;">{{ $u->name ?? $u->email }}</div>
    <div style="font-size:13px;color:#64748b;">{{ $u->email }}</div>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th>Description</th>
      <th>Period</th>
      <th style="text-align:right;">Amount</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>{{ $plan?->name ?? 'Subscription' }}</td>
      <td>
        @if($invoice->subscription)
          {{ \Carbon\Carbon::parse($invoice->subscription->start_at)->format('M d, Y') }}
          &ndash;
          {{ \Carbon\Carbon::parse($invoice->subscription->end_at)->format('M d, Y') }}
        @else
          &mdash;
        @endif
      </td>
      <td style="text-align:right;">
        {{ $invoice->currency_code }}
        {{ number_format($invoice->amount_cents / 100, 2) }}
      </td>
    </tr>
    <tr class="total-row">
      <td colspan="2">Total</td>
      <td style="text-align:right;">
        {{ $invoice->currency_code }}
        {{ number_format($invoice->amount_cents / 100, 2) }}
      </td>
    </tr>
  </tbody>
</table>

<div class="footer">
  Thank you for using Clinforce. This is a computer-generated invoice.
</div>

<script>window.onload = function() { window.print(); }</script>
</body>
</html>
