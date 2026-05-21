<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_no }}</title>
    <style>
        :root {
            --print-page-margin: 12mm;
            --print-wrapper-padding: {{ request()->has('pdf') ? '8mm 14mm 8mm 8mm' : '0mm' }};
            --print-footer-margin-top: {{ request()->has('pdf') ? '20px' : '8px' }};
            --print-signature-margin-top: {{ request()->has('pdf') ? '30px' : '12px' }};
        }

        @page { size: A4 landscape; margin: var(--print-page-margin); }

        /* Base Reset & Print Safe Fonts */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.4;
            background-color: #f3f4f6;
        }

        .inr {
            font-family: 'DejaVu Sans', sans-serif;
        }
        
        /* Container for screen viewing */
        .page-wrapper {
            max-width: 1100px; /* Wider for landscape */
            margin: 20px auto;
            background: #fff;
            padding: 30px 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: relative;
        }


        /* Print controls */
        .no-print-controls {
            max-width: 1100px;
            margin: 20px auto 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            gap: 12px;
            flex-wrap: nowrap;
        }
        .no-print-controls > div {
            display: flex;
            gap: 10px;
            flex-wrap: nowrap;
            align-items: center;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            white-space: nowrap;
        }
        .btn-primary { background: #4f46e5; color: white; }
        .btn-secondary { background: #e5e7eb; color: #374151; }

        /* Typography & Utilities */
        .bold { font-weight: 700; }
        .uppercase { text-transform: uppercase; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-blue { color: #1e3a8a; } /* Dark Blue for Headers */
        .bg-blue { background-color: #1e3a8a; color: white; }
        .text-gray { color: #6b7280; }
        .text-sm { font-size: 10px; }
        .text-xs { font-size: 9px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .mt-4 { margin-top: 16px; }

        /* Header Section */
        .header-container {
            display: table;
            width: 100%;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 8px;
            table-layout: fixed;
        }
        .header-container > div {
            display: table-cell;
            vertical-align: top;
            padding-right: 8px;
        }
        .header-container > div:last-child {
            padding-right: 0;
        }
        .header-container > .company-info { width: 29%; }
        .header-container > .party-box:nth-of-type(2) { width: 29%; }
        .header-container > .party-box:nth-of-type(3) { width: 29%; }
        .header-container > .invoice-details { width: 13%; }
        .company-info {
            min-width: 0;
            height: auto;
        }
        .company-info > .header-box {
            height: auto;
        }
        .invoice-details {
            text-align: center !important;
            min-width: 0;
        }
        .header-box.invoice-details,
        .header-box.invoice-details * {
            text-align: center !important;
        }
        .invoice-title {
            font-size: 14px;
            color: #1e3a8a;
            font-weight: 800;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .party-box { min-width: 0; }

        .header-box {
            border: 1px solid #d1d5db;
            border-radius: 3px;
            background-color: #f8fafc;
            padding: 6px 8px;
            height: auto;
            overflow: visible;
        }

        .header-row {
            display: table;
            width: 100%;
            height: auto;
        }
        .header-logo-cell {
            display: none;
        }
        .header-logo {
            display: block;
        }
        .header-logo-img {
            width: 56px;
            height: 56px;
            object-fit: contain;
            display: block;
            background: #fff;
        }
        .header-text-cell {
            display: table-cell;
            vertical-align: middle;
        }

        @supports (display: grid) {
            .header-container {
                display: grid;
                gap: 8px;
                grid-template-columns: 29fr 29fr 29fr 13fr;
                align-items: stretch;
            }
            .header-container > div {
                display: block;
                padding-right: 0;
            }
            .header-container > .company-info,
            .header-container > .party-box:nth-of-type(2),
            .header-container > .party-box:nth-of-type(3),
            .header-container > .invoice-details {
                width: auto;
            }
        }

        .qr-box {
            display: inline-block;
            margin-top: 0;
            text-align: right;
        }
        .qr-label {
            font-size: 9px;
            color: #6b7280;
            margin-top: 4px;
        }

        .bank-table {
            width: 100%;
            margin-top: 6px;
            border-collapse: collapse;
        }
        .bank-table td {
            border: none;
            padding: 1px 0;
            font-size: 10px;
            color: #000;
        }
        .bank-table td:first-child {
            width: 105px;
            color: #374151;
            font-weight: bold;
        }

        /* Parties Section (Bill To / Ship To) */
        .parties-container {
            width: 100%;
            border: 1px solid #d1d5db;
            margin-bottom: 8px;
            border-radius: 4px;
            border-collapse: collapse;
        }
        .parties-container td {
            width: 50%;
            padding: 8px 12px;
            vertical-align: top;
        }
        .party-title {
            color: #6b7280;
            font-weight: bold;
            margin-bottom: 2px;
            font-size: 9px;
            text-transform: uppercase;
        }
        .party-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .billto-wrap {
            display: block;
            width: 100%;
        }
        .billto-title {
            color: #000;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.2px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .billto-name {
            color: #000;
            font-weight: 700;
            font-size: 10px;
            margin-bottom: 2px;
        }
        .billto-desc,
        .billto-meta {
            color: #000;
            font-weight: 700;
            font-size: 8.8px;
            line-height: 1.2;
            word-break: break-word;
            white-space: normal;
        }
        .billto-meta {
            margin-top: 2px;
        }
        .billto-meta div {
            line-height: 1.15;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            page-break-inside: auto;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 4px 6px;
            vertical-align: middle;
        }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        .header-container,
        .parties-container,
        .bottom-section,
        .footer { page-break-inside: avoid; }
        
        /* Items Table Specific */
        .items-table th {
            background-color: #1e3a8a;
            color: white;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #1e3a8a;
            white-space: nowrap;
        }
        .items-table td {
            font-size: 10px;
            border: 1px solid #e5e7eb;
            color: #000;
        }
        .items-table tr.totals-row td {
            background-color: #f8fafc;
            font-weight: bold;
            border-top: 2px solid #1e3a8a;
        }

        /* HSN & Summary Section */
        .hsn-title {
            color: #000;
            font-weight: 800;
            font-size: 11px;
            margin-bottom: 4px;
        }
        .hsn-table th {
            background-color: #e5e7eb;
            color: #111827;
            font-weight: 800;
            font-size: 9px;
            padding: 3px 6px;
            border: 1px solid #e5e7eb;
        }
        .hsn-table td {
            font-size: 10px;
            padding: 3px 6px;
            border: 1px solid #e5e7eb;
            color: #000;
        }

        /* Bottom Section (Declaration & Totals) */
        .bottom-section {
            width: 100%;
            margin-top: 8px;
            display: table;
            table-layout: fixed;
        }
        .declaration-box {
            display: table-cell;
            width: 65%;
            font-size: 10px;
            color: #000;
            padding-right: 20px;
            vertical-align: top;
        }
        .totals-box {
            display: table-cell;
            width: 35%;
            background: #f8fafc;
            border-radius: 4px;
            padding: 8px;
            vertical-align: top;
        }
        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
            font-size: 11px;
        }
        .total-row > div {
            display: table-cell;
        }
        .total-row > div.bold {
            text-align: right;
            font-weight: bold;
        }
        .amount-payable {
            display: table;
            width: 100%;
            background-color: #1e3a8a;
            color: white;
            padding: 8px 10px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 4px;
            margin-top: 8px;
        }
        .amount-payable > div {
            display: table-cell;
        }
        .amount-payable > div:last-child {
            text-align: right;
        }

        /* Amount in Words */
        .amount-words {
            border: 1px solid #bfdbfe;
            background-color: #eff6ff;
            color: #1e3a8a;
            padding: 6px 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 10px;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            display: table;
            width: 100%;
        }
        .footer-left {
            display: table-cell;
            font-size: 10px;
            color: #6b7280;
            vertical-align: bottom;
            width: 50%;
        }
        .signature-box {
            display: table-cell;
            text-align: right;
            vertical-align: bottom;
            width: 50%;
        }
        .signature-block {
            display: inline-block;
            width: 240px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 240px;
            margin-top: 30px;
            padding-top: 5px;
            display: inline-block;
            text-align: center;
        }

        @media print {
            @page { size: A4 landscape; margin: var(--print-page-margin); }
            body { 
                background: #fff; 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; 
                margin: 0;
            }
            .no-print-controls { display: none !important; }
            .page-wrapper { margin: 0; padding: var(--print-wrapper-padding); box-shadow: none; max-width: none; }
            .footer { margin-top: var(--print-footer-margin-top); }
            .signature-line { margin-top: var(--print-signature-margin-top); }
        }

        @media (max-width: 640px) {
            .no-print-controls {
                flex-wrap: wrap;
                padding: 12px 14px;
            }
            .no-print-controls > div {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>

    @if(!request()->has('pdf'))
    <div class="no-print-controls">
        <a href="{{ route('app.invoices.index') }}" class="btn btn-secondary">← Back to Invoices</a>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
        </div>
    </div>
    @endif

    <div class="page-wrapper">
        
        <!-- Header -->
        <div class="header-container">
            <div class="company-info">
                <div class="header-box">
                    <div class="header-row">
                        <div class="header-logo-cell">
                            @php
                                $headerLogoFile = public_path('images/header-logo.png');
                                $headerLogoSrc = null;
                                if (file_exists($headerLogoFile)) {
                                    $headerLogoSrc = request()->has('pdf')
                                        ? $headerLogoFile
                                        : asset('images/header-logo.png');
                                }
                            @endphp
                            @if($headerLogoSrc)
                                <img class="header-logo-img" src="{{ $headerLogoSrc }}" alt="Logo">
                            @else
                                <svg class="header-logo" width="56" height="56" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <clipPath id="pepsiClip">
                                            <circle cx="60" cy="60" r="52"></circle>
                                        </clipPath>
                                    </defs>
                                    <circle cx="60" cy="60" r="54" fill="#ffffff" stroke="#000000" stroke-width="6"></circle>
                                    <circle cx="60" cy="60" r="51" fill="#ffffff" stroke="#ffffff" stroke-width="3"></circle>
                                    <g clip-path="url(#pepsiClip)">
                                        <rect x="0" y="0" width="120" height="62" fill="#e11d48"></rect>
                                        <rect x="0" y="62" width="120" height="58" fill="#1d4ed8"></rect>
                                        <path d="M0 60 C 26 42, 58 38, 120 46 L120 76 C 82 78, 54 82, 0 92 Z" fill="#ffffff"></path>
                                    </g>
                                    <text x="60" y="74" text-anchor="middle" font-family="Arial Black, Arial, sans-serif" font-size="34" font-weight="900" fill="#000000" letter-spacing="1">PEPSI</text>
                                </svg>
                            @endif
                        </div>
                        <div class="header-text-cell company-details">
                            @php
                                $firmName = trim((string) ($settings['firm_name'] ?? 'SAGARDUTT PEPSI DISTRIBUTORS'));
                                $firmAddress = trim((string) ($settings['firm_address'] ?? ''));
                                $firmGstin = trim((string) ($settings['firm_gstin'] ?? ''));
                                $firmPhone = trim((string) ($settings['firm_phone'] ?? ''));
                                $firmAddressOneLine = trim(preg_replace('/\s+/', ' ', $firmAddress));
                                $addressLine = $firmAddressOneLine;
                                $gstinLine = $firmGstin !== '' ? ('GSTIN: ' . strtoupper($firmGstin)) : '';
                            @endphp
                            <div style="color:#000; font-weight:800; font-size: 13px; line-height: 1.1; text-transform: uppercase;">{{ strtoupper($firmName) }}</div>
                            <div style="color:#000; font-weight:700; font-size: 9.5px; line-height: 1.2; margin-top: 3px;">{{ $addressLine }}</div>
                            @if($gstinLine !== '')
                                <div style="color:#000; font-weight:700; font-size: 9.5px; line-height: 1.2; margin-top: 2px;">{{ $gstinLine }}</div>
                            @endif
                            @if($firmPhone !== '')
                                <div style="color:#000; font-weight:700; font-size: 9.5px; line-height: 1.2; margin-top: 2px;">MOBILE NO: {{ $firmPhone }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @php
                $billCustomer = $invoice->customer;
                $billAddress = trim(($billCustomer->address ?? '') . ' ' . ($billCustomer->city ?? '') . ' ' . ($billCustomer->state ?? ''));
                $billAddress = ucwords(strtolower($billAddress));
                $billCode = $billCustomer->code ?? '';
                $billName = $billCustomer->name ?? '';
            @endphp

            <div class="header-box party-box">
                <div class="billto-title">INVOICE TO</div>
                <div class="billto-name">{{ $billCode ? $billCode . ' - ' : '' }}{{ $billName ?: '-' }}</div>
                <div class="billto-desc">{{ $billAddress }}</div>
                <div class="billto-meta">
                    <div>GSTIN: {{ $billCustomer->gstin ? strtoupper($billCustomer->gstin) : '-' }} | State Code: {{ $billCustomer->pos_code ?? '' }}</div>
                    <div>Mobile: {{ $billCustomer->mobile ?? '' }}</div>
                </div>
            </div>

            <div class="header-box party-box">
                <div class="billto-title">SHIP TO</div>
                <div class="billto-name">{{ $billCode ? $billCode . ' - ' : '' }}{{ $billName ?: '-' }}</div>
                <div class="billto-desc">{{ $billAddress }}</div>
                <div class="billto-meta">
                    <div>GSTIN: {{ $billCustomer->gstin ? strtoupper($billCustomer->gstin) : '-' }} | State Code: {{ $billCustomer->pos_code ?? '' }}</div>
                    <div>Mobile: {{ $billCustomer->mobile ?? '' }}</div>
                </div>
            </div>
            
            <div class="header-box invoice-details">
                <div class="invoice-title">TAX INVOICE</div>
                <p class="mb-1" style="color:#000; font-weight:700; font-size: 9.5px;">Invoice No: <span style="color:#000; font-weight:700;">{{ $invoice->invoice_no }}</span></p>
                <p class="mb-2" style="color:#000; font-weight:700; font-size: 9.5px;">Date: <span style="color:#000; font-weight:700;">{{ $invoice->invoice_date->format('d-m-Y') }}</span></p>
                <p style="color:#000; font-weight:700; font-size: 9.5px;">Salesman: <span style="color:#000; font-weight:700;">{{ $invoice->salesman ?? 'N/A' }}</span></p>
            </div>
        </div>

        @php
            $isIgst = $invoice->tax_type === 'IGST';
            $sgstLabel = $isIgst
                ? 'IGST'
                : ($invoice->tax_type === 'CGST_UTGST' ? 'UTGST' : 'SGST');
            
            // Calculate column totals
            $totQtyCt = 0;
            $totQtyUn = 0;
            $totTradePrice = 0;
            $totBasePrice = 0;
            $totDisc = 0;
            $totTaxable = 0;
            $totCgst = 0;
            $totSgst = 0;
            $totAmount = 0;
        @endphp

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th width="3%" class="text-center">SN</th>
                    <th width="25%">ITEM NAME</th>
                    <th width="8%" class="text-center">HSN</th>
                    <th width="7%" class="text-right">MRP</th>
                    <th width="8%" class="text-right">RATE</th>
                    <th width="5%" class="text-center">PK*</th>
                    <th width="8%" class="text-center">QTY CT/UN</th>
                    <th width="10%" class="text-right">BASE PRICE</th>
                    <th width="10%" class="text-right">TAXABLE</th>
                    @if(!$isIgst)
                        <th width="8%" class="text-right">CGST</th>
                    @endif
                    <th width="8%" class="text-right">{{ $sgstLabel }}</th>
                    <th width="12%" class="text-right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->invoiceItems as $item)
                @php
                    $rate = $item->base_price * (1 + (($item->cgst_rate + $item->sgst_rate + $item->cess_rate) / 100));
                    $tradeFromProduct = $item->product?->trade_price;
                    $displayRate = $item->is_free
                        ? (is_numeric($tradeFromProduct) ? (float) $tradeFromProduct : (float) $rate)
                        : (float) $rate;
                    $basePrice = $item->is_free ? 0 : ($item->base_price * $item->total_units);

                    $totQtyCt += (float) $item->qty_ct;
                    $totQtyUn += (float) $item->qty_un;

                    if(!$item->is_free) {
                        $totBasePrice += $basePrice;
                        $totTaxable += $item->taxable;
                        $totCgst += $item->cgst;
                        $totSgst += $item->sgst;
                        $totAmount += $item->line_total;
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $item->product_description }}</td>
                    <td class="text-center">{{ $item->hsn_code }}</td>
                    <td class="text-right">{{ number_format($item->mrp, 2) }}</td>
                    <td class="text-right">{{ number_format($displayRate, 2) }}</td>
                    <td class="text-center">{{ $item->product->pack_size ?? 1 }}</td>
                    <td class="text-center">{{ (int)$item->qty_ct }}/{{ (int)$item->qty_un }}</td>
                    <td class="text-right">{{ number_format($basePrice, 2) }}</td>
                    
                    @if($item->is_free)
                        <td class="text-right">0.00</td>
                        @if(!$isIgst)
                            <td class="text-right">0.00</td>
                        @endif
                        <td class="text-right">0.00</td>
                        <td class="text-right bold">0.00</td>
                    @else
                        <td class="text-right">{{ number_format($item->taxable, 2) }}</td>
                        @if(!$isIgst)
                            <td class="text-right">{{ number_format($item->cgst, 2) }}</td>
                        @endif
                        <td class="text-right">{{ number_format($item->sgst, 2) }}</td>
                        <td class="text-right bold">{{ number_format($item->line_total, 2) }}</td>
                    @endif
                </tr>
                @endforeach
                
                <tr class="totals-row">
                    <td colspan="6" class="text-right">TOTAL</td>
                    <td class="text-center">{{ $totQtyCt }}/{{ $totQtyUn }}</td>
                    <td class="text-right">{{ number_format($totBasePrice, 2) }}</td>
                    <td class="text-right">{{ number_format($totTaxable, 2) }}</td>
                    @if(!$isIgst)
                        <td class="text-right">{{ number_format($totCgst, 2) }}</td>
                    @endif
                    <td class="text-right">{{ number_format($totSgst, 2) }}</td>
                    <td class="text-right">{{ number_format($totAmount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- HSN Summary -->
        <div class="hsn-title">HSN-wise Tax Summary</div>
        <table class="hsn-table">
            <thead>
                <tr>
                    <th rowspan="2">HSN Code</th>
                    <th rowspan="2" class="text-right">Taxable Value</th>
                    @if(!$isIgst)
                        <th colspan="2" class="text-center">Central Tax</th>
                    @endif
                    <th colspan="2" class="text-center">{{ $sgstLabel }}</th>
                </tr>
                <tr>
                    @if(!$isIgst)
                        <th class="text-center border-l" style="border-left: 1px solid #e5e7eb;">Tax %</th>
                        <th class="text-right">Amount</th>
                    @endif
                    <th class="text-center border-l" style="border-left: 1px solid #e5e7eb;">Tax %</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $hsnGroups = [];
                    foreach($invoice->invoiceItems as $it) {
                        if($it->is_free) continue;
                        $hsn = $it->hsn_code;
                        if(!isset($hsnGroups[$hsn])) {
                            $hsnGroups[$hsn] = [
                                'taxable' => 0, 
                                'cgst' => 0, 
                                'sgst' => 0, 
                                'cgst_rate' => $it->cgst_rate,
                                'sgst_rate' => $it->sgst_rate
                            ];
                        }
                        $hsnGroups[$hsn]['taxable'] += $it->taxable;
                        $hsnGroups[$hsn]['cgst'] += $it->cgst;
                        $hsnGroups[$hsn]['sgst'] += $it->sgst;
                    }
                @endphp
                @foreach($hsnGroups as $hsn => $data)
                <tr>
                    <td class="text-center">{{ $hsn }}</td>
                    <td class="text-right">{{ number_format($data['taxable'], 2) }}</td>
                    @if(!$isIgst)
                        <td class="text-center">{{ number_format($data['cgst_rate'], 1) }}%</td>
                        <td class="text-right">{{ number_format($data['cgst'], 2) }}</td>
                    @endif
                    <td class="text-center">{{ number_format($data['sgst_rate'], 1) }}%</td>
                    <td class="text-right">{{ number_format($data['sgst'], 2) }}</td>
                </tr>
                @endforeach
                <tr style="font-weight: bold; background-color: #f8fafc;">
                    <td class="text-right">Total</td>
                    <td class="text-right">{{ number_format($totTaxable, 2) }}</td>
                    @if(!$isIgst)
                        <td class="text-center"></td>
                        <td class="text-right">{{ number_format($totCgst, 2) }}</td>
                    @endif
                    <td class="text-center"></td>
                    <td class="text-right">{{ number_format($totSgst, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Bottom Section -->
        <div class="bottom-section">
            <div class="declaration-box">
                <div class="mb-2"><strong style="color: #1e3a8a; text-transform: uppercase;">TERMS &amp; CONDITIONS:</strong></div>
                <div style="font-size: 11px; color: #000; font-weight: 700; line-height: 1.55;">
                    1. Goods once sold will not be taken back or exchanged. Please check items at the time of delivery.<br>
                    2. Payment is due within 7 days from the invoice date; overdue payments may attract interest @18% per annum.<br>
                    3. Any damage or shortage must be reported within 12 hours of delivery.<br>
                    4. All disputes are subject to jurisdiction of Daman court only.<br>
                    5. A penalty of Rs 500 will be charged for any cheque returned.
                </div>

                <div class="mb-2" style="margin-top: 8px;"><strong style="color: #1e3a8a; text-transform: uppercase;">BANK DETAILS:</strong></div>
                <div style="display: table; width: 100%;">
                    <div style="display: table-cell; width: 70%; vertical-align: top;">
                        @php
                            $bankName = trim((string) ($settings['bank_name'] ?? 'IDBI BANK'));
                            $bankAccountName = trim((string) ($settings['bank_account_name'] ?? 'SAGARDUTT PEPSI DISTRIBUTORS'));
                            $bankAccountNumber = trim((string) ($settings['bank_account_number'] ?? '3181102000000550'));
                            $bankIfsc = trim((string) ($settings['bank_ifsc'] ?? 'IBKL0000318'));
                            $bankBranch = trim((string) ($settings['bank_branch'] ?? 'NANI DAMAN'));
                        @endphp
                        <table class="bank-table" style="font-size: 11px; font-weight: 800; color: #000;">
                            <tr><td style="font-weight:800; color:#000;">Bank Name</td><td style="font-weight:800; color:#000;">{{ $bankName }}</td></tr>
                            <tr><td style="font-weight:800; color:#000;">Account Name</td><td style="font-weight:800; color:#000;">{{ $bankAccountName }}</td></tr>
                            <tr><td style="font-weight:800; color:#000;">Account Number</td><td style="font-weight:800; color:#000;">{{ $bankAccountNumber }}</td></tr>
                            <tr><td style="font-weight:800; color:#000;">IFSC Code</td><td style="font-weight:800; color:#000;">{{ $bankIfsc }}</td></tr>
                            <tr><td style="font-weight:800; color:#000;">Branch</td><td style="font-weight:800; color:#000;">{{ $bankBranch }}</td></tr>
                        </table>
                    </div>
                    <div style="display: table-cell; width: 30%; vertical-align: middle; text-align: right;">
                        <div class="qr-box">
                            @php
                                $qrSettingPath = trim((string) ($settings['invoice_qr_path'] ?? ''));
                                $qrFromSettings = $qrSettingPath !== '' ? storage_path('app/public/' . $qrSettingPath) : null;
                                $qrPublicFile = public_path('images/payment_qr.jpg');

                                $qrSrc = null;
                                if (request()->has('pdf')) {
                                    $qrSrc = ($qrFromSettings && file_exists($qrFromSettings))
                                        ? $qrFromSettings
                                        : (file_exists($qrPublicFile) ? $qrPublicFile : null);
                                } else {
                                    $qrPublicUrl = asset('images/payment_qr.jpg');
                                    $qrFromSettingsUrl = ($qrFromSettings && file_exists($qrFromSettings))
                                        ? asset('storage/' . ltrim($qrSettingPath, '/'))
                                        : null;
                                    $qrSrc = $qrFromSettingsUrl ?: $qrPublicUrl;
                                }
                            @endphp
                            @if($qrSrc)
                                <img src="{{ $qrSrc }}" alt="QR" style="width:78px; height:78px; object-fit: contain; display:block; border: 1px solid #e5e7eb; background:#fff; padding: 2px;">
                            @else 
                                <svg width="78" height="78" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="100" height="100" fill="#fff"></rect>
                                    <rect x="6" y="6" width="26" height="26" fill="#000"></rect>
                                    <rect x="10" y="10" width="18" height="18" fill="#fff"></rect>
                                    <rect x="14" y="14" width="10" height="10" fill="#000"></rect>
                                    <rect x="68" y="6" width="26" height="26" fill="#000"></rect>
                                    <rect x="72" y="10" width="18" height="18" fill="#fff"></rect>
                                    <rect x="76" y="14" width="10" height="10" fill="#000"></rect>
                                    <rect x="6" y="68" width="26" height="26" fill="#000"></rect>
                                    <rect x="10" y="72" width="18" height="18" fill="#fff"></rect>
                                    <rect x="14" y="76" width="10" height="10" fill="#000"></rect>
                                    <rect x="42" y="10" width="6" height="6" fill="#000"></rect>
                                    <rect x="52" y="10" width="6" height="6" fill="#000"></rect>
                                    <rect x="42" y="20" width="6" height="6" fill="#000"></rect>
                                    <rect x="52" y="20" width="6" height="6" fill="#000"></rect>
                                    <rect x="38" y="32" width="6" height="6" fill="#000"></rect>
                                    <rect x="48" y="32" width="6" height="6" fill="#000"></rect>
                                    <rect x="58" y="32" width="6" height="6" fill="#000"></rect>
                                    <rect x="68" y="32" width="6" height="6" fill="#000"></rect>
                                    <rect x="32" y="42" width="6" height="6" fill="#000"></rect>
                                    <rect x="42" y="42" width="6" height="6" fill="#000"></rect>
                                    <rect x="56" y="42" width="6" height="6" fill="#000"></rect>
                                    <rect x="66" y="42" width="6" height="6" fill="#000"></rect>
                                    <rect x="76" y="42" width="6" height="6" fill="#000"></rect>
                                    <rect x="32" y="52" width="6" height="6" fill="#000"></rect>
                                    <rect x="46" y="52" width="6" height="6" fill="#000"></rect>
                                    <rect x="56" y="52" width="6" height="6" fill="#000"></rect>
                                    <rect x="66" y="52" width="6" height="6" fill="#000"></rect>
                                    <rect x="38" y="62" width="6" height="6" fill="#000"></rect>
                                    <rect x="48" y="62" width="6" height="6" fill="#000"></rect>
                                    <rect x="58" y="62" width="6" height="6" fill="#000"></rect>
                                    <rect x="68" y="62" width="6" height="6" fill="#000"></rect>
                                    <rect x="78" y="62" width="6" height="6" fill="#000"></rect>
                                    <rect x="42" y="72" width="6" height="6" fill="#000"></rect>
                                    <rect x="52" y="72" width="6" height="6" fill="#000"></rect>
                                    <rect x="62" y="72" width="6" height="6" fill="#000"></rect>
                                    <rect x="42" y="82" width="6" height="6" fill="#000"></rect>
                                    <rect x="62" y="82" width="6" height="6" fill="#000"></rect>
                                </svg>
                            @endif
                            <div class="qr-label">Scan QR</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="totals-box">
                <div class="total-row">
                    <div>Taxable Amount</div>
                    <div class="bold">{{ number_format($totTaxable, 2) }}</div>
                </div>
                @if($isIgst)
                <div class="total-row">
                    <div>Total IGST</div>
                    <div class="bold">{{ number_format($totCgst + $totSgst, 2) }}</div>
                </div>
                @else
                <div class="total-row">
                    <div>Total CGST</div>
                    <div class="bold">{{ number_format($totCgst, 2) }}</div>
                </div>
                <div class="total-row">
                    <div>Total SGST</div>
                    <div class="bold">{{ number_format($totSgst, 2) }}</div>
                </div>
                @endif
                <div class="total-row">
                    <div>Round Off</div>
                    <div class="bold">{{ number_format($invoice->round_off, 2) }}</div>
                </div>
                <div class="amount-payable">
                    <div>Grand Total</div>
                    <div><span class="inr">&#8377;</span> {{ number_format($invoice->grand_total, 2) }}</div>
                </div>
                <div class="amount-words" style="margin-top: 6px; font-size: 12px; font-weight: 700;">
                    @php
                        $rawWords = trim((string) ($invoice->amount_in_words ?? ''));
                        $normalized = preg_replace('/\s+/', ' ', $rawWords);
                        $normalized = preg_replace('/^rupees\s+/i', '', $normalized);
                        $normalized = preg_replace('/\brupees\b/i', '', $normalized);
                        $normalized = preg_replace('/\bonly\b/i', '', $normalized);
                        $normalized = trim(preg_replace('/\s+/', ' ', $normalized));
                        $finalWords = ucfirst(strtolower($normalized));
                        $finalWords = trim($finalWords, " .");
                        $finalWords = $finalWords !== '' ? $finalWords . ' rupees only.' : '';
                    @endphp
                    <strong style="color: #1e3a8a; font-weight: 700;">Amount in Words:</strong>
                    <span style="font-weight: 700;">{{ $finalWords }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-left">
                Printed On: {{ now()->format('d-m-Y, g:i:s a') }}
            </div>
            <div class="signature-box">
                <div class="signature-block">
                    <div class="bold text-blue mb-4" style="font-size: 13px;">Inspected and Received by</div>
                    <div class="signature-line" style="font-size: 13px;">Signature</div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
