@extends('layouts.app')

@section('title', 'Customer Import History')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Customer Import History</h3>
            <p class="text-sm text-slate-500">Track previous uploads and results.</p>
        </div>
        <a href="{{ route('app.customers.index') }}" class="bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold transition-colors border border-slate-200 shadow-sm">
            Back to Customers
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($imports->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-clock-counter-clockwise', 'title' => 'No imports yet', 'message' => 'Upload a customer Excel/CSV to start tracking history.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Date</th>
                            <th class="px-6 py-3 font-semibold">File Name</th>
                            <th class="px-6 py-3 font-semibold text-right">Imported</th>
                            <th class="px-6 py-3 font-semibold text-right">Failed</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($imports as $imp)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 text-slate-600">{{ optional($imp->uploaded_at)->format('d M, Y H:i') }}</td>
                                <td class="px-6 py-4 font-medium text-slate-800">{{ $imp->file_name }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-800">{{ $imp->imported_count }}</td>
                                <td class="px-6 py-4 text-right font-semibold {{ $imp->failed_count > 0 ? 'text-red-700' : 'text-slate-800' }}">{{ $imp->failed_count }}</td>
                                <td class="px-6 py-4 text-slate-600 uppercase tracking-wider text-[11px] font-bold">{{ $imp->status }}</td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('app.customers.imports.show', $imp->token) }}" class="text-indigo-700 hover:text-indigo-900 font-semibold text-sm">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $imports->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

