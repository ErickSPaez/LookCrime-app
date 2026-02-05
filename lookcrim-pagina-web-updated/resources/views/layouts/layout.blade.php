<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

    <!-- Custom styles for this template -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @yield('pagestyles')

    <title>@yield('titulo_browser','LookCrim')</title>

    <link rel="icon" href="{!! asset('img/LookCrim-Logo1.png') !!}"/>
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">


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

                <a class="dropdown-item" href="{{ url('/profile') }}">{{ __('auth.profile') }}</a>

                <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="dropdown-item" style="background:none;border:none;width:100%;text-align:left;">@lang('auth.logout')</button>
                </form>
            </div>
        </div>

    @else
        <a href="{{url('/login')}}" class="font-top-menu">@lang('auth.login')</a>
    @endif

    <a  href="{{url('/lang/en')}}" class="font-top-menu lang">EN</a>
   <div class="line-lang"></div>
    <a href="{{url('/lang/pt')}}" class="font-top-menu lang">PT</a>

</div>

<nav class="navbar navbar-expand-lg navbar-light ">
    <div class="container d-flex flex-column flex-md-row justify-content-between">
            <a class="logo-head-bar" href="/">
            <img src="{{ asset('img/LookCrim-Logo1.png') }}" alt="LookCrim Logo 1" class="visible-xs-inline-lookcrimlogo" />
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav">
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
                    <li class="nav-item {{ request()->is('registers*') || request()->is('map') ? 'active' : 'default' }}">
                        <a class="font-head-bar-black" href="{{ $lcRegistersHref }}">
                            <span class="font-head-bar-black-effect">@lang('layout.registers')</span>
                        </a>
                    </li>
                @endif

                    @can('view_page_management')
                    <li class="nav-item {{ request()->is('user/management') ? 'active' : 'default' }}">
                        <a class="font-head-bar-black" href="{{ url('/user/management') }}">
                            <span class="font-head-bar-black-effect">{{ __('auth.management') }}</span>
                        </a>
                    </li>
                @endcan

                    @can('view_page_settings_roles')
                    <li class="nav-item {{ request()->is('settings/roles*') ? 'active' : 'default' }}">
                        <a class="font-head-bar-black" href="{{ route('settings.roles.index') }}">
                            <span class="font-head-bar-black-effect">{{ __('pages.nav_page_settings') }}</span>
                        </a>
                    </li>
                @endcan
                        @if((auth()->user()?->can('view_page_settings_city') ?? false) || (auth()->user()?->can('admin') ?? false))
                            <li class="nav-item">
                                <a class="font-head-bar-black" href="{{ route('settings.city.index') }}" style="margin-left:12px;">
                                    {{ __('pages.nav_city_settings') }}
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
    <div class="main-website-interior">
        <!-- Footer Links -->
        <div class="container-fluid text-center text-md-left">

            <!-- Grid row -->
            <div class="row">

                <!-- Grid column -->
                <div class="col-md-5 mt-md-7 ">

                    <!-- Content (removed — reserved for sponsors) -->
                    <p class="font-weight-bold-white">&nbsp;</p>

                </div>
                <!-- Grid column -->

                <hr class="clearfix w-100 d-md-none pb-3">

                <!-- Grid column -->
                <div class="col-md-3 mb-md-0 mb-3">
                    <div class="row">
                        <!-- Links (text removed) -->
                        <h5 class="font-weight-bold-white-followus">&nbsp;</h5>

                        <!-- Grid column -->
                        <div class="col-md-12">
                            <div class="mb-5 flex-center">
                                <!-- Facebook icon removed -->
                            </div>
                        </div>
                        <!-- Grid column -->

                    </div>

                </div>
                <!-- Grid column -->

                <!-- Grid column -->
                <div class="col-md-4 mb-md-0 mb-3">

                    <!-- Content (removed — reserved for sponsors) -->
                    <p class="font-weight-bold-white">&nbsp;</p>

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


<!-- Neve, enfeite para época de Inverno -->
<!--
<style>
    #snowflakeContainer {
        position: absolute;
        left: 0px;
        top: 0px;
        display: none;
    }

    .snowflake {
        position: fixed;
        background-color: #FFFFFF;
        user-select: none;
        z-index: 1000;
        pointer-events: none;
        border-radius: 50%;
        width: 10px;
        height: 10px;
    }
</style>

<div id="snowflakeContainer">
    <span class="snowflake"></span>
</div>

<script>
    // Array to store our Snowflake objects
    var snowflakes = [];

    // Global variables to store our browser's window size
    var browserWidth;
    var browserHeight;

    // Specify the number of snowflakes you want visible
    var numberOfSnowflakes = 200;

    // Flag to reset the position of the snowflakes
    var resetPosition = false;

    // Handle accessibility
    var enableAnimations = false;
    var reduceMotionQuery = matchMedia("(prefers-reduced-motion)");

    // Handle animation accessibility preferences
    function setAccessibilityState() {
        if (reduceMotionQuery.matches) {
            enableAnimations = false;
        } else {
            enableAnimations = true;
        }
    }
    setAccessibilityState();

    reduceMotionQuery.addListener(setAccessibilityState);

    //
    // It all starts here...
    //
    function setup() {
        if (enableAnimations) {
            window.addEventListener("DOMContentLoaded", generateSnowflakes, false);
            window.addEventListener("resize", setResetFlag, false);
        }
    }
    setup();

    //
    // Constructor for our Snowflake object
    //
    function Snowflake(element, speed, xPos, yPos) {
        // set initial snowflake properties
        this.element = element;
        this.speed = speed;
        this.xPos = xPos;
        this.yPos = yPos;
        this.scale = 1;

        // declare variables used for snowflake's motion
        this.counter = 0;
        this.sign = Math.random() < 0.5 ? 1 : -1;

        // setting an initial opacity and size for our snowflake
        this.element.style.opacity = (.1 + Math.random()) / 3;
    }

    //
    // The function responsible for actually moving our snowflake
    //
    Snowflake.prototype.update = function () {
        // using some trigonometry to determine our x and y position
        this.counter += this.speed / 5000;
        this.xPos += this.sign * this.speed * Math.cos(this.counter) / 40;
        this.yPos += Math.sin(this.counter) / 40 + this.speed / 30;
        this.scale = .5 + Math.abs(10 * Math.cos(this.counter) / 20);

        // setting our snowflake's position
        setTransform(Math.round(this.xPos), Math.round(this.yPos), this.scale, this.element);

        // if snowflake goes below the browser window, move it back to the top
        if (this.yPos > browserHeight) {
            this.yPos = -50;
        }
    }

    //
    // A performant way to set your snowflake's position and size
    //
    function setTransform(xPos, yPos, scale, el) {
        el.style.transform = `translate3d(${xPos}px, ${yPos}px, 0) scale(${scale}, ${scale})`;
    }

    //
    // The function responsible for creating the snowflake
    //
    function generateSnowflakes() {

        // get our snowflake element from the DOM and store it
        var originalSnowflake = document.querySelector(".snowflake");

        // access our snowflake element's parent container
        var snowflakeContainer = originalSnowflake.parentNode;
        snowflakeContainer.style.display = "block";

        // get our browser's size
        browserWidth = document.documentElement.clientWidth;
        browserHeight = document.documentElement.clientHeight;

        // create each individual snowflake
        for (var i = 0; i < numberOfSnowflakes; i++) {

            // clone our original snowflake and add it to snowflakeContainer
            var snowflakeClone = originalSnowflake.cloneNode(true);
            snowflakeContainer.appendChild(snowflakeClone);

            // set our snowflake's initial position and related properties
            var initialXPos = getPosition(50, browserWidth);
            var initialYPos = getPosition(50, browserHeight);
            var speed = 5 + Math.random() * 40;

            // create our Snowflake object
            var snowflakeObject = new Snowflake(snowflakeClone,
                speed,
                initialXPos,
                initialYPos);
            snowflakes.push(snowflakeObject);
        }

        // remove the original snowflake because we no longer need it visible
        snowflakeContainer.removeChild(originalSnowflake);

        moveSnowflakes();
    }

    //
    // Responsible for moving each snowflake by calling its update function
    //
    function moveSnowflakes() {

        if (enableAnimations) {
            for (var i = 0; i < snowflakes.length; i++) {
                var snowflake = snowflakes[i];
                snowflake.update();
            }
        }

        // Reset the position of all the snowflakes to a new value
        if (resetPosition) {
            browserWidth = document.documentElement.clientWidth;
            browserHeight = document.documentElement.clientHeight;

            for (var i = 0; i < snowflakes.length; i++) {
                var snowflake = snowflakes[i];

                snowflake.xPos = getPosition(50, browserWidth);
                snowflake.yPos = getPosition(50, browserHeight);
            }

            resetPosition = false;
        }

        requestAnimationFrame(moveSnowflakes);
    }

    //
    // This function returns a number between (maximum - offset) and (maximum + offset)
    //
    function getPosition(offset, size) {
        return Math.round(-1 * offset + Math.random() * (size + 2 * offset));
    }

    //
    // Trigger a reset of all the snowflakes' positions
    //
    function setResetFlag(e) {
        resetPosition = true;
    }
</script>

-->
<!-- Fim da Neve -->

</body>
</html>
