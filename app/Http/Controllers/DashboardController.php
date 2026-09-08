<?php

namespace App\Http\Controllers;

use App\Models\Penyewaan;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
public function index(Request $request)
{
    $title = 'Dashboard';
    $breadcrumbs = ['Dashboard'];
    $timezone = 'Asia/Jakarta';
    $now = Carbon::now($timezone);
    $periodType = strtolower((string) $request->get('period_type', 'hourly'));
    if (!in_array($periodType, ['hourly', 'daily', 'monthly', 'yearly'], true)) {
        $periodType = 'hourly';
    }

    $periodValue = trim((string) $request->get('period_value', ''));
    $startDate = $now->copy()->startOfDay();
    $endDate = $now->copy()->endOfDay();
    $todayLabel = '';
    $periodBadge = '';
    $chartTitle = '';
    $xAxisTitle = 'Jam';

    if ($periodType === 'hourly') {
        $selectedDate = $now->copy();
        if ($periodValue !== '') {
            try {
                $selectedDate = Carbon::createFromFormat('Y-m-d', $periodValue, $timezone);
            } catch (\Throwable $th) {
                $selectedDate = $now->copy();
            }
        }

        $startDate = $selectedDate->copy()->startOfDay();
        $endDate = $selectedDate->copy()->endOfDay();
        $periodValue = $selectedDate->format('Y-m-d');
        $todayLabel = $selectedDate->copy()->locale('id')->translatedFormat('l, d M Y');
        $periodBadge = 'Per Jam';
        $chartTitle = 'Rekap Transaksi ' . $todayLabel . ' (24 Jam)';
        $xAxisTitle = 'Jam';
    } elseif ($periodType === 'daily') {
        $rangeStart = $now->copy()->startOfDay();
        $rangeEnd = $now->copy()->endOfDay();

        if (preg_match('/^\d{4}-\d{2}-\d{2}\s-\s\d{4}-\d{2}-\d{2}$/', $periodValue) === 1) {
            try {
                [$startRaw, $endRaw] = explode(' - ', $periodValue);
                $rangeStart = Carbon::createFromFormat('Y-m-d', $startRaw, $timezone)->startOfDay();
                $rangeEnd = Carbon::createFromFormat('Y-m-d', $endRaw, $timezone)->endOfDay();
            } catch (\Throwable $th) {
                $rangeStart = $now->copy()->startOfDay();
                $rangeEnd = $now->copy()->endOfDay();
            }
        }

        if ($rangeStart->greaterThan($rangeEnd)) {
            [$rangeStart, $rangeEnd] = [$rangeEnd->copy()->startOfDay(), $rangeStart->copy()->endOfDay()];
        }

        $startDate = $rangeStart;
        $endDate = $rangeEnd;
        $periodValue = $rangeStart->format('Y-m-d') . ' - ' . $rangeEnd->format('Y-m-d');
        $todayLabel = $rangeStart->format('d/m/Y') . ' - ' . $rangeEnd->format('d/m/Y');
        $periodBadge = 'Harian';
        $chartTitle = 'Rekap Transaksi Harian';
        $xAxisTitle = 'Tanggal';
    } elseif ($periodType === 'monthly') {
        $selectedMonth = $now->copy()->startOfMonth();
        if ($periodValue !== '') {
            try {
                $selectedMonth = Carbon::createFromFormat('Y-m', $periodValue, $timezone)->startOfMonth();
            } catch (\Throwable $th) {
                $selectedMonth = $now->copy()->startOfMonth();
            }
        }

        $startDate = $selectedMonth->copy()->startOfMonth();
        $endDate = $selectedMonth->copy()->endOfMonth();
        $periodValue = $selectedMonth->format('Y-m');
        $todayLabel = $selectedMonth->copy()->locale('id')->translatedFormat('F Y');
        $periodBadge = 'Bulanan';
        $chartTitle = 'Rekap Transaksi Bulanan';
        $xAxisTitle = 'Tanggal';
    } else {
        $selectedYear = (int) $now->format('Y');
        if (preg_match('/^\d{4}$/', $periodValue) === 1) {
            $selectedYear = (int) $periodValue;
        }

        $startDate = Carbon::create($selectedYear, 1, 1, 0, 0, 0, $timezone);
        $endDate = Carbon::create($selectedYear, 12, 31, 23, 59, 59, $timezone);
        $periodValue = (string) $selectedYear;
        $todayLabel = 'Tahun ' . $selectedYear;
        $periodBadge = 'Tahunan';
        $chartTitle = 'Rekap Transaksi Tahunan';
        $xAxisTitle = 'Bulan';
    }

    $setting = Setting::asObject();
    $dashboardMetricMode = $setting->dashboard_metric_mode ?? 'amount';
    $isCountMode = $dashboardMetricMode === 'count';
    $chartSubtitle = $isCountMode
        ? 'Perbandingan jumlah transaksi per tipe pada periode terpilih'
        : 'Perbandingan nominal transaksi per tipe pada periode terpilih';

    $currentUser = auth()->user();
    $isAdmin = (int) ($currentUser?->roles()->first()?->id ?? 0) === 1;
    $baseTransactionQuery = Transaction::query()
        ->where('is_active', 1)
        ->whereBetween('created_at', [$startDate, $endDate]);

    if (!$isAdmin) {
        $baseTransactionQuery->where('user_id', (int) ($currentUser?->id ?? 0));
    }

    // Data summary berdasarkan periode terpilih.
    $renewal = (clone $baseTransactionQuery)->where('transaction_type', 'renewal')->sum('bayar');
    $renewalCount = (clone $baseTransactionQuery)->where('transaction_type', 'renewal')->count();
    $newMember = (clone $baseTransactionQuery)->where('transaction_type', 'registration')->sum('bayar');
    $newMemberCount = (clone $baseTransactionQuery)->where('transaction_type', 'registration')->count();
    $rental = (clone $baseTransactionQuery)->where('transaction_type', 'rental')->sum('bayar');
    $rentalCount = (clone $baseTransactionQuery)->where('transaction_type', 'rental')->count();
    $ticket = (clone $baseTransactionQuery)->where('transaction_type', 'ticket')->sum('bayar');
    $ticketCount = (clone $baseTransactionQuery)->where('transaction_type', 'ticket')->count();

    $bucketExpression = 'HOUR(created_at)';
    if ($periodType === 'daily' || $periodType === 'monthly') {
        $bucketExpression = 'DATE(created_at)';
    } elseif ($periodType === 'yearly') {
        $bucketExpression = 'MONTH(created_at)';
    }

    // 1. Ambil data chart berdasarkan bucket periode.
    $chartDataRaw = (clone $baseTransactionQuery)
        ->whereIn('transaction_type', ['rental', 'ticket', 'registration', 'renewal'])
        ->selectRaw(
            $isCountMode
                ? $bucketExpression . ' as bucket, transaction_type, COUNT(*) as total_value'
                : $bucketExpression . ' as bucket, transaction_type, SUM(bayar) as total_value'
        )
        ->groupBy('bucket', 'transaction_type')
        ->orderBy('bucket', 'asc')
        ->get();

    $chartLabels = [];
    $data_renewal = [];
    $data_new_member = [];
    $data_rental = [];
    $data_ticket = [];
    $formattedHours = [];

    if ($periodType === 'hourly') {
        for ($hour = 0; $hour < 24; $hour++) {
            $formattedHourKey = (string) $hour;
            $chartLabels[] = sprintf('%02d:00', $hour);
            $formattedHours[$formattedHourKey] = [
                'renewal' => 0,
                'new_member' => 0,
                'rental' => 0,
                'ticket' => 0,
            ];
        }
    } elseif ($periodType === 'yearly') {
        for ($month = 1; $month <= 12; $month++) {
            $key = (string) $month;
            $chartLabels[] = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('M');
            $formattedHours[$key] = [
                'renewal' => 0,
                'new_member' => 0,
                'rental' => 0,
                'ticket' => 0,
            ];
        }
    } else {
        $loopDate = $startDate->copy()->startOfDay();
        while ($loopDate->lessThanOrEqualTo($endDate)) {
            $key = $loopDate->format('Y-m-d');
            // Gunakan format ISO agar export CSV tidak salah dibaca Excel (mm/dd).
            $chartLabels[] = $key;
            $formattedHours[$key] = [
                'renewal' => 0,
                'new_member' => 0,
                'rental' => 0,
                'ticket' => 0,
            ];
            $loopDate->addDay();
        }
    }

    // 2. Inisialisasi data untuk 4 kategori utama per bucket.
    foreach ($formattedHours as $key => $value) {
        $formattedHours[$key] = [
            'renewal' => 0,
            'new_member' => 0,
            'rental' => 0,
            'ticket' => 0,
        ];
    }

    // 3. Isi array data dari hasil database berdasarkan tipe transaksi per bucket.
    foreach ($chartDataRaw as $item) {
        $hourKey = (string) $item->bucket;
        $type = $item->transaction_type;
        $value = (int) $item->total_value;

        if (isset($formattedHours[$hourKey])) {
            if ($type === 'renewal') {
                $formattedHours[$hourKey]['renewal'] = $value;
            } elseif ($type === 'registration') {
                $formattedHours[$hourKey]['new_member'] = $value;
            } elseif ($type === 'rental') {
                $formattedHours[$hourKey]['rental'] = $value;
            } elseif ($type === 'ticket') {
                $formattedHours[$hourKey]['ticket'] = $value;
            }
        }
    }

    // 4. Ekstrak data ke array yang siap untuk Chart.js
    foreach ($formattedHours as $data) {
        $data_renewal[] = $data['renewal'];
        $data_new_member[] = $data['new_member'];
        $data_rental[] = $data['rental'];
        $data_ticket[] = $data['ticket'];
    }

    // 5. Susun array chartData baru
    $chartData = [
        'labels' => $chartLabels,
        'renewal' => $data_renewal,
        'new_member' => $data_new_member,
        'rental' => $data_rental,
        'ticket' => $data_ticket,
    ];

    return view('dashboard.index', compact(
        'title',
        'breadcrumbs',
        'renewal',
        'renewalCount',
        'newMember',
        'newMemberCount',
        'rental',
        'rentalCount',
        'ticket',
        'ticketCount',
        'todayLabel',
        'periodBadge',
        'chartTitle',
        'chartSubtitle',
        'dashboardMetricMode',
        'periodType',
        'periodValue',
        'xAxisTitle',
        'chartData'
    ));
}
    public function profile()
    {
        $title = 'Edit Profile';
        $breadcrumbs = ['Edit Profile'];
        $user = User::find(auth()->user()->id);

        return view('dashboard.profile', compact('title', 'breadcrumbs', 'user'));
    }

    public function update(Request $request)
    {
        try {
            $user = User::find(auth()->user()->id);

            $request->validate([
                'username' => 'required|string|unique:users,username,' . $user->id,
                'name' => 'required|string',
            ]);

            DB::beginTransaction();

            if ($request->password) {
                $password = bcrypt($request->password);
            } else {
                $password = $user->password;
            }

            $user->update([
                'username' => $request->username,
                'name' => $request->name,
                'password' => $password,
            ]);

            DB::commit();

            return back()->with('success', "Profile berhasil diupdate");
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
