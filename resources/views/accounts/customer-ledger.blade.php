@extends('layouts.app')

@section('title', 'Customer Accounts')

@section('content')
<div x-data="customerAccounts()" x-init="init()" class="space-y-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-lg font-syne font-semibold text-slate-900">Customer Accounts</div>
                <div class="text-xs text-slate-500">Outstanding, collections, and customer-wise ledger access.</div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 text-slate-500 bg-slate-50 cursor-not-allowed" disabled>
                    <span class="inline-flex items-center gap-2"><i class="ph ph-download-simple text-sm"></i>Export</span>
                </button>
                <button type="button" @click="refreshAll()" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                    <span class="inline-flex items-center gap-2"><i class="ph ph-arrow-clockwise text-sm"></i>Refresh</span>
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3">
        <div class="grid grid-cols-12 gap-3 items-end">
            <div class="col-span-4">
                <label class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Search Customer / Invoice</label>
                <div class="relative">
                    <i class="ph ph-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" x-model="filters.q" @keydown.enter.prevent="applyFilters()" placeholder="Name / Code / Mobile / Invoice No" class="w-full pl-8 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <div class="col-span-2">
                <label class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Status</label>
                <select x-model="filters.status" class="w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="all">All</option>
                    <option value="outstanding">Outstanding</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>

            <div class="col-span-2">
                <label class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Date From</label>
                <input type="date" x-model="filters.from_date" class="w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <div class="col-span-2">
                <label class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Date To</label>
                <input type="date" x-model="filters.to_date" class="w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <div class="col-span-1">
                <label class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Aging</label>
                <select x-model="filters.aging" class="w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="all">All</option>
                    <option value="0_7">0–7</option>
                    <option value="8_15">8–15</option>
                    <option value="16_30">16–30</option>
                    <option value="30_plus">30+</option>
                </select>
            </div>

            <div class="col-span-1">
                <label class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Payment</label>
                <select x-model="filters.payment_status" class="w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="all">All</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                </select>
            </div>

            <div class="col-span-12 flex items-center justify-end gap-2 pt-1">
                <button type="button" @click="applyFilters()" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Apply Filters</button>
                <button type="button" @click="resetFilters()" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">Reset</button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Total Outstanding</div>
            <div class="mt-1 text-lg font-bold text-slate-900" x-text="formatRupee6(summary.total_outstanding)"></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Total Overdue</div>
            <div class="mt-1 text-lg font-bold text-slate-900" x-text="formatRupee6(summary.total_overdue)"></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Collection Received</div>
            <div class="mt-1 text-lg font-bold text-slate-900" x-text="formatRupee6(summary.collection_received)"></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">This Month Pending</div>
            <div class="mt-1 text-lg font-bold text-slate-900" x-text="formatRupee6(summary.this_month_pending)"></div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 pt-3 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <button type="button" @click="setTab('customers')" class="pb-2 text-sm font-semibold border-b-2" :class="tab === 'customers' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-600 hover:text-slate-900'">Customer Wise</button>
                <button type="button" @click="setTab('invoices')" class="pb-2 text-sm font-semibold border-b-2" :class="tab === 'invoices' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-600 hover:text-slate-900'">Invoice Wise</button>
                <button type="button" @click="setTab('aging')" class="pb-2 text-sm font-semibold border-b-2" :class="tab === 'aging' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-600 hover:text-slate-900'">Aging</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <div class="max-h-[70vh] overflow-y-auto">
                <table x-show="tab === 'customers'" class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-2 text-xs font-semibold">Customer Name</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right">Outstanding</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right">Overdue</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right">Credit Limit</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right">Available Credit</th>
                            <th class="px-4 py-2 text-xs font-semibold">Last Payment Date</th>
                            <th class="px-4 py-2 text-xs font-semibold">Status</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="c in customers.rows" :key="c.id">
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-2">
                                    <div class="font-semibold text-slate-900" x-text="c.name"></div>
                                    <div class="text-[11px] text-slate-500">
                                        <span x-text="c.code || '-'"></span>
                                        <span class="text-slate-300">•</span>
                                        <span x-text="c.mobile || '-'"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-right font-semibold" :class="isOverLimit(c) ? 'text-red-700' : amountTone(c.outstanding_amount)">
                                    <span x-text="formatRupee6(c.outstanding_amount)"></span>
                                </td>
                                <td class="px-4 py-2 text-right font-semibold" :class="amountTone(c.overdue_amount)">
                                    <span x-text="formatRupee6(c.overdue_amount)"></span>
                                </td>
                                <td class="px-4 py-2 text-right text-slate-700">
                                    <span x-text="creditLimitText(c)"></span>
                                </td>
                                <td class="px-4 py-2 text-right" :class="availableCreditClass(c)">
                                    <span x-text="availableCreditText(c)"></span>
                                </td>
                                <td class="px-4 py-2 text-slate-700" x-text="c.last_payment_date || '-'"></td>
                                <td class="px-4 py-2">
                                    <div class="inline-flex items-center gap-1.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold" :class="statusChipClass(c)" x-text="statusChipText(c)"></span>
                                        <span x-show="isOverLimit(c)" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">Over Limit</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" @click="openDrawer(c)" class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">View</button>
                                        <a :href="`/app/payments/receive?customer_id=${c.id}`" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Receive Payment</a>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="customers.loading && customers.rows.length === 0">
                            <td colspan="8" class="px-4 py-6 text-slate-500 text-center">Loading...</td>
                        </tr>
                        <tr x-show="!customers.loading && customers.rows.length === 0">
                            <td colspan="8" class="px-4 py-6 text-slate-500 text-center">No records found</td>
                        </tr>
                    </tbody>
                </table>

                <table x-show="tab === 'invoices'" class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-2 text-xs font-semibold">Invoice No</th>
                            <th class="px-4 py-2 text-xs font-semibold">Customer</th>
                            <th class="px-4 py-2 text-xs font-semibold cursor-pointer select-none" @click="setInvoiceSort('invoice_date')">Invoice Date</th>
                            <th class="px-4 py-2 text-xs font-semibold">Due Date</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right">Total</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right">Paid</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right cursor-pointer select-none" @click="setInvoiceSort('pending_amount')">Pending</th>
                            <th class="px-4 py-2 text-xs font-semibold">Status</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right cursor-pointer select-none" @click="setInvoiceSort('due_days')">Due Days</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="inv in displayedInvoices()" :key="inv.id">
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-2 font-semibold text-slate-900" x-text="inv.invoice_no"></td>
                                <td class="px-4 py-2 text-slate-700" x-text="inv.customer_name"></td>
                                <td class="px-4 py-2 text-slate-700" x-text="inv.invoice_date || '-'"></td>
                                <td class="px-4 py-2 text-slate-700" x-text="inv.due_date || '-'"></td>
                                <td class="px-4 py-2 text-right text-slate-700" x-text="formatRupee6(inv.total_amount)"></td>
                                <td class="px-4 py-2 text-right text-slate-700" x-text="formatRupee6(inv.paid_amount)"></td>
                                <td class="px-4 py-2 text-right font-semibold" :class="amountTone(inv.pending_amount)" x-text="formatRupee6(inv.pending_amount)"></td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold" :class="invoiceStatusChipClass(inv.payment_status)" x-text="inv.payment_status"></span>
                                </td>
                                <td class="px-4 py-2 text-right text-slate-700" x-text="inv.due_days"></td>
                                <td class="px-4 py-2 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" @click="openDrawerByCustomerId(inv.customer_id, inv.customer_name)" class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">View Customer</button>
                                        <button type="button" @click="openDrawerAndReceivePayment(inv.customer_id, inv.customer_name)" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Receive Payment</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="invoices.loading && invoices.rows.length === 0">
                            <td colspan="10" class="px-4 py-6 text-slate-500 text-center">Loading...</td>
                        </tr>
                        <tr x-show="!invoices.loading && invoices.rows.length === 0">
                            <td colspan="10" class="px-4 py-6 text-slate-500 text-center">No records found</td>
                        </tr>
                    </tbody>
                </table>

                <table x-show="tab === 'aging'" class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-2 text-xs font-semibold">Customer</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right">0–7</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right">8–15</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right">16–30</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right">30+</th>
                            <th class="px-4 py-2 text-xs font-semibold text-right">Total Pending</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="r in aging.rows" :key="r.id">
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-2">
                                    <div class="font-semibold text-slate-900" x-text="r.name"></div>
                                    <div class="text-[11px] text-slate-500">
                                        <span x-text="r.code || '-'"></span>
                                        <span class="text-slate-300">•</span>
                                        <span x-text="r.mobile || '-'"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-right text-slate-700" x-text="formatRupee6(r.b_0_7)"></td>
                                <td class="px-4 py-2 text-right text-slate-700" x-text="formatRupee6(r.b_8_15)"></td>
                                <td class="px-4 py-2 text-right text-slate-700" x-text="formatRupee6(r.b_16_30)"></td>
                                <td class="px-4 py-2 text-right font-semibold" :class="money6(r.b_30_plus) > 0 ? 'text-red-700' : 'text-slate-700'" x-text="formatRupee6(r.b_30_plus)"></td>
                                <td class="px-4 py-2 text-right font-semibold" :class="amountTone(r.total_pending)" x-text="formatRupee6(r.total_pending)"></td>
                            </tr>
                        </template>
                        <tr x-show="!aging.loading && aging.rows.length > 0" class="bg-slate-50">
                            <td class="px-4 py-2 text-xs font-semibold text-slate-700">Totals</td>
                            <td class="px-4 py-2 text-right text-xs font-semibold text-slate-700" x-text="formatRupee6(agingTotals().b_0_7)"></td>
                            <td class="px-4 py-2 text-right text-xs font-semibold text-slate-700" x-text="formatRupee6(agingTotals().b_8_15)"></td>
                            <td class="px-4 py-2 text-right text-xs font-semibold text-slate-700" x-text="formatRupee6(agingTotals().b_16_30)"></td>
                            <td class="px-4 py-2 text-right text-xs font-semibold" :class="agingTotals().b_30_plus > 0 ? 'text-red-700' : 'text-slate-700'" x-text="formatRupee6(agingTotals().b_30_plus)"></td>
                            <td class="px-4 py-2 text-right text-xs font-semibold text-slate-900" x-text="formatRupee6(agingTotals().total_pending)"></td>
                        </tr>
                        <tr x-show="aging.loading && aging.rows.length === 0">
                            <td colspan="6" class="px-4 py-6 text-slate-500 text-center">Loading...</td>
                        </tr>
                        <tr x-show="!aging.loading && aging.rows.length === 0">
                            <td colspan="6" class="px-4 py-6 text-slate-500 text-center">No records found</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="px-4 py-3 border-t border-slate-200 bg-white">
            <div class="flex items-center justify-between">
                <div class="text-xs text-slate-500" x-text="paginationText()"></div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="prevPage()" :disabled="isFirstPage()" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50">Prev</button>
                    <button type="button" @click="nextPage()" :disabled="!hasMore()" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div x-show="drawer.open" x-cloak class="fixed inset-0 z-[80]" @keydown.escape.window="closeDrawer()">
    <div class="absolute inset-0 bg-slate-900/30" @click="closeDrawer()"></div>

    <div class="absolute top-0 right-0 h-full bg-white border-l border-slate-200 shadow-2xl w-full md:w-[52vw] md:min-w-[720px] md:max-w-[980px] flex flex-col">
        <div class="px-4 py-3 border-b border-slate-200">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="text-base font-syne font-semibold text-slate-900 truncate" x-text="drawer.customer?.name || drawer.customerBrief?.name || 'Customer'"></div>
                        <span x-show="drawer.metrics?.over_limit" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">Over Credit Limit</span>
                    </div>
                    <div class="mt-1 text-xs text-slate-600 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span><span class="font-semibold text-slate-700">Phone:</span> <span x-text="drawer.customer?.mobile || drawer.customerBrief?.mobile || '-'"></span></span>
                        <span><span class="font-semibold text-slate-700">GST:</span> <span x-text="drawer.customer?.gstin || '-'"></span></span>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" @click="openReceivePaymentPanel()" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Receive Payment</button>
                    <a :href="drawer.customer?.id ? `/app/accounts/customer-ledger/${drawer.customer.id}` : '#'" target="_blank" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">View Full Statement</a>
                    <button type="button" @click="closeDrawer()" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">Close</button>
                </div>
            </div>

            <div class="mt-3 grid grid-cols-4 gap-3">
                <div class="rounded-lg border border-slate-200 px-3 py-2">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Credit Limit</div>
                    <div class="mt-0.5 text-sm font-semibold text-slate-900" x-text="drawer.customer ? creditLimitText(drawer.customer) : '-'"></div>
                </div>
                <div class="rounded-lg border border-slate-200 px-3 py-2">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Outstanding</div>
                    <div class="mt-0.5 text-sm font-semibold" :class="drawer.metrics?.over_limit ? 'text-red-700' : amountTone(drawer.metrics?.outstanding)" x-text="drawer.metrics ? formatRupee6(drawer.metrics.outstanding) : '-'"></div>
                </div>
                <div class="rounded-lg border border-slate-200 px-3 py-2">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Available Credit</div>
                    <div class="mt-0.5 text-sm font-semibold" :class="drawerAvailableCreditClass()" x-text="drawerAvailableCreditText()"></div>
                </div>
                <div class="rounded-lg border border-slate-200 px-3 py-2">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Last Payment</div>
                    <div class="mt-0.5 text-sm font-semibold text-slate-900" x-text="drawer.metrics?.last_payment_date || '-'"></div>
                </div>
            </div>

            <div class="mt-3 grid grid-cols-4 gap-3">
                <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Total Outstanding</div>
                    <div class="mt-0.5 text-sm font-semibold text-slate-900" x-text="drawer.metrics ? formatRupee6(drawer.metrics.outstanding) : '-'"></div>
                </div>
                <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Total Overdue</div>
                    <div class="mt-0.5 text-sm font-semibold text-slate-900" x-text="drawer.metrics ? formatRupee6(drawer.metrics.overdue) : '-'"></div>
                </div>
                <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">This Month Sales</div>
                    <div class="mt-0.5 text-sm font-semibold text-slate-900" x-text="drawer.metrics ? formatRupee6(drawer.metrics.this_month_sales) : '-'"></div>
                </div>
                <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">This Month Collection</div>
                    <div class="mt-0.5 text-sm font-semibold text-slate-900" x-text="drawer.metrics ? formatRupee6(drawer.metrics.this_month_collection) : '-'"></div>
                </div>
            </div>
        </div>

        <div class="px-4 py-2 border-b border-slate-200 bg-white">
            <div class="grid grid-cols-4 gap-2">
                <div class="rounded-lg border border-slate-200 px-3 py-2">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Aging 0–7</div>
                    <div class="mt-0.5 text-sm font-semibold text-slate-900" x-text="drawer.metrics ? formatRupee6(drawer.metrics.aging?.b_0_7) : '-'"></div>
                </div>
                <div class="rounded-lg border border-slate-200 px-3 py-2">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Aging 8–15</div>
                    <div class="mt-0.5 text-sm font-semibold text-slate-900" x-text="drawer.metrics ? formatRupee6(drawer.metrics.aging?.b_8_15) : '-'"></div>
                </div>
                <div class="rounded-lg border border-slate-200 px-3 py-2">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Aging 16–30</div>
                    <div class="mt-0.5 text-sm font-semibold text-slate-900" x-text="drawer.metrics ? formatRupee6(drawer.metrics.aging?.b_16_30) : '-'"></div>
                </div>
                <div class="rounded-lg border border-slate-200 px-3 py-2">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Aging 30+</div>
                    <div class="mt-0.5 text-sm font-semibold text-slate-900" x-text="drawer.metrics ? formatRupee6(drawer.metrics.aging?.b_30_plus) : '-'"></div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto">
            <div class="px-4 py-3">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-slate-900">Ledger</div>
                    <div class="text-[11px] text-slate-500" x-show="drawer.ledger?.truncated">
                        Showing last <span x-text="drawer.ledger?.limit"></span> of <span x-text="drawer.ledger?.total_entries"></span> entries
                    </div>
                </div>
            </div>

            <div class="px-4 pb-4">
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <div class="max-h-[62vh] overflow-y-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 text-xs font-semibold">Date</th>
                                    <th class="px-3 py-2 text-xs font-semibold">Type</th>
                                    <th class="px-3 py-2 text-xs font-semibold">Reference</th>
                                    <th class="px-3 py-2 text-xs font-semibold text-right">Debit</th>
                                    <th class="px-3 py-2 text-xs font-semibold text-right">Credit</th>
                                    <th class="px-3 py-2 text-xs font-semibold text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-if="drawer.loading">
                                    <template>
                                        <template x-for="i in 10" :key="i">
                                            <tr>
                                                <td class="px-3 py-2"><div class="h-3 w-20 bg-slate-100 rounded"></div></td>
                                                <td class="px-3 py-2"><div class="h-3 w-24 bg-slate-100 rounded"></div></td>
                                                <td class="px-3 py-2"><div class="h-3 w-64 bg-slate-100 rounded"></div></td>
                                                <td class="px-3 py-2 text-right"><div class="h-3 w-20 bg-slate-100 rounded ml-auto"></div></td>
                                                <td class="px-3 py-2 text-right"><div class="h-3 w-20 bg-slate-100 rounded ml-auto"></div></td>
                                                <td class="px-3 py-2 text-right"><div class="h-3 w-20 bg-slate-100 rounded ml-auto"></div></td>
                                            </tr>
                                        </template>
                                    </template>
                                </template>

                                <template x-if="!drawer.loading && (drawer.ledger?.entries || []).length === 0">
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-slate-500">No ledger entries</td>
                                    </tr>
                                </template>

                                <template x-for="e in (drawer.ledger?.entries || [])" :key="`${e.date}-${e.type}-${e.ref}-${e.debit}-${e.credit}`">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-3 py-2 text-slate-700" x-text="e.date"></td>
                                        <td class="px-3 py-2">
                                            <div class="inline-flex items-center gap-1.5">
                                                <span class="font-semibold text-slate-900" x-text="e.type"></span>
                                                <span x-show="e.type === 'Invoice' && e.invoice_status" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold" :class="invoiceStatusChipClass(e.invoice_status)" x-text="e.invoice_status"></span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-slate-700" x-text="e.ref"></td>
                                        <td class="px-3 py-2 text-right font-semibold text-slate-900" x-text="e.debit > 0 ? formatRupee6(e.debit) : '-'"></td>
                                        <td class="px-3 py-2 text-right font-semibold text-emerald-700" x-text="e.credit > 0 ? formatRupee6(e.credit) : '-'"></td>
                                        <td class="px-3 py-2 text-right font-semibold text-slate-900" x-text="formatRupee6(e.balance)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="receivePaymentPanelOpen" x-cloak class="absolute top-0 right-0 h-full w-full bg-white border-l border-slate-200 shadow-2xl flex flex-col">
            <div class="px-4 py-3 border-b border-slate-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-slate-900">Receive Payment</div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="savePayment()" :disabled="payment.saving || !canSavePayment()" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed">
                            <span x-show="!payment.saving">Save Payment</span>
                            <span x-show="payment.saving">Saving...</span>
                        </button>
                        <button type="button" @click="closeReceivePaymentPanel()" :disabled="payment.saving" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-60 disabled:cursor-not-allowed">Cancel</button>
                    </div>
                </div>
            </div>

            <div class="p-4 space-y-3 overflow-y-auto">
                <div class="grid grid-cols-12 gap-3 items-end">
                    <div class="col-span-3">
                        <label class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Payment Date</label>
                        <input type="date" x-model="payment.form.payment_date" class="w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="col-span-4">
                        <label class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Payment Mode</label>
                        <div class="inline-flex w-full rounded-lg border border-slate-200 overflow-hidden">
                            <button type="button" @click="payment.form.payment_mode='Cash'" class="flex-1 px-3 py-2 text-xs font-semibold" :class="payment.form.payment_mode==='Cash' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-50'">Cash</button>
                            <button type="button" @click="payment.form.payment_mode='Bank'" class="flex-1 px-3 py-2 text-xs font-semibold border-l border-slate-200" :class="payment.form.payment_mode==='Bank' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-50'">Bank</button>
                            <button type="button" @click="payment.form.payment_mode='UPI'" class="flex-1 px-3 py-2 text-xs font-semibold border-l border-slate-200" :class="payment.form.payment_mode==='UPI' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-50'">UPI</button>
                            <button type="button" @click="payment.form.payment_mode='Cheque'" class="flex-1 px-3 py-2 text-xs font-semibold border-l border-slate-200" :class="payment.form.payment_mode==='Cheque' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-50'">Cheque</button>
                        </div>
                    </div>

                    <div class="col-span-3">
                        <label class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Reference No</label>
                        <input type="text" x-model="payment.form.reference_no" class="w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="col-span-2">
                        <label class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Amount</label>
                        <input type="number" step="0.000001" min="0" x-model="payment.form.amount" @input="onPaymentAmountInput()" class="w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-right">
                    </div>

                    <div class="col-span-12">
                        <label class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Notes</label>
                        <input type="text" x-model="payment.form.notes" class="w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="grid grid-cols-4 gap-3">
                        <div>
                            <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Total Pending</div>
                            <div class="mt-0.5 text-sm font-semibold text-slate-900" x-text="formatRupee6(paymentTotals().total_pending)"></div>
                        </div>
                        <div>
                            <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Amount Entered</div>
                            <div class="mt-0.5 text-sm font-semibold text-slate-900" x-text="formatRupee6(paymentTotals().amount_entered)"></div>
                        </div>
                        <div>
                            <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Allocated</div>
                            <div class="mt-0.5 text-sm font-semibold text-slate-900" x-text="formatRupee6(paymentTotals().allocated)"></div>
                        </div>
                        <div>
                            <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Remaining Unallocated</div>
                            <div class="mt-0.5 text-sm font-semibold" :class="paymentTotals().remaining_unallocated < -0.000001 ? 'text-red-700' : 'text-slate-900'" x-text="formatRupee6(paymentTotals().remaining_unallocated)"></div>
                        </div>
                    </div>

                    <div x-show="paymentInlineError()" class="mt-2 text-xs font-semibold text-red-700" x-text="paymentInlineError()"></div>
                    <div x-show="paymentInlineHint()" class="mt-2 text-xs text-slate-600" x-text="paymentInlineHint()"></div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-slate-900">Pending Invoices</div>
                    <button type="button" @click="autoAllocateOldestFirst()" :disabled="payment.loadingInvoices || payment.saving" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-60 disabled:cursor-not-allowed">
                        Auto Allocate (Oldest First)
                    </button>
                </div>

                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <div class="max-h-[55vh] overflow-y-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 text-xs font-semibold">Invoice No</th>
                                    <th class="px-3 py-2 text-xs font-semibold">Invoice Date</th>
                                    <th class="px-3 py-2 text-xs font-semibold text-right">Due Days</th>
                                    <th class="px-3 py-2 text-xs font-semibold text-right">Invoice Amount</th>
                                    <th class="px-3 py-2 text-xs font-semibold text-right">Pending</th>
                                    <th class="px-3 py-2 text-xs font-semibold text-right">Allocate Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-if="payment.loadingInvoices">
                                    <template>
                                        <template x-for="i in 10" :key="i">
                                            <tr>
                                                <td class="px-3 py-2"><div class="h-3 w-28 bg-slate-100 rounded"></div></td>
                                                <td class="px-3 py-2"><div class="h-3 w-20 bg-slate-100 rounded"></div></td>
                                                <td class="px-3 py-2 text-right"><div class="h-3 w-10 bg-slate-100 rounded ml-auto"></div></td>
                                                <td class="px-3 py-2 text-right"><div class="h-3 w-16 bg-slate-100 rounded ml-auto"></div></td>
                                                <td class="px-3 py-2 text-right"><div class="h-3 w-16 bg-slate-100 rounded ml-auto"></div></td>
                                                <td class="px-3 py-2 text-right"><div class="h-3 w-20 bg-slate-100 rounded ml-auto"></div></td>
                                            </tr>
                                        </template>
                                    </template>
                                </template>

                                <template x-if="!payment.loadingInvoices && payment.invoices.length === 0">
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-slate-500">No pending credit invoices</td>
                                    </tr>
                                </template>

                                <template x-for="inv in payment.invoices" :key="inv.id">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-3 py-2 font-semibold text-slate-900" x-text="inv.invoice_no"></td>
                                        <td class="px-3 py-2 text-slate-700" x-text="inv.invoice_date"></td>
                                        <td class="px-3 py-2 text-right text-slate-700" x-text="dueDays(inv)"></td>
                                        <td class="px-3 py-2 text-right text-slate-700" x-text="formatRupee6(inv.total_amount)"></td>
                                        <td class="px-3 py-2 text-right font-semibold" :class="amountTone(inv.pending_amount)" x-text="formatRupee6(inv.pending_amount)"></td>
                                        <td class="px-3 py-2 text-right">
                                            <div class="flex flex-col items-end">
                                                <input type="number" step="0.000001" min="0" :max="inv.pending_amount" x-model="inv.allocate_amount" @input="onAllocationInput(inv)" class="w-32 py-1.5 px-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-right">
                                                <div x-show="inv._error" class="mt-1 text-[11px] font-semibold text-red-700" x-text="inv._error"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div x-show="toast.show" x-cloak class="fixed bottom-4 right-4 z-[90]">
    <div class="px-4 py-2 rounded-lg border shadow-sm text-sm font-semibold"
         :class="toast.tone === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'">
        <span x-text="toast.message"></span>
    </div>
</div>

@push('scripts')
<script>
    function customerAccounts() {
        return {
            tab: 'customers',
            perPage: 50,
            invoiceSort: { key: 'pending_amount', dir: 'desc' },
            debug: false,
            filters: {
                q: '',
                status: 'all',
                from_date: '',
                to_date: '',
                aging: 'all',
                payment_status: 'all',
            },
            applied: {
                q: '',
                status: 'all',
                from_date: '',
                to_date: '',
                aging: 'all',
                payment_status: 'all',
            },
            summary: {
                total_outstanding: '0',
                total_overdue: '0',
                collection_received: '0',
                this_month_pending: '0',
            },
            customers: { rows: [], page: 1, total: 0, hasMore: false, loading: false },
            invoices: { rows: [], page: 1, total: 0, hasMore: false, loading: false },
            aging: { rows: [], page: 1, total: 0, hasMore: false, loading: false },
            abortController: null,
            drawerAbortController: null,
            _traceSeq: 0,
            receivePaymentPanelOpen: false,
            payment: {
                loadingInvoices: false,
                saving: false,
                serverError: '',
                serverErrors: {},
                form: {
                    payment_date: '',
                    payment_mode: 'Cash',
                    reference_no: '',
                    amount: '',
                    notes: '',
                },
                invoices: [],
            },
            toast: { show: false, message: '', tone: 'success' },
            drawer: {
                open: false,
                loading: false,
                customerBrief: null,
                customer: null,
                metrics: null,
                ledger: { entries: [], total_entries: 0, limit: 300, truncated: false },
            },
            makeTraceId(prefix) {
                const p = String(prefix || 'trace');
                const n = (++this._traceSeq) || 1;
                const t = Date.now();
                const r = Math.random().toString(16).slice(2, 6);
                return `${p}-${t}-${n}-${r}`;
            },
            traceStart(label, meta = {}) {
                if (!this.debug) return null;
                const id = this.makeTraceId(label);
                const t0 = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
                console.log(`[accounts][${id}] start`, { label, ...meta });
                return { id, label, t0 };
            },
            traceEnd(token, meta = {}) {
                if (!this.debug || !token) return;
                const t1 = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
                const ms = Math.round((t1 - (token.t0 || t1)) * 10) / 10;
                console.log(`[accounts][${token.id}] end`, { label: token.label, ms, ...meta });
            },
            init() {
                const params = new URLSearchParams(window.location.search || '');
                this.debug = params.get('accounts_debug') === '1';
                this.payment.form.payment_date = this.todayDate();
                this.applyFilters();
            },
            setTab(tab) {
                if (this.tab === tab) return;
                this.tab = tab;
                this.refresh();
            },
            async applyFilters() {
                this.applied = {
                    q: (this.filters.q || '').trim(),
                    status: this.filters.status || 'all',
                    from_date: this.filters.from_date || '',
                    to_date: this.filters.to_date || '',
                    aging: this.filters.aging || 'all',
                    payment_status: this.filters.payment_status || 'all',
                };
                this.customers.page = 1;
                this.invoices.page = 1;
                this.aging.page = 1;
                await this.loadCustomers(1);
                if (this.tab === 'invoices') {
                    await this.loadInvoices(1);
                }
                if (this.tab === 'aging') {
                    await this.loadAging(1);
                }
            },
            resetFilters() {
                this.filters = {
                    q: '',
                    status: 'all',
                    from_date: '',
                    to_date: '',
                    aging: 'all',
                    payment_status: 'all',
                };
                this.applyFilters();
            },
            refresh() {
                if (this.tab === 'customers') return this.loadCustomers(this.customers.page);
                if (this.tab === 'invoices') return this.loadInvoices(this.invoices.page);
                if (this.tab === 'aging') return this.loadAging(this.aging.page);
            },
            async refreshAll() {
                await this.loadCustomers(this.customers.page || 1);
                if (this.tab === 'invoices') {
                    await this.loadInvoices(this.invoices.page || 1);
                }
                if (this.tab === 'aging') {
                    await this.loadAging(this.aging.page || 1);
                }
            },
            async openDrawer(c) {
                this.drawer.open = true;
                this.receivePaymentPanelOpen = false;
                this.drawer.customerBrief = c || null;
                this.drawer.customer = null;
                this.drawer.metrics = null;
                this.drawer.ledger = { entries: [], total_entries: 0, limit: 300, truncated: false };
                await this.fetchDrawerDetail(c?.id);
            },
            openDrawerByCustomerId(customerId, customerName) {
                if (!customerId) return;
                return this.openDrawer({ id: customerId, name: customerName || 'Customer' });
            },
            async openDrawerAndReceivePayment(customerId, customerName) {
                if (!customerId) return;
                await this.openDrawerByCustomerId(customerId, customerName);
                this.openReceivePaymentPanel();
            },
            closeDrawer() {
                this.receivePaymentPanelOpen = false;
                this.drawer.open = false;
                if (this.drawerAbortController) {
                    try { this.drawerAbortController.abort(); } catch (e) {}
                }
            },
            openReceivePaymentPanel() {
                this.receivePaymentPanelOpen = true;
                this.payment.form.payment_date = this.todayDate();
                this.payment.form.payment_mode = 'Cash';
                this.payment.form.reference_no = '';
                this.payment.form.notes = '';
                this.payment.form.amount = '';
                this.payment.invoices = [];
                this.loadPendingInvoices();
            },
            closeReceivePaymentPanel() {
                this.receivePaymentPanelOpen = false;
                this.payment.loadingInvoices = false;
                this.payment.saving = false;
            },
            async fetchDrawerDetail(customerId, opts = {}) {
                if (!customerId) return;
                if (this.drawerAbortController) {
                    try { this.drawerAbortController.abort(); } catch (e) {}
                }
                this.drawerAbortController = new AbortController();
                this.drawer.loading = true;

                try {
                    const p = new URLSearchParams();
                    if (this.applied.from_date) p.set('from_date', this.applied.from_date);
                    if (this.applied.to_date) p.set('to_date', this.applied.to_date);
                    p.set('limit', '300');
                    p.set('_ts', String(Date.now()));
                    const url = `/app/api/accounts/customer-ledger/${customerId}/detail?${p.toString()}`;
                    const tr = this.traceStart('drawer', { reason: opts?.reason, traceId: opts?.traceId, customerId, url });
                    const headers = {};
                    if (opts?.traceId) headers['X-Accounts-Trace'] = String(opts.traceId);
                    const res = await fetch(url, { signal: this.drawerAbortController.signal, cache: 'no-store', headers });
                    const json = await res.json().catch(() => null);
                    this.drawer.customer = json?.customer || null;
                    this.drawer.metrics = json?.metrics || null;
                    this.drawer.ledger = json?.ledger || { entries: [], total_entries: 0, limit: 300, truncated: false };
                    if (this.debug) {
                        console.log('[accounts] fetchDrawerDetail', { customerId, url, outstanding: json?.metrics?.outstanding, last_payment: json?.metrics?.last_payment_date });
                    }
                    this.traceEnd(tr, {
                        status: res?.status,
                        ok: !!res?.ok,
                        outstanding: json?.metrics?.outstanding,
                        overdue: json?.metrics?.overdue,
                        last_payment: json?.metrics?.last_payment_date,
                    });
                } catch (e) {
                    this.drawer.customer = null;
                    this.drawer.metrics = null;
                    this.drawer.ledger = { entries: [], total_entries: 0, limit: 300, truncated: false };
                    if (this.debug) {
                        console.log('[accounts] fetchDrawerDetail failed', { customerId, error: String(e) });
                    }
                } finally {
                    this.drawer.loading = false;
                }
            },
            buildQueryParams(extra = {}) {
                const p = new URLSearchParams();
                const a = { ...this.applied, ...extra };
                Object.entries(a).forEach(([k, v]) => {
                    if (v === null || v === undefined) return;
                    const s = String(v).trim();
                    if (s === '') return;
                    p.set(k, s);
                });
                p.set('per_page', String(this.perPage));
                return p.toString();
            },
            async loadCustomers(page, opts = {}) {
                await this.loadAny('customers', `/app/api/accounts/customer-ledger`, page, opts);
            },
            async loadInvoices(page, opts = {}) {
                await this.loadAny('invoices', `/app/api/accounts/customer-ledger/invoices`, page, opts);
            },
            async loadAging(page, opts = {}) {
                await this.loadAny('aging', `/app/api/accounts/customer-ledger/aging`, page, opts);
            },
            async loadAny(key, baseUrl, page, opts = {}) {
                if (this.abortController) {
                    try { this.abortController.abort(); } catch (e) {}
                }
                this.abortController = new AbortController();
                this[key].loading = true;

                try {
                    const query = this.buildQueryParams({ page, _ts: Date.now() });
                    const url = `${baseUrl}?${query}`;
                    const prevSummary = key === 'customers' ? { ...this.summary } : null;
                    const prevRowsLen = Array.isArray(this[key]?.rows) ? this[key].rows.length : 0;
                    const tr = this.traceStart(key, { reason: opts?.reason, traceId: opts?.traceId, page, url });
                    const headers = {};
                    if (opts?.traceId) headers['X-Accounts-Trace'] = String(opts.traceId);
                    const res = await fetch(url, { signal: this.abortController.signal, cache: 'no-store', headers });
                    const json = await res.json().catch(() => null);
                    const rows = Array.isArray(json?.data) ? json.data : [];
                    this[key].rows = rows;
                    this[key].page = Number(json?.meta?.current_page || page || 1);
                    this[key].total = Number(json?.meta?.total || 0);
                    this[key].hasMore = !!json?.meta?.has_more;
                    if (key === 'customers' && json?.meta?.summary) {
                        this.summary = json.meta.summary;
                    }
                    if (this.debug) {
                        console.log('[accounts] loadAny', { key, page, url, rows: rows.length, summary: key === 'customers' ? json?.meta?.summary : undefined });
                        if (key === 'customers' && json?.meta?.summary) {
                            console.log('[accounts] kpi delta', { before: prevSummary, after: json.meta.summary });
                        }
                    }
                    this.traceEnd(tr, {
                        status: res?.status,
                        ok: !!res?.ok,
                        rows: rows.length,
                        prev_rows: prevRowsLen,
                        summary: key === 'customers' ? json?.meta?.summary : undefined,
                    });
                } catch (e) {
                    this[key].rows = [];
                    this[key].hasMore = false;
                    this[key].total = 0;
                    if (this.debug) {
                        console.log('[accounts] loadAny failed', { key, page, baseUrl, error: String(e) });
                    }
                } finally {
                    this[key].loading = false;
                }
            },
            currentPager() {
                if (this.tab === 'customers') return this.customers;
                if (this.tab === 'invoices') return this.invoices;
                return this.aging;
            },
            isFirstPage() {
                return (this.currentPager().page || 1) <= 1;
            },
            hasMore() {
                return !!this.currentPager().hasMore;
            },
            prevPage() {
                const p = this.currentPager();
                const next = Math.max(1, (p.page || 1) - 1);
                if (this.tab === 'customers') return this.loadCustomers(next);
                if (this.tab === 'invoices') return this.loadInvoices(next);
                return this.loadAging(next);
            },
            nextPage() {
                const p = this.currentPager();
                if (!p.hasMore) return;
                const next = (p.page || 1) + 1;
                if (this.tab === 'customers') return this.loadCustomers(next);
                if (this.tab === 'invoices') return this.loadInvoices(next);
                return this.loadAging(next);
            },
            paginationText() {
                const p = this.currentPager();
                const page = Number(p.page || 1);
                const total = Number(p.total || 0);
                const per = Number(this.perPage || 50);
                if (total <= 0) return '0 records';
                const start = (page - 1) * per + 1;
                const end = Math.min(page * per, total);
                return `${start}-${end} of ${total}`;
            },
            setInvoiceSort(key) {
                const k = String(key || '');
                if (!k) return;
                if (this.invoiceSort.key === k) {
                    this.invoiceSort.dir = this.invoiceSort.dir === 'asc' ? 'desc' : 'asc';
                    return;
                }
                this.invoiceSort.key = k;
                this.invoiceSort.dir = (k === 'invoice_date') ? 'desc' : 'desc';
            },
            displayedInvoices() {
                const rows = Array.isArray(this.invoices?.rows) ? [...this.invoices.rows] : [];
                const key = this.invoiceSort?.key || 'pending_amount';
                const dir = this.invoiceSort?.dir === 'asc' ? 1 : -1;
                const num = (v) => this.money6(v);
                const date = (v) => v ? String(v) : '';

                rows.sort((a, b) => {
                    if (key === 'invoice_date') {
                        const av = date(a.invoice_date);
                        const bv = date(b.invoice_date);
                        if (av === bv) return 0;
                        return (av < bv ? -1 : 1) * dir;
                    }
                    if (key === 'due_days') {
                        const av = num(a.due_days);
                        const bv = num(b.due_days);
                        if (av === bv) return 0;
                        return (av < bv ? -1 : 1) * dir;
                    }
                    if (key === 'pending_amount') {
                        const av = num(a.pending_amount);
                        const bv = num(b.pending_amount);
                        if (av === bv) return 0;
                        return (av < bv ? -1 : 1) * dir;
                    }
                    return 0;
                });
                return rows;
            },
            todayDate() {
                const d = new Date();
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}`;
            },
            money6(value) {
                const n = Number(value);
                if (!isFinite(n)) return 0;
                return Math.round(n * 1000000) / 1000000;
            },
            csrfToken() {
                const el = document.querySelector('meta[name="csrf-token"]');
                return el ? el.getAttribute('content') : '';
            },
            showToast(message, tone = 'success') {
                this.toast.message = String(message || '');
                this.toast.tone = tone;
                this.toast.show = true;
                window.clearTimeout(this.toast._t);
                this.toast._t = window.setTimeout(() => {
                    this.toast.show = false;
                }, 2500);
            },
            dueDays(inv) {
                const base = inv?.due_date || inv?.invoice_date;
                if (!base) return 0;
                const d = new Date(base + 'T00:00:00');
                const now = new Date();
                const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                const diff = Math.floor((today - d) / (24 * 60 * 60 * 1000));
                return Math.max(0, diff);
            },
            async loadPendingInvoices() {
                const customerId = this.drawer?.customer?.id || this.drawer?.customerBrief?.id;
                if (!customerId) return;
                this.payment.loadingInvoices = true;
                this.payment.serverError = '';
                this.payment.serverErrors = {};
                try {
                    const url = `/app/api/payments/pending-invoices?customer_id=${encodeURIComponent(customerId)}&_ts=${Date.now()}`;
                    const res = await fetch(url, { cache: 'no-store' });
                    const json = await res.json();
                    const rows = Array.isArray(json?.invoices) ? json.invoices : [];
                    this.payment.invoices = rows.map((r) => ({
                        id: r.id,
                        invoice_no: r.invoice_no,
                        invoice_date: r.invoice_date,
                        due_date: r.due_date,
                        total_amount: r.total_amount,
                        pending_amount: r.pending_amount,
                        allocate_amount: '',
                        _error: '',
                    }));
                    if (this.debug) {
                        console.log('[accounts] loadPendingInvoices', { customerId, invoices: this.payment.invoices.length });
                    }
                } catch (e) {
                    this.payment.invoices = [];
                    if (this.debug) {
                        console.log('[accounts] loadPendingInvoices failed', { customerId, error: String(e) });
                    }
                } finally {
                    this.payment.loadingInvoices = false;
                }
            },
            onPaymentAmountInput() {
                this.payment.serverError = '';
                this.payment.serverErrors = {};
            },
            onAllocationInput(inv) {
                this.payment.serverError = '';
                this.payment.serverErrors = {};
                const pending = this.money6(inv?.pending_amount);
                let v = this.money6(inv?.allocate_amount);
                if (v < 0) v = 0;
                if (v > pending + 0.000001) {
                    v = pending;
                    inv._error = 'Exceeds pending';
                } else {
                    inv._error = '';
                }
                inv.allocate_amount = v <= 0 ? '' : String(v);
            },
            autoAllocateOldestFirst() {
                const amount = this.money6(this.payment.form.amount);
                if (amount <= 0) return;
                let remaining = amount;
                this.payment.invoices.forEach((inv) => {
                    const pending = this.money6(inv.pending_amount);
                    const use = Math.min(pending, remaining);
                    const v = this.money6(use);
                    inv.allocate_amount = v <= 0 ? '' : String(v);
                    inv._error = '';
                    remaining = this.money6(remaining - v);
                });
            },
            paymentTotals() {
                const totalPending = this.money6(this.payment.invoices.reduce((s, inv) => s + this.money6(inv.pending_amount), 0));
                const amountEntered = this.money6(this.payment.form.amount);
                const allocated = this.money6(this.payment.invoices.reduce((s, inv) => s + this.money6(inv.allocate_amount), 0));
                const remaining = this.money6(amountEntered - allocated);
                return {
                    total_pending: totalPending,
                    amount_entered: amountEntered,
                    allocated: allocated,
                    remaining_unallocated: remaining,
                };
            },
            agingTotals() {
                const rows = Array.isArray(this.aging?.rows) ? this.aging.rows : [];
                return rows.reduce((acc, r) => {
                    acc.b_0_7 = this.money6(acc.b_0_7 + this.money6(r.b_0_7));
                    acc.b_8_15 = this.money6(acc.b_8_15 + this.money6(r.b_8_15));
                    acc.b_16_30 = this.money6(acc.b_16_30 + this.money6(r.b_16_30));
                    acc.b_30_plus = this.money6(acc.b_30_plus + this.money6(r.b_30_plus));
                    acc.total_pending = this.money6(acc.total_pending + this.money6(r.total_pending));
                    return acc;
                }, { b_0_7: 0, b_8_15: 0, b_16_30: 0, b_30_plus: 0, total_pending: 0 });
            },
            paymentInlineError() {
                if (this.payment.serverError) return this.payment.serverError;
                const amount = this.money6(this.payment.form.amount);
                if (amount <= 0) return 'Enter payment amount.';
                const totals = this.paymentTotals();
                if (totals.allocated > totals.amount_entered + 0.000001) return 'Allocated amount cannot exceed payment amount.';
                const hasRowError = this.payment.invoices.some((inv) => !!inv._error);
                if (hasRowError) return 'Fix allocation errors before saving.';
                return '';
            },
            paymentInlineHint() {
                const amount = this.money6(this.payment.form.amount);
                if (amount <= 0) return '';
                const totals = this.paymentTotals();
                if (totals.remaining_unallocated > 0.000001) return 'Unallocated amount will be auto-allocated oldest first on save.';
                return '';
            },
            canSavePayment() {
                if (this.payment.saving) return false;
                const customerId = this.drawer?.customer?.id || this.drawer?.customerBrief?.id;
                if (!customerId) return false;
                const amount = this.money6(this.payment.form.amount);
                if (amount <= 0) return false;
                const totals = this.paymentTotals();
                if (totals.allocated > totals.amount_entered + 0.000001) return false;
                if (this.payment.invoices.some((inv) => !!inv._error)) return false;
                return true;
            },
            async savePayment() {
                if (!this.canSavePayment()) return;
                const customerId = this.drawer?.customer?.id || this.drawer?.customerBrief?.id;
                const amount = this.money6(this.payment.form.amount);
                const beforeDrawerOutstanding = this.money6(this.drawer?.metrics?.outstanding);
                const predictedAfterOutstanding = this.money6(beforeDrawerOutstanding - amount);
                const traceId = this.makeTraceId('payment');
                const allocations = this.payment.invoices
                    .map((inv) => ({
                        invoice_id: inv.id,
                        amount: this.money6(inv.allocate_amount),
                    }))
                    .filter((a) => a.amount > 0);

                this.payment.saving = true;
                this.payment.serverError = '';
                this.payment.serverErrors = {};

                try {
                    if (this.debug) {
                        console.log('[accounts] savePayment start', {
                            traceId,
                            customerId,
                            amount,
                            allocations_count: allocations.length,
                            before_drawer_outstanding: beforeDrawerOutstanding,
                            predicted_after_drawer_outstanding: predictedAfterOutstanding,
                            before_kpi_outstanding: this.summary?.total_outstanding,
                        });
                    }
                    const res = await fetch(`/app/payments/receive`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Accounts-Trace': String(traceId),
                        },
                        body: JSON.stringify({
                            customer_id: customerId,
                            payment_date: this.payment.form.payment_date || this.todayDate(),
                            payment_mode: this.payment.form.payment_mode || 'Cash',
                            reference_no: this.payment.form.reference_no || null,
                            notes: this.payment.form.notes || null,
                            amount: amount,
                            allocations: allocations,
                        }),
                    });

                    if (!res.ok) {
                        const json = await res.json().catch(() => null);
                        if (res.status === 422 && json?.errors) {
                            this.payment.serverErrors = json.errors;
                            const firstKey = Object.keys(json.errors)[0];
                            const firstMsg = firstKey ? (Array.isArray(json.errors[firstKey]) ? json.errors[firstKey][0] : json.errors[firstKey]) : null;
                            this.payment.serverError = firstMsg || 'Validation error.';
                        } else {
                            this.payment.serverError = (json?.message || 'Failed to save payment.');
                        }
                        return;
                    }

                    const okJson = await res.json().catch(() => null);
                    this.showToast(okJson?.message || 'Payment saved.', 'success');
                    this.receivePaymentPanelOpen = false;

                    if (this.debug) {
                        console.log('[accounts] savePayment success -> refresh start', { traceId, okJson });
                    }

                    await this.fetchDrawerDetail(customerId, { traceId, reason: 'after-payment-save' });
                    await this.loadCustomers(this.customers.page || 1, { traceId, reason: 'after-payment-save' });
                    if (this.tab === 'invoices') {
                        await this.loadInvoices(this.invoices.page, { traceId, reason: 'after-payment-save' });
                    }
                    if (this.tab === 'aging') {
                        await this.loadAging(this.aging.page, { traceId, reason: 'after-payment-save' });
                    }
                    if (this.debug) {
                        console.log('[accounts] savePayment refresh done', {
                            traceId,
                            kpi_outstanding: this.summary?.total_outstanding,
                            drawer_outstanding: this.drawer?.metrics?.outstanding,
                            customers_rows: this.customers?.rows?.length,
                        });
                    }
                } catch (e) {
                    this.payment.serverError = 'Failed to save payment.';
                    if (this.debug) {
                        console.log('[accounts] savePayment failed', { error: String(e) });
                    }
                } finally {
                    this.payment.saving = false;
                }
            },
            formatRupee6(value) {
                const n = Number(value) || 0;
                const fixed = n.toFixed(6).replace(/\.?0+$/, '');
                return '₹' + fixed;
            },
            creditLimitText(c) {
                const limit = Number(c.credit_limit) || 0;
                if (limit <= 0) return '-';
                return this.formatRupee6(limit);
            },
            isOverLimit(c) {
                const out = Number(c?.outstanding_amount) || 0;
                const limit = Number(c?.credit_limit) || 0;
                if (limit <= 0) return false;
                return out > (limit + 0.000001);
            },
            availableCreditText(c) {
                const limit = Number(c?.credit_limit) || 0;
                if (limit <= 0) return '-';
                const out = Number(c?.outstanding_amount) || 0;
                return this.formatRupee6(limit - out);
            },
            availableCreditClass(c) {
                const limit = Number(c?.credit_limit) || 0;
                if (limit <= 0) return 'text-slate-500';
                const out = Number(c?.outstanding_amount) || 0;
                const avail = limit - out;
                if (avail < -0.000001) return 'text-red-700 font-semibold';
                return 'text-slate-700';
            },
            drawerAvailableCreditText() {
                const limit = Number(this.drawer?.customer?.credit_limit) || 0;
                if (limit <= 0) return '-';
                const out = Number(this.drawer?.metrics?.outstanding) || 0;
                return this.formatRupee6(limit - out);
            },
            drawerAvailableCreditClass() {
                const limit = Number(this.drawer?.customer?.credit_limit) || 0;
                if (limit <= 0) return 'text-slate-500';
                const out = Number(this.drawer?.metrics?.outstanding) || 0;
                const avail = limit - out;
                if (avail < -0.000001) return 'text-red-700';
                return 'text-slate-900';
            },
            amountTone(value) {
                const n = Number(value) || 0;
                if (n <= 0) return 'text-slate-900';
                return 'text-amber-800';
            },
            statusChipText(c) {
                const outstanding = Number(c.outstanding_amount) || 0;
                const overdue = Number(c.overdue_amount) || 0;
                const totalPaid = Number(c.total_paid_amount) || 0;
                if (outstanding <= 0) return 'Paid';
                if (overdue > 0) return 'Overdue';
                if (totalPaid > 0) return 'Partial';
                return 'Outstanding';
            },
            statusChipClass(c) {
                const t = this.statusChipText(c);
                if (t === 'Paid') return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                if (t === 'Overdue') return 'bg-red-50 text-red-700 border border-red-200';
                if (t === 'Partial') return 'bg-amber-50 text-amber-800 border border-amber-200';
                return 'bg-orange-50 text-orange-800 border border-orange-200';
            },
            invoiceStatusChipClass(status) {
                const s = String(status || '').toLowerCase();
                if (s === 'paid') return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                if (s === 'partial') return 'bg-amber-50 text-amber-800 border border-amber-200';
                if (s === 'unpaid') return 'bg-orange-50 text-orange-800 border border-orange-200';
                return 'bg-slate-50 text-slate-700 border border-slate-200';
            }
        }
    }
</script>
@endpush
@endsection
