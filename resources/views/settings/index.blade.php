@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="w-full space-y-6">

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center gap-3">
            <i class="ph ph-check-circle text-lg"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
            <div class="flex items-center gap-3">
                <i class="ph ph-warning-circle text-lg"></i>
                <span class="text-sm font-medium">Please fix the errors and try again.</span>
            </div>
            <div class="mt-2 text-sm">
                <ul class="list-disc pl-6 space-y-1">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="mb-6">
            <h3 class="font-syne font-semibold text-xl text-slate-800">Firm Profile</h3>
            <p class="text-sm text-slate-500">Update your company details. These will appear on printed invoices.</p>
        </div>

        <form action="{{ route('app.settings.update') }}" method="POST">
            @csrf
            
            <div class="space-y-6">
                <!-- Section 1 -->
                <div>
                    <h4 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-4">Basic Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1 md:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Firm Name</label>
                            <input type="text" name="firm_name" value="{{ $settings['firm_name'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1 md:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Complete Address</label>
                            <textarea name="firm_address" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">{{ $settings['firm_address'] ?? '' }}</textarea>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Phone Number</label>
                            <input type="text" name="firm_phone" value="{{ $settings['firm_phone'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Email Address</label>
                            <input type="email" name="firm_email" value="{{ $settings['firm_email'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>
                </div>

                <!-- Section 2 -->
                <div>
                    <h4 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-4">Registration Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">GSTIN</label>
                            <input type="text" name="firm_gstin" value="{{ $settings['firm_gstin'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">State Code</label>
                            <input type="text" name="firm_state_code" value="{{ $settings['firm_state_code'] ?? '' }}" inputmode="numeric" maxlength="2" pattern="[0-9]{2}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">State</label>
                            <input type="text" name="firm_state" value="{{ $settings['firm_state'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">FSSAI License No</label>
                            <input type="text" name="firm_fssai" value="{{ $settings['firm_fssai'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>
                </div>

                <!-- Section 3 -->
                <div>
                    <h4 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-4">Billing Configuration</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Invoice Prefix</label>
                            <input type="text" name="invoice_prefix" value="{{ $settings['invoice_prefix'] ?? 'SD' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Financial Year</label>
                            <input type="text" name="financial_year" value="{{ $settings['financial_year'] ?? '25-26' }}" placeholder="e.g. 25-26" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Next Invoice Seq.</label>
                            <input type="number" name="next_invoice_seq" value="{{ $settings['next_invoice_seq'] ?? 1 }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1 md:col-span-3">
                            <label class="text-xs font-medium text-slate-700">Default Salesman Name</label>
                            <input type="text" name="default_salesman" value="{{ $settings['default_salesman'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-4">Credit Control</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Credit Limit Enforcement</label>
                            <select name="credit_limit_policy" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                                <option value="warn" @selected(($settings['credit_limit_policy'] ?? 'warn') === 'warn')>Warn Only</option>
                                <option value="block" @selected(($settings['credit_limit_policy'] ?? '') === 'block')>Block Invoice</option>
                            </select>
                            <p class="text-[11px] text-slate-500">Applies when projected outstanding exceeds the customer credit limit.</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Customer Excel Import UI</label>
                            <select name="enable_customer_import" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                                <option value="0" @selected(($settings['enable_customer_import'] ?? '1') === '0')>Disabled</option>
                                <option value="1" @selected(($settings['enable_customer_import'] ?? '') === '1')>Enabled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-4">Bank Details (Invoice)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Bank Name</label>
                            <input type="text" name="bank_name" value="{{ $settings['bank_name'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Account Name</label>
                            <input type="text" name="bank_account_name" value="{{ $settings['bank_account_name'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Account Number</label>
                            <input type="text" name="bank_account_number" value="{{ $settings['bank_account_number'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">IFSC Code</label>
                            <input type="text" name="bank_ifsc" value="{{ $settings['bank_ifsc'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1 md:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Branch</label>
                            <input type="text" name="bank_branch" value="{{ $settings['bank_branch'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-4">GST / e-Invoice Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Legal Name</label>
                            <input type="text" name="gst_legal_name" value="{{ $settings['gst_legal_name'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Trade Name</label>
                            <input type="text" name="gst_trade_name" value="{{ $settings['gst_trade_name'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">GSTIN</label>
                            <input type="text" name="gst_gstin" value="{{ $settings['gst_gstin'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">PAN Number</label>
                            <input type="text" name="gst_pan" value="{{ $settings['gst_pan'] ?? '' }}" maxlength="10" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>

                        <div class="space-y-1 md:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Address Line 1</label>
                            <input type="text" name="gst_address_1" value="{{ $settings['gst_address_1'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1 md:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Address Line 2</label>
                            <input type="text" name="gst_address_2" value="{{ $settings['gst_address_2'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">City</label>
                            <input type="text" name="gst_city" value="{{ $settings['gst_city'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">District</label>
                            <input type="text" name="gst_district" value="{{ $settings['gst_district'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">State</label>
                            <input type="text" name="gst_state" value="{{ $settings['gst_state'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">State Code</label>
                            <input type="text" name="gst_state_code" value="{{ $settings['gst_state_code'] ?? '' }}" inputmode="numeric" maxlength="2" pattern="[0-9]{2}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">PIN Code</label>
                            <input type="text" name="gst_pin_code" value="{{ $settings['gst_pin_code'] ?? '' }}" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Country</label>
                            <input type="text" name="gst_country" value="{{ $settings['gst_country'] ?? 'INDIA' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Phone Number</label>
                            <input type="text" name="gst_phone" value="{{ $settings['gst_phone'] ?? '' }}" inputmode="numeric" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Email Address</label>
                            <input type="email" name="gst_email" value="{{ $settings['gst_email'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div class="mt-6">
                        <h5 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-4">Dispatch / Transport (Optional)</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">Dispatch From Name</label>
                                <input type="text" name="dispatch_from_name" value="{{ $settings['dispatch_from_name'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">Dispatch From Address</label>
                                <input type="text" name="dispatch_from_address" value="{{ $settings['dispatch_from_address'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">Dispatch From City</label>
                                <input type="text" name="dispatch_from_city" value="{{ $settings['dispatch_from_city'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">Dispatch From State</label>
                                <input type="text" name="dispatch_from_state" value="{{ $settings['dispatch_from_state'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">Dispatch From State Code</label>
                                <input type="text" name="dispatch_from_state_code" value="{{ $settings['dispatch_from_state_code'] ?? '' }}" inputmode="numeric" maxlength="2" pattern="[0-9]{2}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">Dispatch From PIN Code</label>
                                <input type="text" name="dispatch_from_pin_code" value="{{ $settings['dispatch_from_pin_code'] ?? '' }}" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-8 pt-6 border-t border-slate-200 flex justify-end">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-sm">
                    <i class="ph ph-floppy-disk text-lg"></i> Save Settings
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="mb-6">
            <h3 class="font-syne font-semibold text-xl text-slate-800">Invoice QR</h3>
            <p class="text-sm text-slate-500">Upload the QR image used in invoice print/PDF.</p>
        </div>

        <form action="{{ route('app.settings.invoice_qr.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="space-y-3">
                <div class="space-y-2">
                    <label class="text-xs font-medium text-slate-700">QR Image (PNG/JPG)</label>
                    <input id="invoiceQrInput" type="file" name="invoice_qr_image" accept=".png,.jpg,.jpeg" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <div id="invoiceQrSelected" class="text-[11px] text-slate-500 hidden"></div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-medium text-slate-700">Preview</label>
                    <div class="flex items-center gap-3">
                        <div class="w-24 h-24 border border-slate-200 rounded-lg bg-white flex items-center justify-center overflow-hidden">
                            <img id="invoiceQrPreview" src="{{ route('app.settings.invoice_qr.preview') }}?v={{ urlencode((string)($settings['invoice_qr_path'] ?? '')) }}" alt="QR" class="w-full h-full object-contain">
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Current file: <span class="font-semibold">{{ $settings['invoice_qr_path'] ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button id="invoiceQrUploadBtn" type="submit" class="bg-slate-900 hover:bg-slate-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2 shadow-sm" disabled>
                    <i class="ph ph-upload-simple text-lg"></i> Upload QR
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="mb-6">
            <h3 class="font-syne font-semibold text-xl text-slate-800">Update Password</h3>
            <p class="text-sm text-slate-500">Ensure your account is using a long, random password to stay secure.</p>
        </div>

        <form method="post" action="{{ route('password.update') }}" class="space-y-6">
            @csrf
            @method('put')

            <div class="space-y-1">
                <label class="text-xs font-medium text-slate-700">Current Password</label>
                <div data-password-field class="relative">
                    <input type="password" name="current_password" class="w-full px-3 py-2 pr-10 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <button type="button" data-password-toggle aria-label="Show password" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600">
                        <i class="ph ph-eye text-lg"></i>
                    </button>
                </div>
                @error('current_password', 'updatePassword')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="text-xs font-medium text-slate-700">New Password</label>
                <div data-password-field class="relative">
                    <input type="password" name="password" class="w-full px-3 py-2 pr-10 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <button type="button" data-password-toggle aria-label="Show password" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600">
                        <i class="ph ph-eye text-lg"></i>
                    </button>
                </div>
                @error('password', 'updatePassword')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="text-xs font-medium text-slate-700">Confirm Password</label>
                <div data-password-field class="relative">
                    <input type="password" name="password_confirmation" class="w-full px-3 py-2 pr-10 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <button type="button" data-password-toggle aria-label="Show password" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600">
                        <i class="ph ph-eye text-lg"></i>
                    </button>
                </div>
                @error('password_confirmation', 'updatePassword')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors shadow-sm">
                    Save Password
                </button>

                @if (session('status') === 'password-updated')
                    <p
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 2000)"
                        class="text-sm text-emerald-600 font-medium"
                    >Saved.</p>
                @endif
            </div>
        </form>
    </div>


@push('scripts')
<script>
    (function () {
        const input = document.getElementById('invoiceQrInput');
        const btn = document.getElementById('invoiceQrUploadBtn');
        const sel = document.getElementById('invoiceQrSelected');
        const img = document.getElementById('invoiceQrPreview');
        if (!input || !btn || !sel || !img) return;

        input.addEventListener('change', () => {
            const f = input.files && input.files[0] ? input.files[0] : null;
            btn.disabled = !f;
            if (!f) {
                sel.classList.add('hidden');
                sel.textContent = '';
                return;
            }
            sel.classList.remove('hidden');
            sel.textContent = `Selected: ${f.name}`;
            const url = URL.createObjectURL(f);
            img.src = url;
        });
    })();
</script>
@endpush
</div>
@endsection
