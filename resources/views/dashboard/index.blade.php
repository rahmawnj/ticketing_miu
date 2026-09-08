@extends('layouts.master')

@push('style')
<link href="{{ asset('/') }}plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet" />
<style>
    .dashboard-kpi-card {
        position: relative;
        overflow: hidden;
        border: 0;
        border-radius: 16px;
        box-shadow: 0 10px 24px rgba(20, 33, 61, 0.08);
        transition: transform .2s ease, box-shadow .2s ease;
        background: #fff;
    }
    .dashboard-kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 30px rgba(20, 33, 61, 0.14);
    }
    .dashboard-kpi-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 4px;
    }
    .dashboard-kpi-card::after {
        content: '';
        position: absolute;
        right: -30px;
        bottom: -35px;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        opacity: .08;
    }
    .kpi-renewal::before,
    .kpi-renewal .stats-icon { background: linear-gradient(135deg, #1590ff, #3e47ff); }
    .kpi-renewal::after { background: #1590ff; }
    .kpi-member::before,
    .kpi-member .stats-icon { background: linear-gradient(135deg, #ff8b1f, #ff4f6d); }
    .kpi-member::after { background: #ff8b1f; }
    .kpi-rental::before,
    .kpi-rental .stats-icon { background: linear-gradient(135deg, #00a86b, #11c9a5); }
    .kpi-rental::after { background: #00a86b; }
    .kpi-ticket::before,
    .kpi-ticket .stats-icon { background: linear-gradient(135deg, #7c4dff, #4f7bff); }
    .kpi-ticket::after { background: #7c4dff; }
    .dashboard-kpi-card .stats-content {
        padding-right: 86px;
    }
    .dashboard-kpi-card .stats-icon {
        width: 58px;
        height: 58px;
        line-height: 58px;
        border-radius: 14px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        top: 16px;
        right: 16px;
    }
    .dashboard-kpi-card .stats-title {
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: #5a6672 !important;
        margin-bottom: 8px;
    }
    .dashboard-kpi-card .stats-period {
        display: inline-block;
        margin-left: 6px;
        font-size: 10px;
        font-weight: 700;
        text-transform: none;
        letter-spacing: .2px;
        color: #24538a;
        background: #e9f3ff;
        border: 1px solid #cfe3ff;
        border-radius: 999px;
        padding: 2px 8px;
        vertical-align: middle;
    }
    .dashboard-kpi-card .stats-number {
        font-size: 30px;
        font-weight: 800;
        line-height: 1.1;
        color: #1d2734;
        margin-bottom: 12px;
    }
    .dashboard-kpi-card .stats-progress {
        margin: 0 0 12px;
        height: 2px;
        background: rgba(28, 44, 64, 0.08);
    }
    .dashboard-kpi-card .stats-count {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 700;
        color: #314257;
        background: #eef4ff;
        border: 1px solid #d9e7ff;
        border-radius: 999px;
        padding: 5px 11px;
    }
    @media (max-width: 767px) {
        .dashboard-kpi-card .stats-content {
            padding-right: 0;
        }
        .dashboard-kpi-card .stats-icon {
            position: relative;
            top: 0;
            right: 0;
            margin-bottom: 12px;
        }
        .dashboard-kpi-card .stats-number {
            font-size: 26px;
        }
    }
    .dashboard-chart-card {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(18, 34, 61, 0.10);
        background: #fff;
    }
    .dashboard-chart-head {
        padding: 18px 22px 14px;
        border-bottom: 1px solid #edf1f7;
        background: linear-gradient(135deg, #f7fbff 0%, #ffffff 65%);
    }
    .dashboard-chart-topbar {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }
    .dashboard-chart-title {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: #1d2a3a;
    }
    .dashboard-chart-subtitle {
        margin: 4px 0 0;
        font-size: 12px;
        color: #6c7b8a;
        font-weight: 600;
        letter-spacing: .2px;
    }
    .dashboard-chart-date {
        display: inline-flex;
        align-items: stretch;
        gap: 8px;
        white-space: nowrap;
    }
    .dashboard-filter-select,
    .dashboard-filter-input {
        border: 1px solid #cfe3ff;
        background: #fff;
        color: #1f3e66;
        font-size: 12px;
        font-weight: 700;
        border-radius: 999px;
        padding: 6px 10px;
        outline: none;
    }
    .dashboard-filter-select {
        min-width: 140px;
    }
    .dashboard-filter-input {
        min-width: 190px;
    }
    .dashboard-filter-select:focus,
    .dashboard-filter-input:focus {
        box-shadow: 0 0 0 2px rgba(47, 124, 255, 0.2);
    }
    .dashboard-filter-btn {
        border: 1px solid #cfe3ff;
        background: #eaf3ff;
        color: #275287;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 12px;
    }
    .dashboard-date-label {
        font-size: 12px;
        color: #4b6380;
        font-weight: 700;
        margin-top: 6px;
        text-align: right;
    }
    .dashboard-chart-body {
        padding: 16px 18px 20px;
    }
    .chart-wrap {
        position: relative;
        height: 360px;
        border: 1px solid #eef2f8;
        border-radius: 14px;
        background:
            radial-gradient(circle at 100% 0, rgba(63, 136, 255, 0.08), transparent 45%),
            linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        padding: 12px;
    }
    @media (max-width: 767px) {
        .dashboard-chart-head {
            padding: 14px 16px 12px;
        }
        .dashboard-chart-title {
            font-size: 15px;
        }
        .dashboard-chart-topbar {
            flex-direction: column;
            align-items: flex-start;
        }
        .dashboard-chart-body {
            padding: 12px;
        }
        .chart-wrap {
            height: 300px;
            padding: 8px;
        }
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="widget widget-stats text-inverse dashboard-kpi-card kpi-renewal">
            <div class="stats-icon stats-icon-square text-white"><i class="ion-ios-calculator"></i></div>
            <div class="stats-content">
                <div class="stats-title text-gray-700">Renewal <span class="stats-period">{{ $periodBadge }}</span></div>
                @if($dashboardMetricMode === 'count')
                <div class="stats-number">{{ number_format($renewalCount,0, ',','.') }}</div>
                @else
                <div class="stats-number">Rp {{ number_format($renewal,0, ',','.') }}</div>
                @endif
                <div class="stats-progress progress">
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="widget widget-stats text-inverse dashboard-kpi-card kpi-member">
            <div class="stats-icon stats-icon-square text-white"><i class="ion-ios-pricetags"></i></div>
            <div class="stats-content">
                <div class="stats-title text-gray-700">New Member <span class="stats-period">{{ $periodBadge }}</span></div>
                @if($dashboardMetricMode === 'count')
                <div class="stats-number">{{ number_format($newMemberCount,0, ',','.') }}</div>
                @else
                <div class="stats-number">Rp {{ number_format($newMember,0, ',','.') }}</div>
                @endif
                <div class="stats-progress progress">
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="widget widget-stats text-inverse dashboard-kpi-card kpi-rental">
            <div class="stats-icon stats-icon-square text-white"><i class="ion-ios-people"></i></div>
            <div class="stats-content">
                <div class="stats-title text-gray-700">Rental <span class="stats-period">{{ $periodBadge }}</span></div>
                @if($dashboardMetricMode === 'count')
                <div class="stats-number">{{ number_format($rentalCount,0, ',','.') }}</div>
                @else
                <div class="stats-number">Rp {{ number_format($rental,0, ',','.') }}</div>
                @endif
                <div class="stats-progress progress">
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="widget widget-stats text-inverse dashboard-kpi-card kpi-ticket">
            <div class="stats-icon stats-icon-square text-white"><i class="ion-ios-bookmark"></i></div>
            <div class="stats-content">
                <div class="stats-title text-gray-700">Ticket <span class="stats-period">{{ $periodBadge }}</span></div>
                @if($dashboardMetricMode === 'count')
                <div class="stats-number">{{ number_format($ticketCount,0, ',','.') }}</div>
                @else
                <div class="stats-number">Rp {{ number_format($ticket,0, ',','.') }}</div>
                @endif
                <div class="stats-progress progress">
                </div>
            </div>
        </div>
    </div>
    </div>
<div class="row">
    <div class="col-xl-12">
        <div class="card dashboard-chart-card">
            <div class="dashboard-chart-head">
                <div class="dashboard-chart-topbar">
                    <div>
                        <h4 class="dashboard-chart-title">{{ $chartTitle }}</h4>
                        <p class="dashboard-chart-subtitle">{{ $chartSubtitle }}</p>
                    </div>
                    <div>
                        <form method="GET" class="dashboard-chart-date" id="dashboard-filter-form">
                            <select name="period_type" id="period_type" class="dashboard-filter-select">
                                <option value="hourly" {{ $periodType === 'hourly' ? 'selected' : '' }}>Per Jam</option>
                                <option value="daily" {{ $periodType === 'daily' ? 'selected' : '' }}>Harian</option>
                                <option value="monthly" {{ $periodType === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                                <option value="yearly" {{ $periodType === 'yearly' ? 'selected' : '' }}>Tahunan</option>
                            </select>
                            <input type="text" id="period_value_display" class="dashboard-filter-input" autocomplete="off">
                            <select id="period_year_select" class="dashboard-filter-input d-none">
                                @php
                                    $currentYear = (int) \Carbon\Carbon::now('Asia/Jakarta')->format('Y');
                                    $selectedYear = (int) ($periodType === 'yearly' ? $periodValue : $currentYear);
                                @endphp
                                @for($year = $currentYear + 2; $year >= $currentYear - 10; $year--)
                                    <option value="{{ $year }}" {{ $selectedYear === $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                            <input type="hidden" name="period_value" id="period_value" value="{{ $periodValue }}">
                            <button type="submit" class="dashboard-filter-btn">Terapkan</button>
                        </form>
                        <div class="dashboard-date-label">{{ $todayLabel }}</div>
                    </div>
                </div>
            </div>
            <div class="dashboard-chart-body">
                <div class="chart-wrap">
                    <div id="bar-chart"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('script')
<script src="{{ asset('/') }}plugins/apexcharts/dist/apexcharts.min.js"></script>
<script src="{{ asset('/') }}plugins/moment/min/moment.min.js"></script>
<script src="{{ asset('/') }}plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
<script>
    const chartData = @json($chartData);
    const dashboardMetricMode = @json($dashboardMetricMode);
    const currentPeriodType = @json($periodType);
    const isCountMode = dashboardMetricMode === 'count';
    const formatNumberID = (value) => new Intl.NumberFormat('id-ID').format(Number(value || 0));
    const periodTypeEl = document.getElementById('period_type');
    const periodValueDisplayEl = document.getElementById('period_value_display');
    const periodYearSelectEl = document.getElementById('period_year_select');
    const periodValueEl = document.getElementById('period_value');
    let daterangeInitialized = false;

    function destroyDaterange() {
        if (daterangeInitialized && $(periodValueDisplayEl).data('daterangepicker')) {
            $(periodValueDisplayEl).data('daterangepicker').remove();
        }
        daterangeInitialized = false;
    }

    function setupPeriodInput() {
        const currentType = periodTypeEl.value;
        const currentValue = periodValueEl.value || '';
        destroyDaterange();
        periodValueDisplayEl.classList.remove('d-none');
        periodYearSelectEl.classList.add('d-none');

        if (currentType === 'daily') {
            periodValueDisplayEl.type = 'text';
            periodValueDisplayEl.placeholder = 'YYYY-MM-DD - YYYY-MM-DD';
            periodValueDisplayEl.readOnly = false;
            periodValueDisplayEl.value = currentValue || moment().format('YYYY-MM-DD') + ' - ' + moment().format('YYYY-MM-DD');

            $(periodValueDisplayEl).daterangepicker({
                autoUpdateInput: true,
                locale: { format: 'YYYY-MM-DD' },
                startDate: (periodValueDisplayEl.value.split(' - ')[0]) || moment().format('YYYY-MM-DD'),
                endDate: (periodValueDisplayEl.value.split(' - ')[1]) || moment().format('YYYY-MM-DD')
            });
            daterangeInitialized = true;
            return;
        }

        if (currentType === 'hourly') {
            periodValueDisplayEl.type = 'date';
            periodValueDisplayEl.readOnly = false;
            periodValueDisplayEl.value = currentValue || moment().format('YYYY-MM-DD');
            return;
        }

        if (currentType === 'monthly') {
            periodValueDisplayEl.type = 'month';
            periodValueDisplayEl.readOnly = false;
            periodValueDisplayEl.value = currentValue || moment().format('YYYY-MM');
            return;
        }

        periodValueDisplayEl.classList.add('d-none');
        periodYearSelectEl.classList.remove('d-none');
        periodYearSelectEl.value = currentValue || moment().format('YYYY');
    }

    document.getElementById('dashboard-filter-form').addEventListener('submit', function() {
        if (periodTypeEl.value === 'yearly') {
            periodValueEl.value = periodYearSelectEl.value || moment().format('YYYY');
        } else {
            periodValueEl.value = periodValueDisplayEl.value || '';
        }
    });

    periodTypeEl.addEventListener('change', setupPeriodInput);
    setupPeriodInput();
    const apexOptions = {
        chart: {
            type: 'bar',
            height: 336,
            stacked: false,
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: false,
                    zoomin: false,
                    zoomout: false,
                    pan: false,
                    reset: false
                },
                export: {
                    csv: {
                        filename: 'rekap-transaksi-' + (periodTypeEl.value || 'periode'),
                        columnDelimiter: ',',
                        headerCategory: @json($xAxisTitle),
                        headerValue: isCountMode ? 'Jumlah Transaksi' : 'Nominal'
                    },
                    png: {
                        filename: 'rekap-transaksi-' + (periodTypeEl.value || 'periode')
                    },
                    svg: {
                        filename: 'rekap-transaksi-' + (periodTypeEl.value || 'periode')
                    }
                }
            },
            fontFamily: 'inherit'
        },
        series: [
            { name: 'Renewal', data: chartData.renewal || [] },
            { name: 'New Member', data: chartData.new_member || [] },
            { name: 'Ticket', data: chartData.ticket || [] },
            { name: 'Transaksi Lainnya', data: chartData.rental || [] }
        ],
        colors: ['#22b8cf', '#ff922b', '#3b82f6', '#40c057'],
        plotOptions: {
            bar: {
                borderRadius: 8,
                columnWidth: '52%',
                endingShape: 'rounded'
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 1.5,
            colors: ['transparent']
        },
        xaxis: {
            categories: chartData.labels || [],
            title: {
                text: @json($xAxisTitle),
                style: {
                    color: '#506175',
                    fontWeight: 700
                }
            },
            labels: {
                formatter: function(value) {
                    if (currentPeriodType === 'daily' || currentPeriodType === 'monthly') {
                        return moment(value, 'YYYY-MM-DD').isValid()
                            ? moment(value, 'YYYY-MM-DD').format('DD/MM')
                            : value;
                    }
                    return value;
                },
                style: {
                    colors: '#5f7085'
                }
            }
        },
        yaxis: {
            min: 0,
            title: {
                text: isCountMode ? 'Jumlah Transaksi' : 'Amount (Rp)',
                style: {
                    color: '#506175',
                    fontWeight: 700
                }
            },
            labels: {
                formatter: function(value) {
                    return isCountMode
                        ? formatNumberID(value)
                        : 'Rp ' + formatNumberID(value);
                },
                style: {
                    colors: '#5f7085'
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'center',
            labels: {
                colors: '#425466'
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return isCountMode
                        ? formatNumberID(value)
                        : 'Rp ' + formatNumberID(value);
                }
            }
        },
        grid: {
            borderColor: 'rgba(125, 141, 163, 0.16)',
            strokeDashArray: 3
        }
    };

    const barChart = new ApexCharts(document.querySelector('#bar-chart'), apexOptions);
    barChart.render();
</script>
@endpush
