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
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ file_exists(public_path('css/style.css')) ? filemtime(public_path('css/style.css')) : '1' }}">

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

    @yield('pagestyles')

    <style>
        /* Force navbar items to layout horizontally and center on small screens */
        .line-menubar { display: none; }
        .logo-head-bar img { max-width: 150px; height: auto; }
        @media (max-width: 991px) {
            .container.navbar-custom-flex, .container-fluid.navbar-custom-flex { flex-direction: row !important; align-items: center !important; }
            .navbar-nav { display: flex !important; flex-direction: row !important; justify-content: center; width: 100%; flex-wrap:wrap; }
            .navbar-nav .nav-item { margin: 0 .5rem; }
            .top-menu { text-align: right; padding-right: 8px; }
            .visible-xs-inline-lookcrimlogo { display:block; margin: 0 auto; }
            /* Ensure collapse area uses full width and no right gap */
            .navbar-collapse.collapse { width: 100%; }
            .navbar-collapse { padding-left: 0.5rem; padding-right: 0.5rem; }
            body { overflow-x: hidden; }
        }
    </style>

</head>

<body>

<!-- Navigation -->

<div class="top-menu" id="ovoMenu">
    <div class="main-website-interior d-flex justify-content-end align-items-center">
    @if(Auth::check())

        <div class="dropdown" style="display:inline-block;margin-left:8px;margin-right:8px;">
            <a
                href="#"
                class="font-top-menu dropdown-toggle lc-user-menu-toggle"
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

                <a class="dropdown-item" href="{{ url('/profile') }}">{{ __('auth.profile') }}</a>

                <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="dropdown-item" style="background:none;border:none;width:100%;text-align:left;">@lang('auth.logout')</button>
                </form>
            </div>
        </div>

    @else
        <a href="{{ url('/login') }}" class="font-top-menu">@lang('auth.login')</a>
    @endif

    <a  href="{{ url('/lang/en') }}" class="font-top-menu lang" style="margin-left:12px;">EN</a>
   <div class="line-lang"></div>
    <a href="{{ url('/lang/pt') }}" class="font-top-menu lang" style="margin-left:8px;">PT</a>
    </div>
</div>

<nav class="navbar navbar-expand-lg navbar-light ">
    <div class="main-website-interior navbar-custom-flex d-flex flex-column flex-md-row justify-content-between">
        <a class="logo-head-bar" href="{{ url('/registers') }}">
            <img src="{{ asset('img/LookCrim-Logo1.png') }}" alt="LookCrim Logo 1" class="visible-xs-inline-lookcrimlogo" />
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav d-flex flex-row align-items-center w-100 navbar-main-list">
                <div class="line-menubar"></div>

                @php
                    $lcUser = auth()->user();
                    $lcCanViewPageRegisters = $lcUser && (
                        $lcUser->can('view_page_registers') ||
                        $lcUser->can('view_any_registers') || $lcUser->can('view_all_registers') ||
                        $lcUser->can('view_own_registers') ||
                        $lcUser->can('create_own_registers') || $lcUser->can('create_registers')
                    );
                    $lcCanViewRegisters = $lcUser && (
                        $lcUser->can('view_any_registers') || $lcUser->can('view_all_registers') ||
                        $lcUser->can('view_own_registers')
                    );
                    $lcCanCreateRegisters = $lcUser && ($lcUser->can('create_own_registers') || $lcUser->can('create_registers'));
                    $lcRegistersHref = ($lcCanViewPageRegisters && ($lcCanViewRegisters || $lcCanCreateRegisters))
                        ? ($lcCanViewRegisters ? url('/registers') : route('registers.create'))
                        : null;
                @endphp
                @if ($lcRegistersHref)
                    <li class="nav-item mx-1 {{ request()->is('registers*') || request()->is('map') ? 'active' : 'default' }}">
                        <a class="font-head-bar-black" href="{{ $lcRegistersHref }}">
                            <span class="font-head-bar-black-effect">@lang('layout.registers')</span>
                        </a>
                    </li>
                @endif

                @can('view_page_management')
                    <li class="nav-item mx-1 {{ request()->is('user/management') ? 'active' : 'default' }}">
                        <a class="font-head-bar-black lc-nav-hover-lookcrim" href="{{ url('/user/management') }}">
                            <span class="font-head-bar-black-effect">{{ __('auth.management') }}</span>
                        </a>
                    </li>
                @endcan

                @can('view_page_settings_roles')
                    <li class="nav-item mx-1 {{ request()->is('settings/roles*') ? 'active' : 'default' }}">
                        <a class="font-head-bar-black lc-nav-hover-lookcrim" href="{{ route('settings.roles.index') }}">
                            <span class="font-head-bar-black-effect">{{ __('pages.nav_page_settings') }}</span>
                        </a>
                    </li>
                @endcan

                @if((auth()->user()?->can('view_page_settings_city') ?? false) || (auth()->user()?->can('admin') ?? false))
                    <li class="nav-item mx-1 {{ request()->is('settings/city*') ? 'active' : 'default' }}">
                        <a class="font-head-bar-black lc-nav-hover-lookcrim" href="{{ route('settings.city.index') }}">
                            <span class="font-head-bar-black-effect">{{ __('pages.nav_city_settings') }}</span>
                        </a>
                    </li>
                @endif

                
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
    <div class="main-website-interior"></div>
</div>


<!-- Bootstrap / jQuery are bundled via Vite (resources/js/app.js). Keep only non-bundled vendor scripts here. -->
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

{{-- Snow effect (optional): set LC_ENABLE_SNOW=true in .env to enable. --}}
@php($lcEnableSnow = filter_var(env('LC_ENABLE_SNOW', false), FILTER_VALIDATE_BOOLEAN))
@if($lcEnableSnow)
    @include('partials.snow')
@endif

</body>
</html>
