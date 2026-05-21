@extends('layouts.app')

@section('title', 'Sales Register Report')

@section('content')
@php
    $formatIndian = function ($value, int $decimals = 2): string {
        if ($value === '-' || $value === null || $value === '') return '-';
        $n = (float) $value;
        $neg = $n < 0 ? '-' : '';
        $n = abs($n);
        $base = number_format($n, $decimals, '.', '');
        [$intPart, $decPart] = array_pad(explode('.', $base, 2), 2, null);
        if (strlen($intPart) > 3) {
            $last3 = substr($intPart, -3);
            $rest = substr($intPart, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $intPart = $rest . ',' . $last3;
        }
        return $neg . $intPart . ($decimals > 0 ? '.' . $decPart : '');
    };
@endphp

<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Sales Register Report</h3>
            <p class="text-sm text-slate-500">Invoice-wise sales register with item-level rows and totals.</p>
        </div>

        <div class="w-full lg:w-auto flex flex-col sm:flex-row gap-3 sm:items-center">
            <div class="dash-filter-group">
                <a href="{{ route('app.reports.sales-register', ['range' => 'today']) }}" class="dash-filter-btn {{ $preset === 'today' ? 'dash-filter-btn--active' : '' }}">Today</a>
                <a href="{{ route('app.reports.sales-register', ['range' => 'week']) }}" class="dash-filter-btn {{ $preset === 'week' ? 'dash-filter-btn--active' : '' }}">This Week</a>
                <a href="{{ route('app.reports.sales-register', ['range' => 'month']) }}" class="dash-filter-btn {{ $preset === 'month' ? 'dash-filter-btn--active' : '' }}">This Month</a>
                <a href="{{ route('app.reports.sales-register', ['range' => 'custom', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="dash-filter-btn {{ $preset === 'custom' ? 'dash-filter-btn--active' : '' }}">Custom</a>
            </div>

            <form method="GET" action="{{ route('app.reports.sales-register') }}" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto {{ $preset === 'custom' ? '' : 'hidden' }}">
                <input type="hidden" name="range" value="custom">
                <div class="flex flex-col gap-1 w-full sm:w-auto">
                    <label class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">From</label>
                    <input type="date" name="from_date" value="{{ $fromDate }}" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="flex flex-col gap-1 w-full sm:w-auto">
                    <label class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">To</label>
                    <input type="date" name="to_date" value="{{ $toDate }}" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm self-end">Apply</button>
            </form>

            <a href="{{ route('app.reports.sales-register.export', ['range' => $preset, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="w-full sm:w-auto px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm text-center">
                Export to Excel
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if(empty($rows))
            <div class="p-6 text-slate-500 text-sm text-center">No data in selected period.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 sticky top-0 z-10">
                        <tr>
                            @foreach($headings as $h)
                                <th class="px-3 py-3 font-semibold text-center">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-center">
                        @foreach($rows as $r)
                            @php $isTotal = ((string) ($r[4] ?? '') === '0'); @endphp
                            <tr class="{{ $isTotal ? 'bg-slate-50 font-semibold' : 'hover:bg-slate-50/50' }}">
                                <td class="px-3 py-2">{{ $r[0] }}</td>
                                <td class="px-3 py-2">{{ $r[1] }}</td>
                                <td class="px-3 py-2">{{ $r[2] }}</td>
                                <td class="px-3 py-2">{{ $r[3] }}</td>
                                <td class="px-3 py-2">{{ $r[4] }}</td>
                                <td class="px-3 py-2">{{ $r[5] }}</td>
                                <td class="px-3 py-2">{{ $r[6] }}</td>
                                <td class="px-3 py-2">{{ $r[7] }}</td>
                                <td class="px-3 py-2">{{ $r[8] }}</td>
                                <td class="px-3 py-2">{{ $r[9] }}</td>
                                <td class="px-3 py-2">{{ $r[10] }}</td>
                                <td class="px-3 py-2">{{ $r[11] }}</td>
                                <td class="px-3 py-2">{{ $r[12] }}</td>
                                <td class="px-3 py-2">{{ $r[13] }}</td>
                                <td class="px-3 py-2">{{ $r[14] }}</td>
                                <td class="px-3 py-2">{{ $r[15] }}</td>
                                <td class="px-3 py-2">{{ $r[16] }}</td>
                                <td class="px-3 py-2">{{ $r[17] }}</td>
                                <td class="px-3 py-2">{{ $r[18] }}</td>
                                <td class="px-3 py-2">{{ $r[19] }}</td>
                                <td class="px-3 py-2">{{ $r[20] }}</td>
                                <td class="px-3 py-2">{{ $formatIndian($r[21], 0) }}</td>
                                <td class="px-3 py-2">{{ $formatIndian($r[22], 0) }}</td>
                                <td class="px-3 py-2">{{ $formatIndian($r[23], 0) }}</td>
                                <td class="px-3 py-2">₹{{ $formatIndian($r[24], 2) }}</td>
                                <td class="px-3 py-2">₹{{ $formatIndian($r[25], 2) }}</td>
                                <td class="px-3 py-2">₹{{ $formatIndian($r[26], 2) }}</td>
                                <td class="px-3 py-2">₹{{ $formatIndian($r[27], 2) }}</td>
                                <td class="px-3 py-2">{{ $formatIndian($r[28], 2) }}</td>
                                <td class="px-3 py-2">₹{{ $formatIndian($r[29], 2) }}</td>
                                <td class="px-3 py-2">{{ $formatIndian($r[30], 2) }}</td>
                                <td class="px-3 py-2">₹{{ $formatIndian($r[31], 2) }}</td>
                                <td class="px-3 py-2">{{ $formatIndian($r[32], 2) }}</td>
                                <td class="px-3 py-2">₹{{ $formatIndian($r[33], 2) }}</td>
                                <td class="px-3 py-2">₹{{ $formatIndian($r[34], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
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
@endsection
