<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Thermal Print {{ $invoice->invoice_no }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0 !important;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow-x: hidden !important;
            background: #fff !important;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #111827;
            font-size: 22px;
            line-height: 1.25;
            background: #fff;
            -webkit-text-size-adjust: 100%;
        }
        .no-print { display: block; }
        .thermal-receipt {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 12px !important;
            box-sizing: border-box;
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
        .section { padding: 6px 0; }
        .center { text-align: center; }
        .title {
            font-size: 0.6em;
            font-weight: 900;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .firm {
            font-size: 0.8em;
            font-weight: 900;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .muted { color: #6b7280; }
        .rule { border-top: 1px dashed #cbd5e1; margin: 6px 0; }
        .kv { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 8px; }
        .kv .k { color: #6b7280; font-weight: 700; font-size: 0.55em; }
        .kv .v { color: #111827; font-weight: 700; font-size: 0.6em; word-break: break-word; }
        .kv-wide { display: grid; grid-template-columns: 1fr; gap: 4px; }
        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .table th, .table td { padding: 3px 0; vertical-align: top; }
        .table thead th {
            font-size: 0.55em;
            color: #111827;
            font-weight: 900;
            border-bottom: 1px solid #111827;
            padding-bottom: 4px;
        }
        .pname { font-weight: 800; white-space: normal; word-break: break-word; overflow-wrap: anywhere; }
        .psub { font-size: 0.55em; color: #6b7280; font-weight: 700; margin-top: 1px; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
        .items { width: 100%; }
        .item { padding: 4px 0; }
        .item + .item { border-top: 1px dashed #cbd5e1; }
        .line1 { display: block; }
        .line2 { display: flex; justify-content: space-between; gap: 6px; margin-top: 1px; }
        .meta { flex: 1 1 auto; min-width: 0; font-size: 0.55em; color: #111827; }
        .amt { flex: 0 0 auto; text-align: right; font-weight: 900; }
        .badge-free { font-size: 0.55em; font-weight: 900; letter-spacing: 0.03em; text-transform: uppercase; }
        .free {
            margin-top: 4px;
            padding: 6px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
        }
        .free .label {
            font-size: 0.55em;
            font-weight: 900;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #0f172a;
        }
        .free .name { margin-top: 4px; font-weight: 900; word-break: break-word; }
        .free .meta { margin-top: 3px; font-size: 0.6em; font-weight: 800; color: #111827; }
        .totals { width: 100%; border-collapse: collapse; }
        .totals td { padding: 3px 0; }
        .totals .k { color: #6b7280; font-weight: 800; }
        .totals .v { text-align: right; font-weight: 900; }
        .grand { font-size: 0.75em; }
        .qr { display: flex; justify-content: center; margin-top: 8px; }
        .qr img { width: 38mm; max-width: 40mm; height: auto; object-fit: contain; border: 1px solid #e5e7eb; padding: 2px; background: #fff; }
        .footer { text-align: center; margin-top: 8px; font-weight: 900; }
        @media print {
            @page {
                size: 58mm auto;
                margin: 0 !important;
            }

            html,
            body {
                width: 58mm !important;
                min-width: 58mm !important;
                max-width: 58mm !important;

                margin: 0 !important;
                padding: 0 !important;

                overflow: hidden !important;
                background: #fff !important;
            }

            body * {
                visibility: hidden;
            }

            .thermal-receipt,
            .thermal-receipt * {
                visibility: visible;
            }

            .thermal-receipt {
                position: absolute;
                left: 0;
                top: 0;

                width: 58mm !important;
                max-width: 58mm !important;

                margin: 0 !important;
                padding: 2mm !important;

                box-sizing: border-box;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
            }

            td,
            th {
                word-wrap: break-word;
                overflow-wrap: break-word;
                font-size: inherit !important;
                padding: 1px 0;
            }
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

    <div class="thermal-receipt">
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
            <div class="items">
                @foreach($invoice->invoiceItems as $item)
                    @php
                        $isFree = (bool) ($item->is_free ?? false);
                        $qtyText = ((int) ($item->qty_ct ?? 0)) . '/' . ((int) ($item->qty_un ?? 0));
                        $unitRate = $isFree ? 0.0 : (float) ($item->base_price ?? 0);
                        $gstRate = 0.0;
                        if (!$isFree) {
                            $gstRate = (float) ($item->cgst_rate ?? 0) + (float) ($item->sgst_rate ?? 0) + (float) ($item->cess_rate ?? 0);
                        }
                        $lineTotal = $isFree ? 0.0 : (float) ($item->line_total ?? 0);
                    @endphp

                    <div class="item">
                        <div class="line1">
                            @if($isFree)
                                <div class="pname"><span class="badge-free">FREE:</span> {{ $item->product_description }}</div>
                            @else
                                <div class="pname">{{ $item->product_description }}</div>
                                <div class="psub">HSN: {{ $item->hsn_code ?: '-' }}</div>
                            @endif
                        </div>

                        <div class="line2">
                            <div class="meta mono">
                                {{ $qtyText }} x {{ number_format($unitRate, 2) }}  GST {{ number_format($gstRate, 1) }}%
                            </div>
                            <div class="amt mono">{{ number_format($lineTotal, 2) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
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
            <div class="items">
                @foreach($hsnMap as $hsn => $vals)
                    <div class="item">
                        <div class="line1">
                            <div class="pname">{{ $hsn }}</div>
                        </div>
                        <div class="line2">
                            <div class="meta mono">
                                CGST {{ number_format((float) ($vals['cgst'] ?? 0), 2) }}  SGST {{ number_format((float) ($vals['sgst'] ?? 0), 2) }}  IGST {{ number_format((float) ($vals['igst'] ?? 0), 2) }}
                            </div>
                            <div class="amt mono"></div>
                        </div>
                    </div>
                @endforeach
                <div class="item">
                    <div class="line1">
                        <div class="pname">TOTAL</div>
                    </div>
                    <div class="line2">
                        <div class="meta mono">
                            CGST {{ number_format($cgstTotal, 2) }}  SGST {{ number_format($sgstTotal, 2) }}  IGST {{ number_format($igstTotal, 2) }}
                        </div>
                        <div class="amt mono"></div>
                    </div>
                </div>
            </div>
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
