@extends('layouts.app')

@section('title', 'Customer Import Details')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Customer Import Details</h3>
            <p class="text-sm text-slate-500">{{ $import->file_name }}</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <a href="{{ route('app.customers.imports.index') }}" class="w-full sm:w-auto bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold transition-colors border border-slate-200 shadow-sm">
                Back to History
            </a>
            @if(!empty($import->error_rows))
                <a href="{{ route('app.customers.upload.errors', $import->token) }}" class="w-full sm:w-auto bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold transition-colors border border-slate-200 shadow-sm">
                    Download Error Excel File
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-500 mb-1">Uploaded</p>
            <p class="font-semibold text-slate-800">{{ optional($import->uploaded_at)->format('d M, Y H:i') }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-500 mb-1">Imported</p>
            <p class="font-syne font-bold text-xl text-slate-800">{{ $import->imported_count }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-500 mb-1">Failed</p>
            <p class="font-syne font-bold text-xl {{ $import->failed_count > 0 ? 'text-red-700' : 'text-slate-800' }}">{{ $import->failed_count }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-500 mb-1">Duplicate Mode</p>
            <p class="font-semibold text-slate-800 uppercase tracking-wider text-[11px]">{{ $import->duplicate_mode ?? '-' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200">
            <h4 class="font-syne font-semibold text-slate-800">Errors</h4>
        </div>
        @php
            $errs = $import->errors ?? [];
        @endphp
        @if(empty($errs))
            <div class="p-4 text-sm text-slate-600">No errors.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Row</th>
                            <th class="px-6 py-3 font-semibold">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($errs as $e)
                            <tr class="bg-red-50">
                                <td class="px-6 py-3 font-semibold text-red-800">{{ $e['row'] ?? '-' }}</td>
                                <td class="px-6 py-3 text-red-700 whitespace-normal">{{ $e['message'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

