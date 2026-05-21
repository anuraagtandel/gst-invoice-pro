@php
    $value = (float) ($kpi['value'] ?? 0);
    $change = $kpi['change_pct'] ?? null;
    $kind = $kpi['kind'] ?? 'count';
    $accent = $kpi['accent'] ?? 'from-slate-500 to-slate-700';
    $icon = $kpi['icon'] ?? 'ph-chart-line-up';

    $isUp = is_numeric($change) && $change > 0;
    $isDown = is_numeric($change) && $change < 0;
    $changeLabel = '—';
    if (is_numeric($change)) {
        $abs = abs($change);
        $fixed = number_format($abs, 1, '.', '');
        $fixed = rtrim(rtrim($fixed, '0'), '.');
        $changeLabel = ($isUp ? '+' : ($isDown ? '-' : '')) . $fixed . '%';
    }

    $valueLabel = (string) $value;
    if ($kind === 'currency') {
        $v6 = number_format($value, 6, '.', '');
        $valueLabel = '₹ ' . rtrim(rtrim($v6, '0'), '.');
    } else {
        $valueLabel = (string) (int) round($value);
    }

    $series = $series ?? [];
    $w = 96;
    $h = 28;
    $pad = 2;
    $min = null;
    $max = null;
    foreach ($series as $v) {
        $n = (float) $v;
        $min = $min === null ? $n : min($min, $n);
        $max = $max === null ? $n : max($max, $n);
    }
    if ($min === null) {
        $min = 0;
        $max = 0;
    }
    $range = max(0.0000001, $max - $min);
    $count = max(1, count($series));
    $step = $count === 1 ? 0 : ($w - ($pad * 2)) / ($count - 1);
    $points = [];
    for ($i = 0; $i < $count; $i++) {
        $x = $pad + ($i * $step);
        $y = $h - $pad - (((float) $series[$i] - $min) / $range) * ($h - ($pad * 2));
        $points[] = number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
    }
    $pointsStr = implode(' ', $points);
@endphp

<div class="relative overflow-hidden rounded-2xl border border-slate-200/60 bg-white/70 shadow-[0_10px_30px_-18px_rgba(15,23,42,0.35)] backdrop-blur">
    <div class="absolute inset-0 opacity-10 bg-gradient-to-br {{ $accent }}"></div>
    <div class="absolute -top-14 -right-14 h-36 w-36 rounded-full bg-gradient-to-br {{ $accent }} opacity-20 blur-2xl"></div>
    <div class="relative p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ $kpi['label'] ?? '' }}</div>
                <div class="mt-2 text-2xl font-syne font-bold text-slate-900">{{ $valueLabel }}</div>
                <div class="mt-2 flex items-center gap-2 text-xs font-semibold">
                    <span class="{{ $isUp ? 'text-emerald-700' : ($isDown ? 'text-red-700' : 'text-slate-500') }}">
                        @if($isUp)
                            <i class="ph ph-arrow-up-right"></i>
                        @elseif($isDown)
                            <i class="ph ph-arrow-down-right"></i>
                        @else
                            <i class="ph ph-minus"></i>
                        @endif
                        {{ $changeLabel }}
                    </span>
                    <span class="text-slate-500">vs previous period</span>
                </div>
            </div>
            <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br {{ $accent }} text-white shadow-sm shrink-0">
                <i class="ph {{ $icon }} text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-end justify-between">
            <svg width="{{ $w }}" height="{{ $h }}" viewBox="0 0 {{ $w }} {{ $h }}" class="text-slate-900">
                <polyline points="{{ $pointsStr }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0.75"></polyline>
            </svg>
            <div class="text-[11px] font-semibold text-slate-500">
                {{ \Illuminate\Support\Carbon::parse($rangeMeta['from'])->format('d M') }} – {{ \Illuminate\Support\Carbon::parse($rangeMeta['to'])->format('d M') }}
            </div>
        </div>
    </div>
</div>
