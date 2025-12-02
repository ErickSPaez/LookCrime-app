@extends('layouts.legacy')
@section('titulo_browser','Enviar Teste Newsletter')
@section('conteudo')

<div class="container">
    <h1>{{ __('Send Test') }}</h1>
    <p>{{ __('Send a copy to your email (authenticated user).') }}</p>
    <form action="{{ route('test-newsletter', ['id' => $newsletter->id]) }}" method="post">
        @csrf
        <input type="hidden" name="confirm" value="yes" />
        <button type="submit" class="btn btn-primary">{{ __('Send to me') }}</button>
        <a href="{{ route('newsletter') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
    </form>
</div>

@endsection
