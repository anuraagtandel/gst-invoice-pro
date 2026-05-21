@extends('layouts.app')

@section('title', 'Customer Import Preview')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Customer Import Preview</h3>
            <p class="text-sm text-slate-500">Review rows before importing. Rows with errors will not be imported. Rows with warnings will be imported.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <form method="POST" action="{{ route('app.customers.upload.commit') }}" class="w-full sm:w-auto">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="mb-2">
                    <div class="text-xs font-semibold text-slate-700 mb-1">Do you want to update existing records or skip duplicates?</div>
                    <div class="flex items-center gap-4 text-sm text-slate-700">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="duplicate_mode" value="skip" checked>
                            <span>Skip duplicates</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="duplicate_mode" value="update">
                            <span>Update existing</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">
                    Import Valid Rows Only
                </button>
            </form>
            <form method="POST" action="{{ route('app.customers.upload.cancel') }}" class="w-full sm:w-auto">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <button type="submit" class="w-full sm:w-auto bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold transition-colors border border-slate-200 shadow-sm">
                    Cancel Upload
                </button>
            </form>
            @if(($summary['skipped'] ?? 0) > 0)
                <a href="{{ route('app.customers.upload.errors', $token) }}" class="w-full sm:w-auto bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold transition-colors border border-slate-200 shadow-sm">
                    Download Error Excel File
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-500 mb-1">Will Create</p>
            <p class="font-syne font-bold text-xl text-slate-800">{{ $summary['created'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-500 mb-1">Will Update</p>
            <p class="font-syne font-bold text-xl text-slate-800">{{ $summary['updated'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-500 mb-1">Invalid / Skipped</p>
            <p class="font-syne font-bold text-xl text-red-700">{{ $summary['skipped'] ?? 0 }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Row</th>
                        <th class="px-4 py-3 font-semibold">Action</th>
                        <th class="px-4 py-3 font-semibold">Business Name</th>
                        <th class="px-4 py-3 font-semibold">Mobile</th>
                        <th class="px-4 py-3 font-semibold">Code</th>
                        <th class="px-4 py-3 font-semibold">GSTIN</th>
                        <th class="px-4 py-3 font-semibold">GST Status</th>
                        <th class="px-4 py-3 font-semibold">Address</th>
                        <th class="px-4 py-3 font-semibold">City</th>
                        <th class="px-4 py-3 font-semibold">Area</th>
                        <th class="px-4 py-3 font-semibold">State</th>
                        <th class="px-4 py-3 font-semibold">POS</th>
                        <th class="px-4 py-3 font-semibold">FSSAI</th>
                        <th class="px-4 py-3 font-semibold">Errors</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($rows as $r)
                        @php
                            $hasErrors = !empty($r['errors']);
                            $hasWarnings = !empty($r['warnings']);
                            $d = $r['data'] ?? [];
                            $isInvalidGst = ($d['gst_status'] ?? '') === 'Invalid GST';
                        @endphp
                        <tr class="{{ $hasErrors ? 'bg-red-50' : ($isInvalidGst ? 'bg-amber-50' : 'bg-white') }}">
                            <td class="px-4 py-3 font-medium text-slate-700">{{ $r['row'] }}</td>
                            <td class="px-4 py-3">
                                @if($hasErrors)
                                    <span class="text-xs font-bold text-red-700">SKIP</span>
                                @elseif($isInvalidGst)
                                    <span class="text-xs font-bold text-amber-700">{{ $r['action'] }}</span>
                                @else
                                    <span class="text-xs font-bold text-slate-700">{{ $r['action'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-800">{{ $d['name'] ?? '' }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $d['mobile'] ?? '' }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $d['code'] ?? '' }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $d['gstin'] ?? '' }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $d['gst_status'] ?? '' }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $d['address'] ?? '' }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $d['city'] ?? '' }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $d['area_name'] ?? '' }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $d['state'] ?? '' }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $d['pos_code'] ?? '' }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $d['fssai_no'] ?? '' }}</td>
                            <td class="px-4 py-3">
                                @if($hasErrors)
                                    <div class="text-xs text-red-700 font-semibold whitespace-normal max-w-[320px]">
                                        {{ implode(' ', $r['errors']) }}
                                    </div>
                                @elseif($hasWarnings)
                                    <div class="text-xs text-amber-700 font-semibold whitespace-normal max-w-[320px]">
                                        {{ implode(' ', $r['warnings']) }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
