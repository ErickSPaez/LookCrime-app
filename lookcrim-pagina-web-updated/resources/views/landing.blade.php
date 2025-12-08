@extends('layouts.legacy')

@section('titulo_browser','LookCrim')

@section('conteudo')
<style>
    /* Ocultar la barra de navegación principal en la landing y mostrar solo el enlace Dashboard arriba */
    nav.navbar, .logo-head-bar, .line-menubar { display: none !important; }
    /* Mantener el top-menu (login/register) visible */
    .top-menu { display: block !important; }
</style>

<div class="main-website-interior" style="text-align:center;padding:40px 20px;">
    <div style="max-width:900px;margin:0 auto 18px;">
        <div style="display:flex;justify-content:center;gap:18px;margin-bottom:18px;flex-wrap:wrap;">
            <a href="{{ url('/dashboard') }}" class="btn btn-sm btn-link">Dashboard</a>
        </div>
    </div>

    <div style="max-width:560px;margin:0 auto;">
        <img src="{{ asset('img/LookCrim-Logo1.png') }}" alt="LookCrim" style="max-width:320px; width:100%; height:auto; display:block; margin:0 auto 18px;"/>
        <div style="margin-top:8px;">
            @guest
                <a href="{{ route('login') }}" class="btn btn-lookcrim" style="margin-right:8px;">Login</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-outline-lookcrim">Register</a>
                @endif
            @endguest
        </div>
    </div>
</div>

@endsection
