@extends('layouts.legacy')

@section('titulo_browser','LookCrim')

@section('conteudo')
<style>
    /* Ocultar la barra de navegación principal en la landing y mostrar solo el enlace Dashboard arriba */
    nav.navbar, .logo-head-bar, .line-menubar { display: none !important; }
    /* Mantener el top-menu visible */
    .top-menu { display: block !important; }

    /* Evitar franja blanca inferior: fijar footer al fondo del viewport */
    html, body { height: 100%; }
    body { min-height: 100vh; display: flex; flex-direction: column; }
    .main-website { flex: 1 0 auto; min-height: 0 !important; }
    .bg-lcred2 { flex-shrink: 0; }
</style>

<div class="main-website-interior" style="text-align:center;padding:40px 20px;">
    <div style="max-width:560px;margin:0 auto;">
        <img src="{{ asset('img/LookCrim-Logo1.png') }}" alt="LookCrim" style="max-width:320px; width:100%; height:auto; display:block; margin:0 auto 18px;"/>
        <p class="text-muted" style="margin:0 auto;max-width:560px;">
            @lang('pages.landing_private_platform')
        </p>
    </div>
</div>

@endsection
