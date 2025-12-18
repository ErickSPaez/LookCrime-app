@extends('layouts.legacy')
@section('titulo_browser',__('Dashboard - LookCrim'))
@section('conteudo')
    <div class="main-website-interior">
        <h1 class="font-title-for-customization interior-title">{{ __('Dashboard') }}</h1>
        <hr class="interior-title-line">

        <div class="row">
            <div class="col-12">
                <p>{{ __('Welcome back,') }} <strong>{{ Auth::user() ? Auth::user()->name : __('User') }}</strong>.</p>
                <p>{{ __('Use the quick links below to navigate:') }}</p>
                <ul>
                    <li><a href="{{ url('/registers') }}">{{ __('Publications') }}</a></li>
                    <li><a href="{{ url('/map') }}">{{ __('Map') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
@endsection
