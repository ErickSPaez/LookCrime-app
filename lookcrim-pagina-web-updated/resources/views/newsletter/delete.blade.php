@extends('layouts.legacy')
@section('titulo_browser', __('Delete Newsletter'))
@section('conteudo')

<div class="container">
    <h1>{{ __('Delete Newsletter') }}</h1>
    <p>{{ __('Confirm you want to delete this newsletter?') }}</p>
    <form action="{{ route('delete-newsletter', ['id' => $newsletter->id]) }}" method="post">
        @csrf
        <input type="hidden" name="confirm" value="yes" />
        <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
        <a href="{{ route('newsletter') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
    </form>
</div>

@endsection
