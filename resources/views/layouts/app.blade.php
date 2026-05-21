<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sagardutt')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Syne:wght@400..800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #f8fafc;
        }
        h1, h2, h3, h4, h5, h6, .font-syne {
            font-family: 'Syne', sans-serif;
        }
        [x-cloak] { display: none !important; }
        
        /* Modern Toggle Switch */
        .nt-switch {
            position: relative;
            display: inline-block;
            width: 42px;
            height: 22px;
            vertical-align: middle;
        }
        .nt-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .nt-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 22px;
        }
        .nt-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 2;
        }
        input:checked + .nt-slider {
            background-color: #10b981;
        }
        input:checked + .nt-slider:before {
            transform: translateX(20px);
        }
        /* Hover state */
        .nt-switch:hover .nt-slider {
            background-color: #94a3b8;
        }
        .nt-switch:hover input:checked + .nt-slider {
            background-color: #059669;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
    @stack('styles')
</head>
@php
    $layoutUser = auth()->user();
    $layoutRoleName = strtolower((string) ($layoutUser?->role?->name ?? ''));
    $layoutIsSalesman = $layoutRoleName === 'salesman';
@endphp
<body class="antialiased text-slate-800 h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <aside @if($layoutIsSalesman) class="hidden lg:flex w-[252px] bg-[#07111f] text-slate-300 flex-col h-full flex-shrink-0 z-20 shadow-xl" @else class="w-[252px] bg-[#07111f] text-slate-300 flex flex-col h-full flex-shrink-0 z-20 shadow-xl" @endif>
        <!-- Logo -->
        <div class="h-16 flex items-center px-6 border-b border-white/5">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-syne font-bold text-lg mr-3 shadow-lg shadow-indigo-500/20">
                SD
            </div>
            <div>
                <h1 class="text-white font-syne font-black text-[17px] leading-none tracking-[0.18em] uppercase">SAGARDUTT</h1>
                <p class="text-[10px] text-slate-400 font-medium tracking-wider uppercase">Pepsi Distributor</p>
            </div>
        </div>

        <!-- Nav Links -->
        <div id="sidebar-scroll" class="flex-1 overflow-y-auto py-3 px-4 space-y-5 scrollbar-hide">
            @php
                $user = $layoutUser;
                $roleName = $layoutRoleName;
                $isSalesmanPanel = $layoutIsSalesman;

                $showDashboard = true;
                $showProducts = true;
                $showMasters = true;
                $showCustomers = true;
                $showSuppliers = true;
                $showSchemes = true;
                $showProductGroups = true;
                $showInventory = true;
                $showPurchases = true;
                $showNewInvoice = true;
                $showAllInvoices = true;
                $showDeletedInvoices = true;
                $showReceivables = true;
                $showReceivePayment = true;
                $showCustomerLedger = true;
                $showReports = true;
                $showSettings = true;
                $showRoleMaster = true;
                $showUsers = true;

                $showMainSection = true;
                $showInventorySection = true;
                $showSalesSection = true;
                $showAccountsSection = true;
                $showReportsSection = true;
                $showSystemSection = true;
            @endphp

            @if($isSalesmanPanel)
            <div>
                <p class="px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Salesman</p>
                <div class="space-y-0">
                    <a href="{{ route('salesman.invoices.index') }}" @if($layoutIsSalesman) @click="sidebarOpen = false" @endif class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('salesman/invoices*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-receipt text-lg"></i>
                        <span class="text-sm font-medium">My Invoices</span>
                    </a>
                    <a href="{{ route('salesman.invoices.create') }}" @if($layoutIsSalesman) @click="sidebarOpen = false" @endif class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('salesman/invoices/create') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-plus-circle text-lg"></i>
                        <span class="text-sm font-medium">New Invoice</span>
                    </a>
                </div>
            </div>
            @else
            <!-- MAIN -->
            @if($showMainSection)
            <div>
                <p class="px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Main</p>
                <div class="space-y-0">
                    @if($showDashboard)
                    <a href="{{ route('app.dashboard') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/dashboard*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-squares-four text-lg"></i>
                        <span class="text-sm font-medium">Dashboard</span>
                    </a>
                    @endif
                    @if($showProducts)
                    <a href="{{ route('app.products.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/products*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-package text-lg"></i>
                        <span class="text-sm font-medium">Products</span>
                    </a>
                    @endif
                    @if($showMasters)
                    <a href="{{ route('app.masters.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/masters*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-database text-lg"></i>
                        <span class="text-sm font-medium">Masters</span>
                    </a>
                    @endif
                    @if($showProductGroups)
                    <a href="{{ route('app.product-groups.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/product-groups*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-folders text-lg"></i>
                        <span class="text-sm font-medium">Product Groups</span>
                    </a>
                    @endif
                    @if($showCustomers)
                    <a href="{{ route('app.customers.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/customers*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-users text-lg"></i>
                        <span class="text-sm font-medium">Customers</span>
                    </a>
                    @endif
                    @if($showSuppliers)
                    <a href="{{ route('app.suppliers.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/suppliers*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-truck text-lg"></i>
                        <span class="text-sm font-medium">Suppliers</span>
                    </a>
                    @endif
                    @if($showSchemes)
                    <a href="{{ route('app.schemes.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/schemes*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-ticket text-lg"></i>
                        <span class="text-sm font-medium">Schemes</span>
                    </a>
                    @endif
                    @if($showSchemes)
                    <a href="{{ route('app.group-schemes.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/group-schemes*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-ticket text-lg"></i>
                        <span class="text-sm font-medium">Schemes (Groups)</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- INVENTORY -->
            @if($showInventorySection)
            <div>
                <p class="px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Inventory</p>
                <div class="space-y-0">
                    @if($showInventory)
                    <a href="{{ route('app.inventory.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/inventory*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-archive-box text-lg"></i>
                        <span class="text-sm font-medium">Inventory</span>
                    </a>
                    @endif
                    @if($showPurchases)
                    <a href="{{ route('app.purchases.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/purchases*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-shopping-cart text-lg"></i>
                        <span class="text-sm font-medium">Purchases</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- SALES -->
            @if($showSalesSection)
            <div>
                <p class="px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Sales</p>
                <div class="space-y-0">
                    @if($showNewInvoice)
                    <a href="{{ route('app.invoices.create') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/invoices/create') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-plus-circle text-lg"></i>
                        <span class="text-sm font-medium">New Invoice</span>
                    </a>
                    @endif
                    @if($showAllInvoices)
                    <a href="{{ route('app.invoices.index') }}" class="flex items-center justify-between px-3 py-1 rounded-lg transition-all {{ Request::is('app/invoices') || Request::is('app/invoices/*/edit') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-receipt text-lg"></i>
                            <span class="text-sm font-medium">All Invoices</span>
                        </div>
                        @php
                            $invCount = 0;
                            try {
                                $invCount = \App\Models\Invoice::active()->count();
                            } catch (\Throwable $e) {
                                $invCount = 0;
                            }
                        @endphp
                        @if($invCount > 0)
                            <span class="bg-indigo-600/20 text-indigo-400 py-0.5 px-2 rounded-full text-xs font-semibold">{{ $invCount }}</span>
                        @endif
                    </a>
                    @endif
                    @if($showDeletedInvoices)
                    <a href="{{ route('app.invoices.deleted') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/invoices/deleted/list') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-trash text-lg"></i>
                        <span class="text-sm font-medium">Deleted Invoices</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- ACCOUNTS -->
            @if($showAccountsSection)
            <div>
                <p class="px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Accounts</p>
                <div class="space-y-0">
                    @if($showCustomerLedger)
                    <a href="{{ route('accounts-v2.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('accounts-v2*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-book-open-text text-lg"></i>
                        <span class="text-sm font-medium">Customer Accounts</span>
                    </a>
                    @endif
                    <a href="{{ route('app.accounts.customer_ledger') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/accounts/customer-ledger*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-archive text-lg"></i>
                        <span class="text-sm font-medium">Legacy Accounts</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- REPORTS -->
            @if($showReportsSection)
            <div>
                <p class="px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Reports</p>
                <div class="space-y-0">
                    @if($showReports)
                    <a href="{{ route('app.reports.sales') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/reports/sales*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-chart-line-up text-lg"></i>
                        <span class="text-sm font-medium">Sales Report</span>
                    </a>
                    <a href="{{ route('app.reports.purchase') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/reports/purchase*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-chart-bar text-lg"></i>
                        <span class="text-sm font-medium">Purchase Report</span>
                    </a>
                    <a href="{{ route('app.reports.gst') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/reports/gst*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-file-text text-lg"></i>
                        <span class="text-sm font-medium">GST Report</span>
                    </a>
                    <a href="{{ route('app.reports.sales-register') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/reports/sales-register*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-receipt text-lg"></i>
                        <span class="text-sm font-medium">Sales Register</span>
                    </a>
                    <a href="{{ route('app.reports.daily') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/reports/daily*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-table text-lg"></i>
                        <span class="text-sm font-medium">Daily Report</span>
                    </a>
                    <a href="{{ route('app.stock-report.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/stock-report*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-warehouse text-lg"></i>
                        <span class="text-sm font-medium">Stock Report</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- SYSTEM -->
            @if($showSystemSection)
            <div>
                <p class="px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">System</p>
                <div class="space-y-0">
                    @if($showSettings)
                    <a href="{{ route('app.settings.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/settings*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-gear text-lg"></i>
                        <span class="text-sm font-medium">Settings</span>
                    </a>
                    @endif
                    @if($showRoleMaster || $showUsers)
                    <a href="{{ route('app.roles.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/roles*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-identification-card text-lg"></i>
                        <span class="text-sm font-medium">Role Master</span>
                    </a>
                    <a href="{{ route('app.users.index') }}" class="flex items-center gap-3 px-3 py-1 rounded-lg transition-all {{ Request::is('app/users*') ? 'bg-indigo-600/10 text-indigo-400' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="ph ph-users-three text-lg"></i>
                        <span class="text-sm font-medium">Users</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            @endif

        </div>

        <!-- User Info -->
        <div class="p-4 border-t border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-slate-800 flex items-center justify-center border border-white/10 text-indigo-400">
                    <i class="ph ph-user text-lg"></i>
                </div>
                <div class="flex-1 overflow-hidden">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()?->name ?? 'Admin User' }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ auth()->user()?->email ?? 'admin@example.com' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-500 hover:text-white transition-colors" title="Logout">
                        <i class="ph ph-sign-out text-lg"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-h-0 overflow-hidden relative">
        
        <!-- Topbar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 flex-shrink-0 z-10">
            <div>
                <h2 class="font-syne font-semibold text-lg text-slate-800">@yield('title', 'Dashboard')</h2>
            </div>
            <div class="flex items-center gap-4">
                @if($layoutIsSalesman)
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-10 h-10 sm:w-auto sm:h-auto inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 px-0 sm:px-4 py-0 sm:py-2 font-semibold" aria-label="Logout">
                            <i class="ph ph-sign-out text-xl sm:text-lg"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </form>
                @endif
            </div>
        </header>

        <!-- Flash Messages via Alpine -->
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" class="{{ $layoutIsSalesman ? 'fixed top-20 left-4 right-4 sm:left-auto sm:right-6 z-50' : 'absolute top-20 right-6 z-50' }}">
            @if (session('success'))
                <div x-show="show" x-transition.duration.300ms class="bg-white border-l-4 border-emerald-500 shadow-lg rounded-r-lg p-4 flex items-center gap-3 {{ $layoutIsSalesman ? 'w-full sm:min-w-[300px]' : 'min-w-[300px]' }}">
                    <div class="bg-emerald-100 text-emerald-600 rounded-full p-1">
                        <i class="ph-fill ph-check-circle text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-800">Success</h4>
                        <p class="text-xs text-slate-500 mt-0.5">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div x-show="show" x-transition.duration.300ms class="bg-white border-l-4 border-red-500 shadow-lg rounded-r-lg p-4 flex items-center gap-3 {{ $layoutIsSalesman ? 'w-full sm:min-w-[300px]' : 'min-w-[300px]' }} mt-2">
                    <div class="bg-red-100 text-red-600 rounded-full p-1">
                        <i class="ph-fill ph-warning-circle text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-800">Error</h4>
                        <p class="text-xs text-slate-500 mt-0.5">{{ session('error') }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Content Area -->
        <div class="flex-1 min-h-0 overflow-y-auto {{ $layoutIsSalesman ? 'p-4 sm:p-6' : 'p-6' }} scrollbar-hide">
            @yield('content')
        </div>

    </main>

    <script>
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-password-toggle]');
            if (!btn) return;
            const container = btn.closest('[data-password-field]');
            if (!container) return;
            const input = container.querySelector('input');
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.remove('ph-eye', 'ph-eye-slash');
                icon.classList.add(show ? 'ph-eye-slash' : 'ph-eye');
            }
        });
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar-scroll');
            if (!sidebar) return;
            const stored = localStorage.getItem('sidebarScrollTop');
            if (stored !== null) {
                const val = parseInt(stored, 10);
                if (!Number.isNaN(val)) {
                    sidebar.scrollTop = val;
                }
            }
            sidebar.addEventListener('scroll', () => {
                localStorage.setItem('sidebarScrollTop', String(sidebar.scrollTop));
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
