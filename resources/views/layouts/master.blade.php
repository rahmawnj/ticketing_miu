<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>miu ticketing system</title>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- ================== BEGIN core-css ================== -->
    <link href="{{ asset('/') }}css/vendor.min.css" rel="stylesheet" />
    <link href="{{ asset('/') }}css/apple/app.min.css" rel="stylesheet" />
    <link href="{{ asset('/') }}plugins/ionicons/css/ionicons.min.css" rel="stylesheet" />
    <!-- ================== END core-css ================== -->

    <!-- ================== BEGIN page-css ================== -->
    <link href="{{ asset('/') }}plugins/jvectormap-next/jquery-jvectormap.css" rel="stylesheet" />
    <link href="{{ asset('/') }}plugins/bootstrap-calendar/css/bootstrap_calendar.css" rel="stylesheet" />
    <link href="{{ asset('/') }}plugins/gritter/css/jquery.gritter.css" rel="stylesheet" />
    <link href="{{ asset('/') }}plugins/nvd3/build/nv.d3.css" rel="stylesheet" />
    <!-- ================== END page-css ================== -->

    @stack('style')
</head>

<body>
    <div id="app" class="app app-header-fixed app-sidebar-fixed">
        <div id="header" class="app-header">
            <div class="navbar-header">
                <a href="/dashboard" class="navbar-brand"><span class="navbar-logo"><i class="ion-ios-browsers"></i></span> <b>Membership</b></a>
                <button type="button" class="navbar-mobile-toggler" data-toggle="app-sidebar-mobile">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>

            <div class="navbar-nav">
                <div class="navbar-item navbar-user dropdown">
                    <a href="#" class="navbar-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                        <img src="{{ asset('/') }}img/user/user-13.jpg" alt="" />
                        <span>
                            <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                            <b class="caret"></b>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end me-1">
                        <a href="{{ route('profile') }}" class="dropdown-item">Edit Profile</a>
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="dropdown-item">Log Out</a>
                    </div>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>

        <div id="sidebar" class="app-sidebar">
            <div class="app-sidebar-content" data-scrollbar="true" data-height="100%">
                <div class="menu">
                    <div class="menu-profile">
                        <a href="javascript:;" class="menu-profile-link" data-toggle="app-sidebar-profile" data-target="#appSidebarProfileMenu">
                            <div class="menu-profile-cover with-shadow"></div>
                            <div class="menu-profile-image">
                                <img src="{{ asset('/') }}img/user/user-13.jpg" alt="" />
                            </div>
                            <div class="menu-profile-info">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        {{ auth()->user()->name }}
                                    </div>
                                    <div class="menu-caret ms-auto"></div>
                                </div>
                                <small>{{ auth()->user()->roles()->first()->name ?? '-' }}</small>
                            </div>
                        </a>
                    </div>
                    <div id="appSidebarProfileMenu" class="collapse">
                        <div class="menu-item pt-5px">
                            <a href="{{ route('profile') }}" class="menu-link">
                                <div class="menu-icon"><i class="fa fa-cog"></i></div>
                                <div class="menu-text">Edit Profile</div>
                            </a>
                        </div>
                        <div class="menu-item pb-5px">
                            <a href="{{ route('logout') }}" class="menu-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <div class="menu-icon"><i class="fa fa-sign-out-alt"></i></div>
                                <div class="menu-text"> Log Out</div>
                            </a>
                        </div>
                        <div class="menu-divider m-0"></div>
                    </div>

                    <div class="menu-header">Navigation</div>
                    <div class="menu-item {{ request()->is('dashboard') ? 'active' : '' }}">
                        <a href="/dashboard" class="menu-link">
                            <div class="menu-icon">
                                <i class="ion-ios-home bg-blue"></i>
                            </div>
                            <div class="menu-text">Dashboard</div>
                        </a>
                    </div>

                    @can('master-access')
                    <div class="menu-item {{ request()->is('users*') ||  request()->is('terusan*') || request()->is('tickets*') || request()->is('sewa*') || request()->is('memberships*') || request()->routeIs('membership-admin-fees.*') || request()->routeIs('gate-accesses.*') ? 'active' : '' }} has-sub">
                        <a href="javascript:;" class="menu-link">
                            <div class="menu-icon">
                                <i class="ion-ios-apps"></i>
                            </div>
                            <div class="menu-text">Data Master</div>
                            <div class="menu-caret"></div>
                        </a>
                        <div class="menu-submenu">
                            <div class="menu-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                @can('user-access')
                                <a href="{{ route('users.index') }}" class="menu-link">
                                    <div class="menu-text">Data User</div>
                                </a>
                                @endcan
                            </div>

                            {{-- <div class="menu-item {{ request()->routeIs('terusan.*') ? 'active' : '' }}">
                                @can('ticket-access')
                                <a href="{{ route('terusan.index') }}" class="menu-link">
                                    <div class="menu-text">Data Terusan</div>
                                </a>
                                @endcan
                            </div> --}}
                            <div class="menu-item {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                                @can('ticket-access')
                                <a href="{{ route('tickets.index') }}" class="menu-link">
                                    <div class="menu-text">Data Ticket</div>
                                </a>
                                @endcan
                            </div>

                            <div class="menu-item {{ request()->routeIs('membership-admin-fees.*') ? 'active' : '' }}">
                                @can('sewa-access')
                                <a href="{{ route('membership-admin-fees.index') }}" class="menu-link">
                                    <div class="menu-text">Jenis Admin</div>
                                </a>
                                @endcan
                            </div>

                            <div class="menu-item {{ request()->routeIs('sewa.*') ? 'active' : '' }}">
                                @can('sewa-access')
                                <a href="{{ route('sewa.index') }}" class="menu-link">
                                    <div class="menu-text">Data Lainnya</div>
                                </a>
                                @endcan
                            </div>

                            <div class="menu-item {{ request()->routeIs('memberships.*') ? 'active' : '' }}">
                                @can('sewa-access')
                                <a href="{{ route('memberships.index') }}" class="menu-link">
                                    <div class="menu-text">Data Membership</div>
                                </a>
                                @endcan
                            </div>

                            <div class="menu-item {{ request()->routeIs('gate-accesses.*') ? 'active' : '' }}">
                                <a href="{{ route('gate-accesses.index') }}" class="menu-link">
                                    <div class="menu-text">Data Dispenser</div>
                                </a>
                            </div>

                        </div>
                    </div>
                    @endcan

                    @can('member-access')
                    <div class="menu-item {{ (request()->routeIs('members.*') && !request()->routeIs('members.bulk_renew') && !request()->routeIs('members.process_bulk_renew')) ? 'active' : '' }}">
                        <a href="{{ route('members.index') }}" class="menu-link">
                            <div class="menu-icon">
                                <i class="ion-ios-people bg-green"></i>
                            </div>
                            <div class="menu-text">
                                Data Member
                                <span class="menu-label bg-danger fw-800">
                                    @php
                                        $setting = App\Models\Setting::asObject();
                                        $reminderDays = $setting->member_suspend_before_days ?? 7;
                                        $limitDate = Carbon\Carbon::now('Asia/Jakarta')->addDays($reminderDays)->format('Y-m-d');

                                        $count = App\Models\Member::where('tgl_expired', '<=', $limitDate)
                                            ->where('is_active', 1)
                                            ->where('parent_id', 0)
                                            ->count();
                                    @endphp
                                    {{ $count }}
                                </span>
                            </div>
                        </a>
                    </div>
                    @endcan

                    @can('transaction-access')
                    <div class="menu-item {{ (request()->is('transactions*') || request()->routeIs('members.bulk_renew') || request()->routeIs('members.process_bulk_renew')) ? 'active' : '' }}">
                        <a href="{{ route('transactions.index') }}" class="menu-link">
                            <div class="menu-icon">
                                <i class="ion-ios-stats bg-cyan"></i>
                            </div>
                            <div class="menu-text">Data Transaction</div>
                        </a>
                    </div>
                    @endcan
                    {{-- Menu Data Transaksi Lainnya disembunyikan --}}

                    @can('report-access')
                    <div class="menu-item {{ request()->is('report*') || request()->is('rekap*') || request()->is('history*') ? 'active' : '' }} has-sub">
                        <a href="javascript:;" class="menu-link">
                            <div class="menu-icon">
                                <i class="ion-ios-filing bg-gradient-cyan-blue"></i>
                            </div>
                            <div class="menu-text">Report</div>
                            <div class="menu-caret"></div>
                        </a>
                        <div class="menu-submenu">
                            @can('report-transaction-access')
                            <div class="menu-item">
                                <a href="{{ route('reports.transactions') }}" class="menu-link">
                                    <div class="menu-text">Report Transaction</div>
                                </a>
                            </div>
                            @endcan
                            @can('report-transaction-access')
                            <div class="menu-item">
                                <a href="{{ route('reports.ringkasan-transaksi') }}" class="menu-link">
                                    <div class="menu-text">Report Ringkasan</div>
                                </a>
                            </div>
                            @endcan
                            @can('report-transaction-access')
                            <div class="menu-item">
                                <a href="{{ route('rekap.transactions') }}" class="menu-link">
                                    <div class="menu-text">Rekap Transaction</div>
                                </a>
                            </div>
                            @endcan
                            @can('report-transaction-access')
                            <div class="menu-item">
                                <a href="{{ route('reports.penyewaan') }}" class="menu-link">
                                    <div class="menu-text">Report Transaksi Lainnya</div>
                                </a>
                            </div>
                            @endcan
                            {{-- Hidden menu: Rekap Transaksi Lainnya --}}
                            {{--
                            @can('report-transaction-access')
                            <div class="menu-item">
                                <a href="{{ route('rekap.penyewaan') }}" class="menu-link">
                                    <div class="menu-text">Rekap Transaksi Lainnya</div>
                                </a>
                            </div>
                            @endcan
                            --}}

                            {{-- Hidden menus: History Transaction / History Access Member / History Membership --}}
                        </div>
                    </div>
                    @endcan

                    @can('management-access')
                    <div class="menu-item {{ request()->is('roles*') || request()->is('permissions*') ? 'active' : '' }} has-sub">
                        <a href="javascript:;" class="menu-link">
                            <div class="menu-icon">
                                <i class="ion-ios-eye-off bg-danger"></i>
                            </div>
                            <div class="menu-text">Access Management</div>
                            <div class="menu-caret"></div>
                        </a>
                        <div class="menu-submenu">
                            <div class="menu-item {{ request()->is('roles*') ? 'active' : '' }}">
                                <a href="{{ route('roles.index') }}" class="menu-link">
                                    <div class="menu-text"> Role</div>
                                </a>
                            </div>

                            <div class="menu-item {{ request()->is('permissions*') ? 'active' : '' }}">
                                <a href="{{ route('permissions.index') }}" class="menu-link">
                                    <div class="menu-text"> Permission</div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="menu-item {{ request()->is('setting*') ? 'active' : '' }}">
                        <a href="{{ route('setting.index') }}" class="menu-link">
                            <div class="menu-icon">
                                <i class="ion-ios-cog bg-secondary"></i>
                            </div>
                            <div class="menu-text">Setting</div>
                        </a>
                    </div>
                    @endcan

                    <div class="menu-item d-flex">
                        <a href="javascript:;" class="app-sidebar-minify-btn ms-auto" data-toggle="app-sidebar-minify"><i class="ion-ios-arrow-back"></i>
                            <div class="menu-text">Collapse</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-sidebar-bg"></div>
        <div class="app-sidebar-mobile-backdrop"><a href="#" data-dismiss="app-sidebar-mobile" class="stretched-link"></a></div>

        <div id="content" class="app-content">

            <ol class="breadcrumb float-xl-end">
                @foreach($breadcrumbs as $breadcrumb)
                <li class="breadcrumb-item"><a href="javascript:;">{{ $breadcrumb }}</a></li>
                @endforeach
            </ol>

            <h1 class="page-header">{{ $title }}</h1>

            @yield('content')

        </div>

        <a href="javascript:;" class="btn btn-icon btn-circle btn-primary btn-scroll-to-top" data-toggle="scroll-to-top"><i class="fa fa-angle-up"></i></a>
    </div>

    <!-- ================== BEGIN core-js ================== -->
    <script src="{{ asset('/') }}js/vendor.min.js"></script>
    <script src="{{ asset('/') }}js/app.min.js"></script>
    <script src="{{ asset('/') }}js/theme/apple.min.js"></script>
    <!-- ================== END core-js ================== -->

    <!-- ================== BEGIN page-js ================== -->
    <script src="{{ asset('/') }}plugins/d3/d3.min.js"></script>
    <script src="{{ asset('/') }}plugins/nvd3/build/nv.d3.min.js"></script>
    <script src="{{ asset('/') }}plugins/jvectormap-next/jquery-jvectormap.min.js"></script>
    <script src="{{ asset('/') }}plugins/jvectormap-next/jquery-jvectormap-world-mill.js"></script>
    <script src="{{ asset('/') }}plugins/bootstrap-calendar/js/bootstrap_calendar.min.js"></script>
    <script src="{{ asset('/') }}js/demo/dashboard-v2.js"></script>
    <!-- ================== END page-js ================== -->
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    @stack('script')
</body>

</html>




