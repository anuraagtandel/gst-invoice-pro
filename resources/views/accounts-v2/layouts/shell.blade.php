<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Accounts V2')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Syne:wght@400..800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'DM Sans', sans-serif; background-color: #f8fafc; }
        h1, h2, h3, h4, h5, h6, .font-syne { font-family: 'Syne', sans-serif; }

        @media print {
            @page { size: A4 portrait; margin: 12mm; }
            html, body { background: #fff !important; }
            header { display: none !important; }
            .no-print { display: none !important; }
            .shadow-sm { box-shadow: none !important; }
            .backdrop-blur { backdrop-filter: none !important; }
            .border { border-color: #e5e7eb !important; }
            table { font-size: 10.5px !important; }
            th, td { padding-top: 5px !important; padding-bottom: 5px !important; }
            tr { break-inside: avoid; page-break-inside: avoid; }
            thead { display: table-header-group; }
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased text-slate-800 min-h-screen">
    <header class="sticky top-0 z-10 bg-white/90 backdrop-blur border-b border-slate-200 no-print">
        <div class="max-w-7xl mx-auto px-6 h-14 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center font-syne font-bold text-sm">
                    AV2
                </div>
                <div class="font-semibold text-slate-900">Accounts V2</div>
            </div>
            <a href="{{ route('app.dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900">Back to Dashboard</a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-6">
        <div class="mb-5">
            <h1 class="text-xl font-syne font-bold text-slate-900">@yield('page-title')</h1>
            <div class="mt-1 text-sm text-slate-600">@yield('page-subtitle')</div>
        </div>

        <main>
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
