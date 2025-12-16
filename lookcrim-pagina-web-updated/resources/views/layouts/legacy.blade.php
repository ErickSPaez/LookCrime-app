<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- Legacy / vendor CSS -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

    <!-- Local custom styles (legacy) -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @yield('pagestyles')

    <title>@yield('titulo_browser','LookCrim')</title>

    <link rel="icon" href="{{ asset('img/LookCrim-Logo1.png') }}"/>
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">

    {{-- Vite modern assets: prefer using @vite for new assets --}}
    @if (file_exists(public_path('build')))
        @vite(['resources/css/app.css','resources/js/app.js'])
    @else
        @vite(['resources/css/app.css','resources/js/app.js'])
    @endif

</head>

<body>

<!-- Navigation -->

<div class="top-menu" id="ovoMenu">

    @if(Auth::check())

        <div class="dropdown" style="display:inline-block;">
            <a
                href="#"
                class="font-top-menu dropdown-toggle"
                id="userMenuDropdown"
                data-bs-toggle="dropdown"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
                style="padding:0;"
                role="button"
            >
                <img
                    src="{{ asset('img/user-photo.jpg') }}"
                    alt="User"
                    style="height:24px;width:24px;border-radius:50%;object-fit:cover;vertical-align:middle;"
                />
            </a>

            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userMenuDropdown">
                <div class="dropdown-item" style="display:flex;align-items:center;gap:8px;white-space:normal;">
                    <img
                        src="{{ asset('img/user-photo.jpg') }}"
                        alt="User"
                        style="height:28px;width:28px;border-radius:50%;object-fit:cover;"
                    />
                    <div style="line-height:1.2;">
                        <div>{{ Auth::user()->name }} {{ Auth::user()->nickname }}</div>
                    </div>
                </div>

                <div class="dropdown-divider"></div>

                @if(Auth::user()->admin)
                    <a class="dropdown-item" href="{{ url('/user/management') }}">{{ __('auth.management') }}</a>
                @endif

                <a class="dropdown-item" href="{{ url('/profile') }}">{{ __('auth.profile') }}</a>

                <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="dropdown-item" style="background:none;border:none;width:100%;text-align:left;">@lang('auth.logout')</button>
                </form>
            </div>
        </div>

    @else
        @if(Auth::check() && Auth::user()->admin)

            <a href="{{ url('/user/management') }}" class="simple-a">
                <img src="{{ asset('img/logo-admin.png') }}" alt="Users Management Icon" class="visible-xs-inline-users-management-logo" />
            </a>
        @endif

        <a href="{{ url('/login') }}" class="font-top-menu">@lang('auth.login')</a>

    @endif

    <a  href="{{ url('/lang/en') }}" class="font-top-menu lang">EN</a>
   <div class="line-lang"></div>
    <a href="{{ url('/lang/pt') }}" class="font-top-menu lang">PT</a>

</div>

<nav class="navbar navbar-expand-lg navbar-light ">
    <div class="container d-flex flex-column flex-md-row justify-content-between">
        <a class="logo-head-bar" href="{{ url('/') }}">
            <img src="{{ asset('img/LookCrim-Logo1.png') }}" alt="LookCrim Logo 1" class="visible-xs-inline-lookcrimlogo" />
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav">
                <div class="line-menubar"></div>

                <li class="nav-item {{ request()->is('publications') ? 'active' : 'default' }}">
                    <a class="font-head-bar-black" href="{{ url('/publications') }}">
                        <span class="font-head-bar-black-effect">@lang('layout.publications')</span>
                    </a>
                </li>

                <!-- Dashboard link in main navbar (visible to all; protected by route middleware) -->
                <li class="nav-item {{ request()->is('dashboard') ? 'active' : 'default' }}">
                    <a class="font-head-bar-black" href="{{ url('/dashboard') }}">
                        <span class="font-head-bar-black-effect">Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>



<!-- Page Content -->
<div class="main-website">
    @yield('conteudo')
</div>
<!-- /.container -->



<!-- Footer -->
<div class="bg-lcred2">
    <div class="main-website-interior">
        <!-- Footer Links -->
        <div class="container-fluid text-center text-md-left">

            <!-- Grid row -->
            <div class="row">

                <!-- Grid column -->
                <div class="col-md-5 mt-md-7 ">

                    <!-- Content -->
                    <p class="font-weight-bold-white">@lang('layout.left-side-text-footer-1')
                        <a class="white-link" href="https://opvcufp.com/" target="_blank"> @lang('layout.left-side-text-footer-opvc') </a>
                        @lang('layout.left-side-text-footer-2')

                        <a class="white-link" href="https://www.ufp.pt/" target="_blank"> @lang('layout.left-side-text-footer-ufp') </a>
                    </p>

                </div>
                <!-- Grid column -->

                <hr class="clearfix w-100 d-md-none pb-3">

                <!-- Grid column -->
                <div class="col-md-3 mb-md-0 mb-3">
                    <div class="row">
                        <!-- Links -->
                        <h5 class="font-weight-bold-white-followus">@lang('layout.followus')</h5>

                        <!-- Grid column -->
                        <div class="col-md-12">
                            <div class="mb-5 flex-center">
                                <!-- Facebook -->
                                <a href="https://www.facebook.com/LookCrim/" target="_blank">
                                    <img src="{{ asset('img/FB.png') }}" alt="Facebook Icon" class="visible-xs-inline-fblogo" />
                                </a>

                            </div>
                        </div>
                        <!-- Grid column -->

                    </div>

                </div>
                <!-- Grid column -->

                <!-- Grid column -->
                <div class="col-md-4 mb-md-0 mb-3">

                    <!-- Content -->
                    <p class="font-weight-bold-white">@lang('layout.right-side-text-footer')</p>

                </div>
                <!-- Grid column -->

            </div>
            <!-- Grid row -->

        </div>
        <!-- Footer Links-->
    </div>
</div>


<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js" crossorigin="anonymous"></script>
<script src="{{ asset('js/jquery.put-delete.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<script src="{{ asset('js/tinymce/tinymce.min.js') }}"></script>

<script>
    (function () {
        function initUserMenuDropdown() {
            var trigger = document.getElementById('userMenuDropdown');
            if (!trigger) return;

            var dropdown = trigger.closest('.dropdown');
            if (!dropdown) return;

            var menu = dropdown.querySelector('.dropdown-menu');
            if (!menu) return;

            function close() {
                menu.classList.remove('show');
                trigger.setAttribute('aria-expanded', 'false');
            }

            function toggle(e) {
                if (e) e.preventDefault();
                var isOpen = menu.classList.contains('show');
                if (isOpen) {
                    close();
                } else {
                    menu.classList.add('show');
                    trigger.setAttribute('aria-expanded', 'true');
                }
            }

            trigger.addEventListener('click', toggle);

            document.addEventListener('click', function (e) {
                if (!dropdown.contains(e.target)) close();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') close();
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initUserMenuDropdown);
        } else {
            initUserMenuDropdown();
        }
    })();
</script>

<script>tinymce.init({ selector:'textarea',
        toolbar: "sizeselect | bold italic | fontselect | forecolor backcolor | fontsizeselect | link",
        theme: "silver",
        plugins: "link"
        });</script>

@yield('pagescripts')
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
 <script>
   AOS.init();
 </script>

</body>
</html>
