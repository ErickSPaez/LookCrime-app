@extends('layouts.legacy')

@section('titulo_browser', $aux = __('pages.no_permissions_title') . ' - LookCrim')

@section('conteudo')
<div class="main-website-interior" style="min-height:65vh;display:flex;flex-direction:column;align-items:center;justify-content:center;">
    <div style="max-width:760px;text-align:center;padding:16px 12px;">
        <h1 class="font-title-for-customization" style="margin-bottom:10px;">
            {{ __('pages.no_permissions_title') }}
        </h1>
        <div style="font-size:1.05rem;color:#333;margin-bottom:10px;">
            {{ __('pages.no_permissions_message') }}
        </div>
        <div style="font-size:0.98rem;color:#555;margin-bottom:18px;">
            {{ __('pages.no_permissions_contact') }}
        </div>
        <div style="font-size:0.95rem;color:#666;">
            {{ __('pages.no_permissions_hint') }}
        </div>
    </div>

    <div style="margin-top:28px;opacity:0.95;">
        <img src="{{ asset('img/LookCrim-Logo1.png') }}" alt="LookCrim" style="max-width:220px;width:60vw;height:auto;" />
    </div>
</div>
@endsection
