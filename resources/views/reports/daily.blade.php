@extends('layouts.app')

@section('title', 'Daily Report')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Daily Report</h3>
            <p class="text-sm text-slate-500">Sales and scheme summary by salesman in pivot format.</p>
        </div>

        <div class="w-full lg:w-auto flex flex-col sm:flex-row gap-3 sm:items-center">
            <div class="dash-filter-group">
                @php $base = ['tab' => $tab, 'salesman' => $selectedSalesmen ?? []]; @endphp
                <a href="{{ route('app.reports.daily', array_merge($base, ['range' => 'today'])) }}" class="dash-filter-btn {{ $preset === 'today' ? 'dash-filter-btn--active' : '' }}">Today</a>
                <a href="{{ route('app.reports.daily', array_merge($base, ['range' => 'week'])) }}" class="dash-filter-btn {{ $preset === 'week' ? 'dash-filter-btn--active' : '' }}">This Week</a>
                <a href="{{ route('app.reports.daily', array_merge($base, ['range' => 'month'])) }}" class="dash-filter-btn {{ $preset === 'month' ? 'dash-filter-btn--active' : '' }}">This Month</a>
                <a href="{{ route('app.reports.daily', array_merge($base, ['range' => 'custom', 'from_date' => $fromDate, 'to_date' => $toDate])) }}" class="dash-filter-btn {{ $preset === 'custom' ? 'dash-filter-btn--active' : '' }}">Custom</a>
            </div>

            @php
                $sel = $selectedSalesmen ?? [];
                $selCount = is_array($sel) ? count($sel) : 0;
                $selLabel = $selCount === 0 ? 'All' : ($selCount === 1 ? '1 selected' : ($selCount . ' selected'));
            @endphp
            <form method="GET" action="{{ route('app.reports.daily') }}" class="relative">
                <input type="hidden" name="range" value="{{ $preset }}">
                <input type="hidden" name="from_date" value="{{ $fromDate }}">
                <input type="hidden" name="to_date" value="{{ $toDate }}">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <details class="relative">
                    <summary class="list-none cursor-pointer px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 shadow-sm flex items-center gap-2 select-none">
                        <span>Salesman:</span>
                        <span class="text-slate-500">{{ $selLabel }}</span>
                    </summary>
                    <div class="absolute right-0 mt-2 w-72 rounded-xl border border-slate-200 bg-white shadow-lg p-3 z-20">
                        <input type="text" data-salesman-search placeholder="Search..." class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <div class="mt-2 max-h-56 overflow-y-auto space-y-1" data-salesman-list>
                            <label class="flex items-center gap-2 px-2 py-1 rounded hover:bg-slate-50">
                                <input type="checkbox" name="salesman[]" value="__NONE__" @checked(in_array('__NONE__', $sel ?? [], true)) class="rounded border-slate-300">
                                <span class="text-sm text-slate-700">Unassigned</span>
                            </label>
                            @foreach(($activeSalesmen ?? []) as $s)
                                <label class="flex items-center gap-2 px-2 py-1 rounded hover:bg-slate-50" data-salesman-item>
                                    <input type="checkbox" name="salesman[]" value="{{ $s }}" @checked(in_array($s, $sel ?? [], true)) class="rounded border-slate-300">
                                    <span class="text-sm text-slate-700">{{ $s }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-2">
                            <a href="{{ route('app.reports.daily', ['tab' => $tab, 'range' => $preset, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700">Clear</a>
                            <button type="submit" class="px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold">Apply</button>
                        </div>
                    </div>
                </details>
            </form>

            <form method="GET" action="{{ route('app.reports.daily') }}" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto {{ $preset === 'custom' ? '' : 'hidden' }}">
                <input type="hidden" name="range" value="custom">
                <input type="hidden" name="tab" value="{{ $tab }}">
                @foreach(($selectedSalesmen ?? []) as $s)
                    <input type="hidden" name="salesman[]" value="{{ $s }}">
                @endforeach
                <div class="flex flex-col gap-1 w-full sm:w-auto">
                    <label class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">From</label>
                    <input type="date" name="from_date" value="{{ $fromDate }}" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                </div>
                <div class="flex flex-col gap-1 w-full sm:w-auto">
                    <label class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">To</label>
                    <input type="date" name="to_date" value="{{ $toDate }}" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm self-end">Apply</button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden" x-data="{ openKey: null }">
        <div class="px-5 py-4 border-b border-slate-200">
            <h4 class="font-syne font-semibold text-base text-slate-800">Cash Collection By Salesman</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Salesman</th>
                        <th class="px-5 py-3 font-semibold text-right whitespace-nowrap">Cash Invoices</th>
                        <th class="px-5 py-3 font-semibold text-right whitespace-nowrap">Cash Collected</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse(($cashCollection ?? []) as $i => $row)
                        @php
                            $k = 's' . (string) $i;
                            $inv = is_array($row['invoices'] ?? null) ? $row['invoices'] : [];
                            $invTotal = 0.0;
                            foreach ($inv as $x) { $invTotal += (float) ($x['amount'] ?? 0); }
                        @endphp
                        <tr class="hover:bg-slate-50/60 cursor-pointer" @click="openKey = openKey === '{{ $k }}' ? null : '{{ $k }}'">
                            <td class="px-5 py-3 font-semibold text-slate-800">{{ $row['salesman'] ?? '-' }}</td>
                            <td class="px-5 py-3 text-right text-slate-700">{{ number_format((int) ($row['invoice_count'] ?? 0)) }}</td>
                            <td class="px-5 py-3 text-right font-bold text-slate-900 whitespace-nowrap">₹{{ number_format((float) ($row['cash_collected'] ?? 0), 2) }}</td>
                        </tr>
                        <tr x-show="openKey === '{{ $k }}'" x-cloak>
                            <td colspan="3" class="px-5 py-4 bg-slate-50/40">
                                <div x-show="openKey === '{{ $k }}'" x-transition.opacity.duration.150ms class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                    <table class="min-w-full text-left text-[13px]">
                                        <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                                            <tr>
                                                <th class="px-4 py-2 font-semibold whitespace-nowrap">Invoice No</th>
                                                <th class="px-4 py-2 font-semibold whitespace-nowrap">Date</th>
                                                <th class="px-4 py-2 font-semibold">Customer Name</th>
                                                <th class="px-4 py-2 font-semibold text-right whitespace-nowrap">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse($inv as $x)
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="px-4 py-2 font-semibold text-indigo-700 whitespace-nowrap">
                                                        <a href="{{ route('app.invoices.print', ['id' => (int) ($x['id'] ?? 0)]) }}" target="_blank" class="hover:underline">
                                                            {{ $x['invoice_no'] ?? '' }}
                                                        </a>
                                                    </td>
                                                    <td class="px-4 py-2 text-slate-600 whitespace-nowrap">{{ $x['invoice_date'] ?? '' }}</td>
                                                    <td class="px-4 py-2 text-slate-800">{{ $x['customer_name'] ?? '-' }}</td>
                                                    <td class="px-4 py-2 text-right font-semibold text-slate-900 whitespace-nowrap">₹{{ number_format((float) ($x['amount'] ?? 0), 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-4 py-4 text-center text-slate-500">No cash invoices.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="bg-slate-50 border-t border-slate-200">
                                            <tr>
                                                <td class="px-4 py-2 font-bold text-slate-900" colspan="3">Total</td>
                                                <td class="px-4 py-2 text-right font-bold text-slate-900 whitespace-nowrap">₹{{ number_format((float) $invTotal, 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-slate-500">No cash collection in selected period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <a href="{{ route('app.reports.daily', ['tab' => 'sales', 'range' => $preset, 'from_date' => $fromDate, 'to_date' => $toDate, 'salesman' => $selectedSalesmen ?? []]) }}" class="px-3 py-2 rounded-lg text-sm font-semibold {{ $tab === 'sales' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">Sales Report</a>
                <a href="{{ route('app.reports.daily', ['tab' => 'scheme', 'range' => $preset, 'from_date' => $fromDate, 'to_date' => $toDate, 'salesman' => $selectedSalesmen ?? []]) }}" class="px-3 py-2 rounded-lg text-sm font-semibold {{ $tab === 'scheme' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">Scheme Report</a>
            </div>

            <div class="flex items-center gap-2">
                @if($tab === 'sales')
                    <a href="{{ route('app.reports.daily.exportSales', ['range' => $preset, 'from_date' => $fromDate, 'to_date' => $toDate, 'salesman' => $selectedSalesmen ?? []]) }}" class="px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">Export to Excel</a>
                @else
                    <a href="{{ route('app.reports.daily.exportScheme', ['range' => $preset, 'from_date' => $fromDate, 'to_date' => $toDate, 'salesman' => $selectedSalesmen ?? []]) }}" class="px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">Export to Excel</a>
                @endif
            </div>
        </div>

        @if($tab === 'sales')
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 font-semibold sticky left-0 bg-slate-50 z-20 w-20">Sr No.</th>
                            <th class="px-4 py-3 font-semibold sticky left-20 bg-slate-50 z-20 min-w-[280px]">Product Name</th>
                            @foreach($sales['salesmen'] as $s)
                                <th class="px-4 py-3 font-semibold text-right min-w-[120px]">{{ $s }}</th>
                            @endforeach
                            <th class="px-4 py-3 font-semibold text-right min-w-[120px]">Total CT</th>
                            <th class="px-4 py-3 font-semibold text-right min-w-[120px]">Rate/CT</th>
                            <th class="px-4 py-3 font-semibold text-right min-w-[160px]">Total Amount ₹</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($sales['rows'] as $r)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3 sticky left-0 bg-white z-10 text-slate-600">{{ $r['sr'] }}</td>
                                <td class="px-4 py-3 sticky left-20 bg-white z-10 font-semibold text-slate-800">{{ $r['product_name'] }}</td>
                                @foreach($sales['salesmen'] as $s)
                                    <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float) ($r['salesmen'][$s] ?? 0), 2, '.', '') }}</td>
                                @endforeach
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format((float) $r['total_ct'], 2, '.', '') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float) $r['rate_per_ct'], 2, '.', '') }}</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900">{{ number_format((float) $r['total_amount'], 2, '.', '') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 5 + count($sales['salesmen']) }}" class="px-6 py-8 text-center text-slate-500">No sales in selected period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($sales['rows']) > 0)
                        <tfoot class="bg-slate-50 border-t border-slate-200">
                            <tr>
                                <td class="px-4 py-3 sticky left-0 bg-slate-50 z-10"></td>
                                <td class="px-4 py-3 sticky left-20 bg-slate-50 z-10 font-bold text-slate-900">Total</td>
                                @foreach($sales['salesmen'] as $s)
                                    <td class="px-4 py-3"></td>
                                @endforeach
                                <td class="px-4 py-3 text-right font-bold text-slate-900">{{ number_format((float) $sales['footer']['total_ct'], 2, '.', '') }}</td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900">{{ number_format((float) $sales['footer']['total_amount'], 2, '.', '') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 font-semibold sticky left-0 bg-slate-50 z-20 w-20">Sr No.</th>
                            <th class="px-4 py-3 font-semibold sticky left-20 bg-slate-50 z-20 min-w-[280px]">Free Product Name</th>
                            @foreach($scheme['salesmen'] as $s)
                                <th class="px-4 py-3 font-semibold text-right min-w-[120px]">{{ $s }}</th>
                            @endforeach
                            <th class="px-4 py-3 font-semibold text-right min-w-[120px]">Total UN</th>
                            <th class="px-4 py-3 font-semibold text-right min-w-[120px]">Rate/UN</th>
                            <th class="px-4 py-3 font-semibold text-right min-w-[160px]">Total Value ₹</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($scheme['rows'] as $r)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3 sticky left-0 bg-white z-10 text-slate-600">{{ $r['sr'] }}</td>
                                <td class="px-4 py-3 sticky left-20 bg-white z-10 font-semibold text-slate-800">{{ $r['product_name'] }}</td>
                                @foreach($scheme['salesmen'] as $s)
                                    <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float) ($r['salesmen'][$s] ?? 0), 2, '.', '') }}</td>
                                @endforeach
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format((float) $r['total_un'], 2, '.', '') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float) $r['rate_per_un'], 2, '.', '') }}</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900">{{ number_format((float) $r['total_value'], 2, '.', '') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 5 + count($scheme['salesmen']) }}" class="px-6 py-8 text-center text-slate-500">No scheme/free items in selected period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($scheme['rows']) > 0)
                        <tfoot class="bg-slate-50 border-t border-slate-200">
                            <tr>
                                <td class="px-4 py-3 sticky left-0 bg-slate-50 z-10"></td>
                                <td class="px-4 py-3 sticky left-20 bg-slate-50 z-10 font-bold text-slate-900">Total</td>
                                @foreach($scheme['salesmen'] as $s)
                                    <td class="px-4 py-3"></td>
                                @endforeach
                                <td class="px-4 py-3 text-right font-bold text-slate-900">{{ number_format((float) $scheme['footer']['total_un'], 2, '.', '') }}</td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900">{{ number_format((float) $scheme['footer']['total_value'], 2, '.', '') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    .dash-filter-group {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 4px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(226, 232, 240, 1);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
    }
    .dash-filter-btn {
        height: 40px;
        width: 112px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
        color: rgba(51, 65, 85, 1);
        background: transparent;
        transition: background-color 120ms ease, color 120ms ease;
        user-select: none;
        text-decoration: none;
    }
    .dash-filter-btn:hover { background: rgba(241, 245, 249, 1); }
    .dash-filter-btn--active {
        background: rgba(15, 23, 42, 1);
        color: rgba(255, 255, 255, 1);
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        for (const root of document.querySelectorAll('form')) {
            const search = root.querySelector('[data-salesman-search]');
            const list = root.querySelector('[data-salesman-list]');
            if (!search || !list) continue;
            const items = Array.from(list.querySelectorAll('[data-salesman-item]'));
            const apply = () => {
                const q = (search.value || '').toLowerCase().trim();
                for (const el of items) {
                    const text = (el.textContent || '').toLowerCase();
                    el.style.display = q === '' || text.includes(q) ? '' : 'none';
                }
            };
            search.addEventListener('input', apply);
            apply();
        }

        const openPicker = (el) => {
            if (!el) return;
            try { el.focus(); } catch (e) { }
            if (typeof el.showPicker === 'function') {
                try { el.showPicker(); } catch (e) { }
            }
        };
        for (const form of document.querySelectorAll('form')) {
            const from = form.querySelector('input[type="date"][name="from_date"]');
            const to = form.querySelector('input[type="date"][name="to_date"]');
            if (from) {
                from.addEventListener('click', () => openPicker(from));
                from.addEventListener('focus', () => openPicker(from));
            }
            if (to) {
                to.addEventListener('click', () => openPicker(to));
                to.addEventListener('focus', () => openPicker(to));
            }
        }
    });
</script>
@endpush
@endsection
