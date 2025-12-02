@extends('layouts.legacy')
@section('titulo_browser','Enviar Newsletter')
@section('conteudo')

<div class="container">
    <h1>{{ __('Send Newsletter') }}</h1>
    <p>{{ __('Confirm sending of newsletter:') }} <strong>{{ $newsletter->subject }}</strong></p>
    <form action="{{ route('send-newsletter', ['id' => $newsletter->id]) }}" method="post">
        @csrf
        <input type="hidden" name="confirm" value="yes" />
        <button type="submit" class="btn btn-success">{{ __('Send to all confirmed subscriptions') }}</button>
        <a href="{{ route('newsletter') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
    </form>
</div>

@endsection
