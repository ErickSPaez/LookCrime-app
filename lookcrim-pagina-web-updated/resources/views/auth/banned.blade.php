@extends('layouts.legacy')

@section('titulo_browser', __('Account banned') . ' - LookCrim')

@section('conteudo')
    <div class="container">
        <h1>{{ __('Account banned') }}</h1>
        <p>{{ __('Your account has been banned by an administrator. If you believe this is a mistake, contact the site administrators.') }}</p>
        <a href="{{ route('login') }}" class="btn btn-lookcrim">{{ __('Return to login') }}</a>
    </div>
@endsection
