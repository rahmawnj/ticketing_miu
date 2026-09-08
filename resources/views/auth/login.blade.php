<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>{{ config('app.name', 'E-ticket') }} | Login</title>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />

    <!-- ================== BEGIN core-css ================== -->
    <link href="{{ asset('/') }}css/vendor.min.css" rel="stylesheet" />
    <link href="{{ asset('/') }}css/apple/app.min.css" rel="stylesheet" />
    <link href="{{ asset('/') }}plugins/ionicons/css/ionicons.min.css" rel="stylesheet" />
    <!-- ================== END core-css ================== -->
    <style>
        .login.login-with-news-feed .news-caption {
            top: 5px;
            bottom: auto;
        }
        .login.login-with-news-feed .news-caption .news-logo {
            width: 220px;
            max-width: 70%;
            height: auto;
            display: block;
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.25));
        }
        @media (max-width: 767.98px) {
            .login.login-with-news-feed .news-caption {
                top: 18px;
            }
            .login.login-with-news-feed .news-caption .news-logo {
                width: 170px;
            }
        }
    </style>
</head>

<body class='pace-top'>
    <!-- BEGIN #loader -->
    <div id="loader" class="app-loader">
        <span class="spinner"></span>
    </div>
    <!-- END #loader -->

    <!-- BEGIN #app -->
    <div id="app" class="app">
        <!-- BEGIN login -->
        <div class="login login-with-news-feed">
            <!-- BEGIN news-feed -->
            <div class="news-feed">
                <div class="news-image" style="background-image: url({{ asset('/') }}img/login-bg/login-bg-water.jpg)"></div>
                <div class="news-caption">
                    <img class="news-logo" src="{{ asset('img/logo/logo_landscape.png') }}" alt="MIU Ticketing Logo">
                </div>
            </div>
            <!-- END news-feed -->

            <!-- BEGIN login-container -->
            <div class="login-container">
                <!-- BEGIN login-header -->
                <div class="login-header mb-30px">
                    <div class="brand">
                        <div class="d-flex align-items-center">
                            <span class="logo"><i class="ion-ios-lock"></i></span>
                            <b>MIU Ticketing</b>
                        </div>
                        <small>Login dengan akun anda.</small>
                    </div>
                    <div class="icon">
                        <i class="fa fa-sign-in-alt"></i>
                    </div>
                </div>
                <!-- END login-header -->

                <!-- BEGIN login-content -->
                <div class="login-content">
                    <form action="{{ route('login') }}" method="POST" class="fs-13px">
                        @csrf
                        <div class="form-floating mb-15px">
                            <input type="text" class="form-control h-45px fs-13px" placeholder="Username" id="username" name="username" />
                            <label for="username" class="d-flex align-items-center fs-13px text-gray-600">Username</label>

                            @error('username')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-floating mb-15px">
                            <input type="password" class="form-control h-45px fs-13px" placeholder="Password" id="password" name="password" />
                            <label for="password" class="d-flex align-items-center fs-13px text-gray-600">Password</label>

                            @error('password')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-15px text-center">
                            <button type="submit" class="btn btn-primary mb-3 d-block h-45px w-100 btn-lg fs-14px">Sign me in</button>

                        </div>
                    </form>
                </div>
                <!-- END login-content -->
            </div>
            <!-- END login-container -->
        </div>
        <!-- END login -->

        <!-- BEGIN scroll-top-btn -->
        <a href="javascript:;" class="btn btn-icon btn-circle btn-primary btn-scroll-to-top" data-toggle="scroll-to-top"><i class="fa fa-angle-up"></i></a>
        <!-- END scroll-top-btn -->
    </div>
    <!-- END #app -->

    <!-- ================== BEGIN core-js ================== -->
    <script src="{{ asset('/') }}js/vendor.min.js"></script>
    <script src="{{ asset('/') }}js/app.min.js"></script>
    <script src="{{ asset('/') }}js/theme/apple.min.js"></script>
    <!-- ================== END core-js ================== -->
</body>

</html>
