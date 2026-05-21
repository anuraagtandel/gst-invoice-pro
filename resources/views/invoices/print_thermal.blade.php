<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thermal Print {{ $invoice->invoice_no }}</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.35;
            background: #ffffff;
        }
        .no-print { display: block; }
        .wrap {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
        }
        .topbar {
            display: flex;
            gap: 8px;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }
        .btn {
            appearance: none;
            border: 1px solid #e5e7eb;
            background: #111827;
            color: #fff;
            font-weight: 700;
            font-size: 12px;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
        }
        .btn-secondary {
            background: #ffffff;
            color: #111827;
        }
        .section { padding: 10px 0; }
        .center { text-align: center; }
        .title {
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .firm {
            font-size: 14px;
            font-weight: 900;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .muted { color: #6b7280; }
        .rule { border-top: 1px dashed #cbd5e1; margin: 10px 0; }
        .kv { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 10px; }
        .kv .k { color: #6b7280; font-weight: 700; font-size: 10px; }
        .kv .v { color: #111827; font-weight: 700; font-size: 11px; word-break: break-word; }
        .kv-wide { display: grid; grid-template-columns: 1fr; gap: 6px; }
        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .table th, .table td { padding: 6px 0; vertical-align: top; }
        .table thead th {
            font-size: 10px;
            color: #111827;
            font-weight: 900;
            border-bottom: 1px solid #111827;
            padding-bottom: 6px;
        }
        .col-product { width: 40%; }
        .col-qty { width: 14%; text-align: right; }
        .col-rate { width: 16%; text-align: right; }
        .col-gst { width: 14%; text-align: right; }
        .col-amt { width: 16%; text-align: right; }
        .pname { font-weight: 800; white-space: normal; word-break: break-word; }
        .psub { font-size: 10px; color: #6b7280; font-weight: 700; margin-top: 2px; }
        .free {
            margin-top: 6px;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f8fafc;
        }
        .free .label {
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #0f172a;
        }
        .free .name { margin-top: 4px; font-weight: 900; word-break: break-word; }
        .free .meta { margin-top: 4px; font-size: 11px; font-weight: 800; color: #111827; }
        .totals { width: 100%; border-collapse: collapse; }
        .totals td { padding: 4px 0; }
        .totals .k { color: #6b7280; font-weight: 800; }
        .totals .v { text-align: right; font-weight: 900; }
        .grand { font-size: 13px; }
        .qr { display: flex; justify-content: center; margin-top: 12px; }
        .qr img { width: 120px; height: 120px; object-fit: contain; border: 1px solid #e5e7eb; padding: 4px; background: #fff; }
        .footer { text-align: center; margin-top: 10px; font-weight: 900; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .topbar { display: none !important; }
            .wrap { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print topbar">
        <button class="btn btn-secondary" type="button" onclick="history.back()">Back</button>
        <button class="btn" type="button" onclick="window.print()">Print</button>
    </div>

    @php
        $firmName = trim((string) ($settings['firm_name'] ?? ''));
        $firmGstin = trim((string) ($settings['firm_gstin'] ?? ''));
        $customer = $invoice->customer;
        $printedOn = now()->format('d-m-Y, g:i a');
        $invoiceDate = $invoice->invoice_date ? $invoice->invoice_date->format('d-m-Y') : '';
        $taxType = (string) ($invoice->tax_type ?? '');
        $cgstTotal = (float) ($invoice->total_cgst ?? 0);
        $sgstTotal = (float) ($invoice->total_sgst ?? 0);
        $igstTotal = 0.0;
        if ($taxType === 'IGST') {
            $igstTotal = $sgstTotal;
            $sgstTotal = 0.0;
            $cgstTotal = 0.0;
        }
        $taxAmountTotal = $cgstTotal + $sgstTotal + $igstTotal + (float) ($invoice->total_cess ?? 0);
        $taxableAmount = (float) ($invoice->taxable_amount ?? 0);
        $amountPayable = (float) ($invoice->grand_total ?? 0);
        $paymentMode = trim((string) ($invoice->payment_mode ?? '')) !== '' ? (string) $invoice->payment_mode : (string) ($invoice->payment_type ?? '');
        $hsnMap = [];
        foreach ($invoice->invoiceItems as $it) {
            if ((bool) ($it->is_free ?? false)) continue;
            $hsn = trim((string) ($it->hsn_code ?? ''));
            if ($hsn === '') $hsn = '-';
            if (!isset($hsnMap[$hsn])) {
                $hsnMap[$hsn] = ['cgst' => 0.0, 'sgst' => 0.0, 'igst' => 0.0];
            }
            $cg = (float) ($it->cgst ?? 0);
            $sg = (float) ($it->sgst ?? 0);
            if ($taxType === 'IGST') {
                $hsnMap[$hsn]['igst'] += $sg;
            } else {
                $hsnMap[$hsn]['cgst'] += $cg;
                $hsnMap[$hsn]['sgst'] += $sg;
            }
        }
        $qrSettingPath = trim((string) ($settings['invoice_qr_path'] ?? ''));
        $qrFromSettings = $qrSettingPath !== '' ? storage_path('app/public/' . $qrSettingPath) : null;
        $qrPublicFile = public_path('images/payment_qr.jpg');
        $qrSrc = null;
        $qrPublicUrl = asset('images/payment_qr.jpg');
        $qrFromSettingsUrl = ($qrFromSettings && file_exists($qrFromSettings))
            ? asset('storage/' . ltrim($qrSettingPath, '/'))
            : null;
        $qrSrc = $qrFromSettingsUrl ?: $qrPublicUrl;
        if ($qrFromSettings && file_exists($qrFromSettings)) {
            $qrSrc = $qrFromSettingsUrl ?: $qrSrc;
        } elseif (file_exists($qrPublicFile)) {
            $qrSrc = $qrPublicUrl;
        }
    @endphp

    <div class="wrap">
        <div class="section center">
            <div class="firm">{{ $firmName !== '' ? $firmName : 'COMPANY' }}</div>
            @if($firmGstin !== '')
                <div class="muted" style="margin-top: 4px; font-weight: 800;">GSTIN: {{ strtoupper($firmGstin) }}</div>
            @endif
            <div class="title">Delivery Challan Cum Invoice</div>
        </div>

        <div class="rule"></div>

        <div class="section">
            <div class="kv">
                <div>
                    <div class="k">Invoice No</div>
                    <div class="v">{{ $invoice->invoice_no }}</div>
                </div>
                <div>
                    <div class="k">Invoice Date</div>
                    <div class="v">{{ $invoiceDate }}</div>
                </div>
                <div>
                    <div class="k">Printed On</div>
                    <div class="v">{{ $printedOn }}</div>
                </div>
                <div>
                    <div class="k">Salesman</div>
                    <div class="v">{{ $invoice->salesman ?? '-' }}</div>
                </div>
            </div>

            <div class="rule"></div>

            <div class="kv-wide">
                <div>
                    <div class="k">Customer</div>
                    <div class="v">{{ $customer?->name ?? '-' }}</div>
                </div>
                <div>
                    <div class="k">Mobile</div>
                    <div class="v">{{ $customer?->mobile ?? '-' }}</div>
                </div>
                <div>
                    <div class="k">GSTIN</div>
                    <div class="v">{{ $customer?->gstin ? strtoupper($customer->gstin) : '-' }}</div>
                </div>
                <div>
                    <div class="k">State</div>
                    <div class="v">{{ $customer?->state ?? '-' }}</div>
                </div>
                <div>
                    <div class="k">Payment Mode</div>
                    <div class="v">{{ $paymentMode !== '' ? $paymentMode : '-' }}</div>
                </div>
            </div>
        </div>

        <div class="rule"></div>

        <div class="section">
            <table class="table">
                <thead>
                    <tr>
                        <th class="col-product">PRODUCT</th>
                        <th class="col-qty">QTY</th>
                        <th class="col-rate">RATE</th>
                        <th class="col-gst">GST</th>
                        <th class="col-amt">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    @php $lastWasPaid = false; @endphp
                    @foreach($invoice->invoiceItems as $item)
                        @php
                            $isFree = (bool) ($item->is_free ?? false);
                            $qtyText = ((int) ($item->qty_ct ?? 0)) . '/' . ((int) ($item->qty_un ?? 0));
                            $unitRate = $isFree ? 0.0 : (float) ($item->base_price ?? 0);
                            $gstAmt = 0.0;
                            if (!$isFree) {
                                $gstAmt = (float) ($item->cgst ?? 0) + (float) ($item->sgst ?? 0) + (float) ($item->cess ?? 0);
                            }
                            $gstRate = 0.0;
                            if (!$isFree) {
                                $gstRate = (float) ($item->cgst_rate ?? 0) + (float) ($item->sgst_rate ?? 0) + (float) ($item->cess_rate ?? 0);
                            }
                            $lineTotal = $isFree ? 0.0 : (float) ($item->line_total ?? 0);
                            $lastWasPaid = $lastWasPaid || (!$isFree);
                        @endphp
                        @if(!$isFree)
                            <tr>
                                <td class="col-product">
                                    <div class="pname">{{ $item->product_description }}</div>
                                    <div class="psub">HSN: {{ $item->hsn_code ?: '-' }}</div>
                                </td>
                                <td class="col-qty">{{ $qtyText }}</td>
                                <td class="col-rate">{{ number_format($unitRate, 2) }}</td>
                                <td class="col-gst">
                                    <div>{{ number_format($gstAmt, 2) }}</div>
                                    <div class="psub">{{ number_format($gstRate, 1) }}%</div>
                                </td>
                                <td class="col-amt">{{ number_format($lineTotal, 2) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td class="col-product">
                                    <div class="pname" style="font-weight: 900;">FREE: {{ $item->product_description }}</div>
                                </td>
                                <td class="col-qty">{{ $qtyText }}</td>
                                <td class="col-rate">0.00</td>
                                <td class="col-gst">
                                    <div>0.00</div>
                                    <div class="psub">0.0%</div>
                                </td>
                                <td class="col-amt">0.00</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="rule"></div>

        <div class="section">
            <table class="totals">
                <tr>
                    <td class="k">Taxable Amount</td>
                    <td class="v">{{ number_format($taxableAmount, 2) }}</td>
                </tr>
                <tr>
                    <td class="k">Tax Amount</td>
                    <td class="v">{{ number_format($taxAmountTotal, 2) }}</td>
                </tr>
                <tr>
                    <td class="k grand">Amount Payable</td>
                    <td class="v grand">{{ number_format($amountPayable, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="rule"></div>

        <div class="section">
            <div style="font-weight: 900; margin-bottom: 6px;">GST SUMMARY</div>
            <table class="table">
                <thead>
                    <tr>
                        <th class="col-product">HSN</th>
                        <th class="col-rate">CGST</th>
                        <th class="col-rate">SGST</th>
                        <th class="col-rate">IGST</th>
                        <th class="col-amt"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hsnMap as $hsn => $vals)
                        <tr>
                            <td class="col-product"><div class="pname">{{ $hsn }}</div></td>
                            <td class="col-rate">{{ number_format((float) ($vals['cgst'] ?? 0), 2) }}</td>
                            <td class="col-rate">{{ number_format((float) ($vals['sgst'] ?? 0), 2) }}</td>
                            <td class="col-rate">{{ number_format((float) ($vals['igst'] ?? 0), 2) }}</td>
                            <td class="col-amt"></td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="col-product"><div class="pname">TOTAL</div></td>
                        <td class="col-rate">{{ number_format($cgstTotal, 2) }}</td>
                        <td class="col-rate">{{ number_format($sgstTotal, 2) }}</td>
                        <td class="col-rate">{{ number_format($igstTotal, 2) }}</td>
                        <td class="col-amt"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="qr">
                @if($qrSrc)
                    <img src="{{ $qrSrc }}" alt="QR">
                @endif
            </div>
        </div>

        <div class="footer section">Thank You Visit Again</div>
    </div>
</body>
</html>
