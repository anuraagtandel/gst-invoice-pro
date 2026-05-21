@extends('layouts.app')

@section('title', 'Deleted Invoices')

@section('content')
<div class="space-y-6">

    <div class="flex justify-between items-center bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Trash / Soft Deleted</h3>
            <p class="text-sm text-slate-500">Restore invoices or permanently delete them.</p>
        </div>
        <a href="{{ route('app.invoices.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
            <i class="ph ph-arrow-left text-lg"></i> Back to Active
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($invoices->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-trash', 'title' => 'Trash is empty', 'message' => 'No deleted invoices found.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Invoice No</th>
                            <th class="px-6 py-3 font-semibold">Deleted At</th>
                            <th class="px-6 py-3 font-semibold">Customer</th>
                            <th class="px-6 py-3 font-semibold text-right">Total Amount</th>
                            <th class="px-6 py-3 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($invoices as $invoice)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-500 line-through">{{ $invoice->invoice_no }}</td>
                            <td class="px-6 py-4 text-red-600 font-medium">{{ $invoice->deleted_at ? $invoice->deleted_at->format('d-m-Y h:i A') : '-' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $invoice->customer->name ?? 'Unknown' }}</td>
                            <td class="px-6 py-4 text-right text-slate-600">₹{{ number_format($invoice->grand_total, 2) }}</td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <form action="{{ route('app.invoices.restore', $invoice->id) }}" method="POST" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-3 py-1 rounded transition-colors">
                                            Restore
                                        </button>
                                    </form>
                                    <form action="{{ route('app.invoices.forceDelete', $invoice->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to PERMANENTLY delete this invoice? This action cannot be undone.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-1 rounded transition-colors">
                                            Permanent Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-200">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
