<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DetailTransactionController;
use App\Http\Controllers\GateAccessController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HistoryMembershipController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MembershipAdminFeeController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\PenyewaanController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SecretSettingController;
use App\Http\Controllers\SewaController;
use App\Http\Controllers\TerusanController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TopupController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/storage/{filename}', function ($filename) {
    $path = storage_path('app/public/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return response($file)->header('Content-Type', $type);
})->where('filename', '.*');

Route::get('/', function () {
    return view('auth.login');
})->middleware('guest');

Route::get('secret-setting', [SecretSettingController::class, 'index'])->name('secret-setting.index');
Route::post('secret-setting', [SecretSettingController::class, 'store'])->name('secret-setting.update');

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('profile', [DashboardController::class, 'profile'])->name('profile');
    Route::post('profile', [DashboardController::class, 'update'])->name('profile.update');

    Route::get('users/get', [UserController::class, 'get'])->name('users.list');
    Route::resource('users', UserController::class);

    Route::get('gate-accesses/get', [GateAccessController::class, 'get'])->name('gate-accesses.list');
    Route::resource('gate-accesses', GateAccessController::class);

    Route::get('tickets/get', [TicketController::class, 'get'])->name('tickets.list');
    Route::get('tickets/find/{ticket:id}', [TicketController::class, 'find'])->name('tickets.find');
    Route::resource('tickets', TicketController::class);

    Route::get('terusan/get', [TerusanController::class, 'get'])->name('terusan.list');
    Route::resource('terusan', TerusanController::class);

    Route::get('sewa/get', [SewaController::class, 'get'])->name('sewa.list');
    Route::resource('sewa', SewaController::class);

    Route::get('members/get', [MemberController::class, 'get'])->name('members.list');
    Route::get('members/export', [MemberController::class, 'export'])->name('members.export');
    Route::get("member/download/example-import", [MemberController::class, 'download'])->name('members.download');
    Route::get('members/{member:id}/print-qr', [MemberController::class, 'print_qr'])->name('members.printqr');
    Route::post('member/{member:id}/expired', [MemberController::class, 'expired'])->name('members.expired');
    Route::get('members/{member:id}/invoice', [MemberController::class, 'invoice'])->name('members.invoice');
    Route::get('members/{member:id}/invoice-pdf', [MemberController::class, 'invoicePdf'])->name('members.invoice.pdf');
    Route::get('transactions/{transaction}/invoice', [TransactionController::class, 'invoice'])->name('transactions.invoice');
    Route::get('transactions/{transaction}/invoice-pdf', [TransactionController::class, 'invoicePdf'])->name('transactions.invoice.pdf');
    Route::get('transactions/{transaction:id}/invoice-pdf-file', [TransactionController::class, 'invoicePdfFile'])->name('transactions.invoice.pdf_file');
    Route::post('update-setting', [MemberController::class, 'update_setting'])->name('setting.update');
    Route::post('import', [MemberController::class, 'import'])->name('member.import');
    Route::get('members/bulk-renew', [MemberController::class, 'bulkRenewIndex'])->name('members.bulk_renew');
    Route::post('members/bulk-renew', [MemberController::class, 'processBulkRenew'])->name('members.process_bulk_renew');
    Route::get('members/renewable-list', [MemberController::class, 'getRenewableMembers'])->name('members.get_renewable');
    Route::get('members/{member:id}/renewal-notice/{format}', [MemberController::class, 'downloadSingleRenewalNotice'])
        ->where('format', 'preview|pdf')
        ->name('members.download_renewal_notice_member');
    Route::post('members/renewal-notice/download', [MemberController::class, 'downloadRenewalNotice'])->name('members.download_renewal_notice');
    Route::resource('members', MemberController::class);

    Route::get('memberships/get', [MembershipController::class, 'get'])->name('memberships.list');
    Route::resource('memberships', MembershipController::class);

    Route::get('membership-admin-fees/get', [MembershipAdminFeeController::class, 'get'])->name('membership-admin-fees.list');
    Route::resource('membership-admin-fees', MembershipAdminFeeController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy'])
        ->parameters(['membership-admin-fees' => 'membershipAdminFee']);

    Route::get('transactions/get', [TransactionController::class, 'get'])->name('transactions.list');
    Route::get('transactions/{transaction}/scan-details', [TransactionController::class, 'scanDetails'])->name('transactions.scan_details');
    Route::get('transactions/export-daily', [TransactionController::class, 'exportDaily'])->name('transactions.export.daily');
    Route::get('transactions/{transaction:id}/print', [TransactionController::class, 'print'])->name('transactions.print');
    Route::get('transactions/{transaction:id}/ticket-pdf', [TransactionController::class, 'ticketPdf'])->name('transactions.ticket.pdf');
    Route::get('transaction/create', [DetailTransactionController::class, 'store']);
    Route::post('transactions/{transaction}/full-scan', [TransactionController::class, 'setFullScan'])->name('transactions.set_full_scan');
    Route::resource('transactions', TransactionController::class);



    Route::get('detail/{id}/list', [DetailTransactionController::class, 'index'])->name('detail.list');
    Route::delete('detail/session/{rowId}', [DetailTransactionController::class, 'destroySession'])->name('detail.destroy.session');
    Route::delete('detail/{detailTransaction:id}', [DetailTransactionController::class, 'destroy'])->name('detail.destroy');
    Route::post('detail/{id}/save', [DetailTransactionController::class, 'save'])->name('detail.save');
    Route::get('detail/{detailTransaction:id}/remove', [DetailTransactionController::class, 'remove'])->name('detail.remove');
    Route::get('/detail/qty', [DetailTransactionController::class, 'qty'])->name('detail.qty');

    Route::get('penyewaan/get', [PenyewaanController::class, 'get'])->name('penyewaan.list');
    Route::get('penyewaan/{id}/print', [PenyewaanController::class, 'print'])->name('penyewaan.print');
    Route::get('penyewaan/{id}/print-pdf', [PenyewaanController::class, 'printPdf'])->name('penyewaan.print.pdf');
    Route::resource('penyewaan', PenyewaanController::class);

    Route::get('topup/get', [TopupController::class, 'get'])->name('topup.list');
    Route::resource('topup', TopupController::class);

    Route::get('roles/get', [RoleController::class, 'get'])->name('roles.list');
    Route::resource('roles', RoleController::class);

    Route::get('permissions/get', [PermissionController::class, 'get'])->name('permissions.list');
    Route::resource('permissions', PermissionController::class);

    Route::get('report/transactions', [ReportController::class, 'transaction'])->name('reports.transactions');
    Route::get('report/ringkasan-transaksi', [ReportController::class, 'ringkasanTransaksi'])->name('reports.ringkasan-transaksi');
    Route::get('report/ringkasan-transaksi/export', [ReportController::class, 'exportRingkasanTransaksi'])->name('reports.ringkasan-transaksi.export');
    Route::get('report/transactions-list', [ReportController::class, 'transactionList'])->name('reports.transaction-list');
    Route::get('/report/transactions/export', [ReportController::class, 'export_transaction'])->name('report.transaction.export');
    Route::get('/report/transactions/export-txt', [ReportController::class, 'export_transaction_txt'])->name('report.transaction.export.txt');
    Route::get('rekap/transactions', [ReportController::class, 'rekapTransaction'])->name('rekap.transactions');
    Route::get('export-transaction', [ReportController::class, 'exportTransaction'])->name('transactions.export');
    Route::get('print-transaction', [ReportController::class, 'printTransaction'])->name('transactions.download');
    Route::get('report/penyewaan', [ReportController::class, 'penyewaan'])->name('reports.penyewaan');
    Route::get('report/penyewaan-list', [ReportController::class, 'penyewaanList'])->name('reports.penyewaan-list');
    Route::get('/report/penyewaan/export', [ReportController::class, 'export_penyewaan'])->name('report.penyewaan.export');
    Route::get('rekap/penyewaan', [ReportController::class, 'rekapPenyewaan'])->name('rekap.penyewaan');
    Route::get('export-penyewaan', [ReportController::class, 'exportPenyewaan'])->name('penyewaan.export');
    Route::get('print-penyewaan', [ReportController::class, 'printPenyewaan'])->name('penyewaan.download');

    Route::get('history-transactions', [HistoryController::class, 'transaction'])->name('history-transactions.index');
    Route::get('history-transactions/list', [HistoryController::class, 'transaction_list'])->name('history-transactions.list');
    Route::get('history-member', [HistoryController::class, 'index'])->name('history-member.index');
    Route::get('history-member/list', [HistoryController::class, 'list'])->name('history-member.list');
    Route::get('history-member/print', [HistoryController::class, 'print_member'])->name('history-member.print');
    Route::get('history-member/export', [HistoryController::class, 'export_member'])->name('history-member.export');
    Route::get('history-karyawan', [HistoryController::class, 'karyawan'])->name('history-karyawan.index');
    Route::get('history-karyawan/list', [HistoryController::class, 'list_karyawan'])->name('history-karyawan.list');
    Route::get('history-karyawan/print', [HistoryController::class, 'print_karyawan'])->name('history-karyawan.print');
    Route::get('history-karyawan/export', [HistoryController::class, 'export_karyawan'])->name('history-karyawan.export');

    Route::get('history-memberships', [HistoryMembershipController::class, 'index'])->name('history-memberships.index');
    Route::get('history-memberships/list', [HistoryMembershipController::class, 'list'])->name('history-memberships.list');

    Route::get('setting', [SettingController::class, 'index'])->name('setting.index');
    Route::post('setting', [SettingController::class, 'store'])->name('setting.store');
});

Route::get('/detail-group', [ApiController::class, 'detailGroup']);
Route::get('/welcome-member', function () {
    return view('member');
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('reset', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('optimize:clear');
});

Route::get('test-print', [DetailTransactionController::class, 'testPrint']);
Route::get('test-pdf', [DetailTransactionController::class, 'print']);
