<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SchemeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\OpeningStockController;
use App\Http\Controllers\ReceivablesController;
use App\Http\Controllers\ReceivePaymentController;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\AccountsV2Controller;
use App\Http\Controllers\SalesmanController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductGroupController;
use App\Http\Controllers\GroupSchemeController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\SalesRegisterReportController;
use App\Http\Middleware\EnsurePermission;
use App\Models\CustomerPayment;
use App\Models\CustomerPaymentAllocation;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// Redirect root to dashboard
Route::redirect('/', '/app/dashboard');

Route::middleware(['auth', EnsurePermission::class])->prefix('app')->name('app.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Products
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::patch('/products/{id}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');

    // Categories
    Route::get('/categories', [\App\Http\Controllers\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [\App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [\App\Http\Controllers\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [\App\Http\Controllers\CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::patch('/categories/{category}/toggle', [\App\Http\Controllers\CategoryController::class, 'toggle'])->name('categories.toggle');

    // Other Masters
    Route::get('/masters', [\App\Http\Controllers\MasterController::class, 'index'])->name('masters.index');
    Route::post('/masters/hsn', [\App\Http\Controllers\MasterController::class, 'storeHsn'])->name('masters.hsn.store');
    Route::delete('/masters/hsn/{hsnCode}', [\App\Http\Controllers\MasterController::class, 'destroyHsn'])->name('masters.hsn.destroy');
    Route::post('/masters/unit-types', [\App\Http\Controllers\MasterController::class, 'storeUnitType'])->name('masters.unit.store');
    Route::delete('/masters/unit-types/{unitType}', [\App\Http\Controllers\MasterController::class, 'destroyUnitType'])->name('masters.unit.destroy');
    Route::post('/masters/pack-types', [\App\Http\Controllers\MasterController::class, 'storePackType'])->name('masters.pack.store');
    Route::delete('/masters/pack-types/{packType}', [\App\Http\Controllers\MasterController::class, 'destroyPackType'])->name('masters.pack.destroy');
    Route::post('/masters/brands', [\App\Http\Controllers\MasterController::class, 'storeBrand'])->name('masters.brand.store');
    Route::delete('/masters/brands/{brand}', [\App\Http\Controllers\MasterController::class, 'destroyBrand'])->name('masters.brand.destroy');
    Route::post('/masters/volumes', [\App\Http\Controllers\MasterController::class, 'storeVolume'])->name('masters.volume.store');
    Route::delete('/masters/volumes/{volume}', [\App\Http\Controllers\MasterController::class, 'destroyVolume'])->name('masters.volume.destroy');
    Route::post('/masters/areas', [\App\Http\Controllers\MasterController::class, 'storeArea'])->name('masters.area.store');
    Route::delete('/masters/areas/{area}', [\App\Http\Controllers\MasterController::class, 'destroyArea'])->name('masters.area.destroy');
    Route::post('/masters/pack-sizes', [\App\Http\Controllers\MasterController::class, 'storePackSize'])->name('masters.pack_size.store');
    Route::delete('/masters/pack-sizes/{packSize}', [\App\Http\Controllers\MasterController::class, 'destroyPackSize'])->name('masters.pack_size.destroy');
    Route::post('/masters/categories', [\App\Http\Controllers\MasterController::class, 'storeCategory'])->name('masters.category.store');
    Route::delete('/masters/categories/{category}', [\App\Http\Controllers\MasterController::class, 'destroyCategory'])->name('masters.category.destroy');

    // Product Groups
    Route::get('/product-groups', [ProductGroupController::class, 'index'])->name('product-groups.index');
    Route::post('/product-groups', [ProductGroupController::class, 'store'])->name('product-groups.store');
    Route::get('/product-groups/{id}/edit', [ProductGroupController::class, 'edit'])->name('product-groups.edit');
    Route::put('/product-groups/{id}', [ProductGroupController::class, 'update'])->name('product-groups.update');

    // Schemes (Product Groups)
    Route::get('/group-schemes', [GroupSchemeController::class, 'index'])->name('group-schemes.index');
    Route::post('/group-schemes', [GroupSchemeController::class, 'store'])->name('group-schemes.store');
    Route::get('/group-schemes/{id}/edit', [GroupSchemeController::class, 'edit'])->name('group-schemes.edit');
    Route::put('/group-schemes/{id}', [GroupSchemeController::class, 'update'])->name('group-schemes.update');
    Route::patch('/group-schemes/{id}/toggle', [GroupSchemeController::class, 'toggle'])->name('group-schemes.toggle');

    // Salesman Master
    Route::post('/masters/salesmen', [SalesmanController::class, 'store'])->name('masters.salesmen.store');
    Route::get('/masters/salesmen/{id}/edit', [SalesmanController::class, 'edit'])->name('masters.salesmen.edit');
    Route::put('/masters/salesmen/{id}', [SalesmanController::class, 'update'])->name('masters.salesmen.update');
    Route::delete('/masters/salesmen/{id}', [SalesmanController::class, 'destroy'])->name('masters.salesmen.destroy');
    Route::patch('/masters/salesmen/{id}/toggle', [SalesmanController::class, 'toggle'])->name('masters.salesmen.toggle');
    
    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/template', [CustomerController::class, 'downloadTemplate'])->name('customers.template');
    Route::post('/customers/upload', [CustomerController::class, 'uploadExcel'])->name('customers.upload');
    Route::get('/customers/upload/errors/{token}', [CustomerController::class, 'downloadErrorExcel'])->name('customers.upload.errors');
    Route::post('/customers/upload/commit', [CustomerController::class, 'commitUpload'])->name('customers.upload.commit');
    Route::post('/customers/upload/cancel', [CustomerController::class, 'cancelUpload'])->name('customers.upload.cancel');
    Route::get('/customers/imports', [CustomerController::class, 'importHistory'])->name('customers.imports.index');
    Route::get('/customers/imports/{token}', [CustomerController::class, 'importHistoryShow'])->name('customers.imports.show');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{id}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::patch('/customers/{id}/toggle', [CustomerController::class, 'toggle'])->name('customers.toggle');
    
    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/{id}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::put('/suppliers/{id}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    Route::patch('/suppliers/{id}/toggle', [SupplierController::class, 'toggle'])->name('suppliers.toggle');
    Route::get('/api/suppliers/search', [SupplierController::class, 'search'])->name('api.suppliers.search');
    
    // Schemes
    Route::get('/schemes', [SchemeController::class, 'index'])->name('schemes.index');
    Route::post('/schemes', [SchemeController::class, 'store'])->name('schemes.store');
    Route::get('/schemes/{id}', [SchemeController::class, 'show'])->name('schemes.show');
    Route::put('/schemes/{id}', [SchemeController::class, 'update'])->name('schemes.update');
    Route::delete('/schemes/{id}', [SchemeController::class, 'destroy'])->name('schemes.destroy');
    Route::patch('/schemes/{id}/toggle', [SchemeController::class, 'toggle'])->name('schemes.toggle');
    
    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/{product}', [InventoryController::class, 'show'])->name('inventory.show');
    Route::post('/inventory/{product}/transaction', [InventoryController::class, 'storeTransaction'])->name('inventory.transaction');
    
    // Stock Report
    Route::get('/stock-report', [StockController::class, 'index'])->name('stock-report.index');
    Route::get('/stock-report/export', [StockController::class, 'export'])->name('stock-report.export');

    // Opening Stock
    Route::get('/opening-stock', [OpeningStockController::class, 'index'])->name('opening-stock.index');
    Route::post('/opening-stock/add', [OpeningStockController::class, 'addStock'])->name('opening-stock.add');
    Route::post('/opening-stock/save', [OpeningStockController::class, 'save'])->name('opening-stock.save');
    
    // Purchases
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{id}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::get('/purchases/{id}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit');
    Route::put('/purchases/{id}', [PurchaseController::class, 'update'])->name('purchases.update');
    Route::delete('/purchases/{id}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');
    
    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{id}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{id}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::get('/invoices/deleted/list', [InvoiceController::class, 'deleted'])->name('invoices.deleted');
    Route::patch('/invoices/{id}/restore', [InvoiceController::class, 'restore'])->name('invoices.restore');
    Route::delete('/invoices/{id}/force', [InvoiceController::class, 'forceDelete'])->name('invoices.forceDelete');
    Route::get('/invoices/{id}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{id}/details-json', [InvoiceController::class, 'detailsJson'])->name('invoices.details_json');
    Route::get('/invoices/{id}/einvoice-json', [InvoiceController::class, 'eInvoiceJson'])->name('invoices.einvoice_json');

    // Receivables / Outstanding
    Route::get('/receivables', [ReceivablesController::class, 'index'])->name('receivables.index');
    Route::get('/receivables/{customerId}', [ReceivablesController::class, 'show'])->name('receivables.show');
    Route::get('/receivables-invoices', [ReceivablesController::class, 'invoices'])->name('receivables.invoices');
    Route::get('/receivables-aging', [ReceivablesController::class, 'aging'])->name('receivables.aging');

    // Receive Payment
    Route::get('/payments/receive', [ReceivePaymentController::class, 'create'])->name('payments.receive');
    Route::post('/payments/receive', [ReceivePaymentController::class, 'store'])->name('payments.receive.store');
    Route::get('/api/payments/pending-invoices', [ReceivePaymentController::class, 'pendingInvoices'])->name('api.payments.pending_invoices');

    // Accounts
    Route::get('/accounts/customer-ledger', [AccountsController::class, 'customerLedger'])->name('accounts.customer_ledger');
    Route::get('/accounts/customer-ledger/{customerId}', [AccountsController::class, 'customerLedgerShow'])->name('accounts.customer_ledger.show');
    
    // Reports
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/sales-register', [SalesRegisterReportController::class, 'index'])->name('reports.sales-register');
    Route::get('/reports/sales-register/export', [SalesRegisterReportController::class, 'export'])->name('reports.sales-register.export');
    Route::get('/reports/purchase', [ReportController::class, 'purchase'])->name('reports.purchase');
    Route::get('/reports/gst', [ReportController::class, 'gst'])->name('reports.gst');
    Route::post('/reports/gst/bulk-json', [ReportController::class, 'bulkJson'])->name('reports.gst.bulk-json');
    Route::get('/reports/daily', [DailyReportController::class, 'index'])->name('reports.daily');
    Route::get('/reports/daily/export-sales', [DailyReportController::class, 'exportSales'])->name('reports.daily.exportSales');
    Route::get('/reports/daily/export-scheme', [DailyReportController::class, 'exportScheme'])->name('reports.daily.exportScheme');
    
    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/invoice-qr', [SettingController::class, 'uploadInvoiceQr'])->name('settings.invoice_qr.upload');
    Route::get('/settings/invoice-qr/preview', [SettingController::class, 'invoiceQrPreview'])->name('settings.invoice_qr.preview');

    // Role Master
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{id}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    
    // API helpers
    Route::get('/api/products/search', [ProductController::class, 'search'])->name('api.products.search');
    Route::get('/api/customers/search', [CustomerController::class, 'search'])->name('api.customers.search');
    Route::get('/api/dashboard', [DashboardController::class, 'data'])->name('api.dashboard');
    Route::get('/api/dashboard/products', [DashboardController::class, 'products'])->name('api.dashboard.products');
    Route::get('/api/inventory/products', [InventoryController::class, 'products'])->name('api.inventory.products');
    Route::post('/api/inventory/products/{product}/adjust', [InventoryController::class, 'adjust'])->name('api.inventory.adjust');
    Route::get('/api/accounts/customer-ledger', [AccountsController::class, 'customerLedgerSearch'])->name('api.accounts.customer_ledger');
    Route::get('/api/accounts/customer-ledger/invoices', [AccountsController::class, 'customerLedgerInvoicesSearch'])->name('api.accounts.customer_ledger.invoices');
    Route::get('/api/accounts/customer-ledger/aging', [AccountsController::class, 'customerLedgerAgingSearch'])->name('api.accounts.customer_ledger.aging');
    Route::get('/api/accounts/customer-ledger/{customerId}/detail', [AccountsController::class, 'customerLedgerDrawerDetail'])->name('api.accounts.customer_ledger.detail');
    Route::get('/api/schemes/for-product/{id}', [SchemeController::class, 'forProduct'])->name('api.schemes.forProduct');
    Route::get('/api/invoice/next-no', [InvoiceController::class, 'nextNo'])->name('api.invoice.nextNo');
});

Route::get('/debug/db-check', function () {
    abort_unless(app()->isLocal() || (bool) config('app.debug'), 404);

    $connName = DB::getDefaultConnection();
    $conn = DB::connection($connName);

    $databaseSelect = null;
    try {
        $row = DB::selectOne('select database() as db');
        $databaseSelect = $row?->db ?? null;
    } catch (\Throwable $e) {
        $databaseSelect = null;
    }

    $invoicesCount = Invoice::query()->count();
    $paymentsCount = CustomerPayment::query()->count();

    $latestPayment = CustomerPayment::query()->orderByDesc('id')->first();
    $latestAllocation = Schema::hasTable('customer_payment_allocations')
        ? CustomerPaymentAllocation::query()->orderByDesc('id')->first()
        : null;
    $latestInvoiceUpdated = Invoice::query()->orderByDesc('updated_at')->first();

    return response()->json([
        'runtime' => [
            'app_env' => config('app.env'),
            'app_debug' => (bool) config('app.debug'),
            'config_cached' => app()->configurationIsCached(),
            'routes_cached' => app()->routesAreCached(),
            'config_cache_file_exists' => file_exists(base_path('bootstrap/cache/config.php')),
            'routes_cache_file_exists' => file_exists(base_path('bootstrap/cache/routes-v7.php')) || file_exists(base_path('bootstrap/cache/routes.php')),
        ],
        'db_env' => [
            'DB_CONNECTION' => env('DB_CONNECTION'),
            'DB_HOST' => env('DB_HOST'),
            'DB_PORT' => env('DB_PORT'),
            'DB_DATABASE' => env('DB_DATABASE'),
            'DB_USERNAME' => env('DB_USERNAME'),
        ],
        'db_config' => [
            'default' => config('database.default'),
            'connection_name' => $connName,
            'host' => $conn->getConfig('host'),
            'port' => $conn->getConfig('port'),
            'database' => $conn->getConfig('database'),
            'username' => $conn->getConfig('username'),
            'driver' => $conn->getConfig('driver'),
        ],
        'db_runtime' => [
            'database_select' => $databaseSelect,
            'connection_getDatabaseName' => $conn->getDatabaseName(),
        ],
        'counts' => [
            'invoices' => $invoicesCount,
            'customer_payments' => $paymentsCount,
        ],
        'latest' => [
            'payment' => $latestPayment ? [
                'id' => $latestPayment->id,
                'customer_id' => $latestPayment->customer_id,
                'amount' => (string) $latestPayment->amount,
                'payment_date' => (string) optional($latestPayment->payment_date)->toDateString(),
                'created_at' => (string) $latestPayment->created_at,
            ] : null,
            'allocation' => $latestAllocation ? [
                'id' => $latestAllocation->id,
                'customer_payment_id' => $latestAllocation->customer_payment_id,
                'invoice_id' => $latestAllocation->invoice_id,
                'amount' => (string) $latestAllocation->amount,
                'created_at' => (string) $latestAllocation->created_at,
            ] : null,
            'invoice_updated' => $latestInvoiceUpdated ? [
                'id' => $latestInvoiceUpdated->id,
                'invoice_no' => $latestInvoiceUpdated->invoice_no,
                'pending_amount' => (string) $latestInvoiceUpdated->pending_amount,
                'updated_at' => (string) $latestInvoiceUpdated->updated_at,
            ] : null,
        ],
        'drivers' => [
            'cache_default' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_default' => config('queue.default'),
        ],
        'opcache' => [
            'opcache_enable' => (bool) ini_get('opcache.enable'),
            'opcache_enable_cli' => (bool) ini_get('opcache.enable_cli'),
        ],
    ]);
});

Route::post('/debug/trae-event', function (Request $request) {
    abort_unless(app()->isLocal() || (bool) config('app.debug'), 404);

    $payload = $request->json()->all();
    if (!is_array($payload) || $payload === []) {
        $payload = $request->all();
    }
    if (!is_array($payload)) {
        $payload = [];
    }

    $payload['ts'] = $payload['ts'] ?? (int) (microtime(true) * 1000);
    $payload['sessionId'] = $payload['sessionId'] ?? 'invoice-autocomplete-stops';

    $outDir = base_path('.dbg');
    if (!is_dir($outDir)) {
        @mkdir($outDir, 0777, true);
    }
    $outFile = $outDir . DIRECTORY_SEPARATOR . 'trae-debug-log-invoice-autocomplete-stops.ndjson';
    @file_put_contents($outFile, json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);

    return response()->json(['ok' => true]);
})->name('debug.trae_event');

Route::middleware(['auth', EnsurePermission::class])
    ->prefix('accounts-v2')
    ->name('accounts-v2.')
    ->group(function () {
        Route::get('/', [AccountsV2Controller::class, 'index'])->name('index');
        Route::get('/customer/{customer}', [AccountsV2Controller::class, 'statement'])->name('customer.statement');
        Route::get('/customer/{customer}/receive-payment', [AccountsV2Controller::class, 'receivePayment'])->name('customer.receive-payment');
        Route::post('/customer/{customer}/receive-payment', [AccountsV2Controller::class, 'storeReceivePayment'])->name('customer.receive-payment.store');
        Route::get('/customer/{customer}/export.xlsx', [AccountsV2Controller::class, 'exportStatementExcel'])->name('customer.export.xlsx');
        Route::get('/customer/{customer}/export.csv', [AccountsV2Controller::class, 'exportStatementCsv'])->name('customer.export.csv');
    });

Route::middleware(['auth'])->prefix('salesman')->name('salesman.')->group(function () {
    Route::redirect('/', '/salesman/invoices');

    Route::get('/invoices', [InvoiceController::class, 'salesmanIndex'])->name('invoices.index');
    Route::get('/invoices/cash-summary', [InvoiceController::class, 'salesmanCashSummary'])->name('invoices.cashSummary');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{id}/print', [InvoiceController::class, 'print'])->name('invoices.print');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
