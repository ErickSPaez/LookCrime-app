@extends('layouts.legacy')

@section('titulo_browser', __('buttons.delete') . ' - LookCrim')

@section('conteudo')
<div class="main-website-interior">
    <h3>@lang('buttons.delete')</h3>
    <p>@lang('pages.confirm-delete')</p>
    @if (Route::has('news-delete'))
    <form method="POST" action="{{ route('news-delete', $news->id) }}">
        @csrf
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="btn btn-danger">@lang('buttons.delete')</button>
        <a href="{{ route('news') }}" class="btn btn-secondary">@lang('buttons.cancel')</a>
    </form>
    @else
    <div class="alert alert-warning">@lang('pages.action-not-available')</div>
    <a href="{{ route('news') }}" class="btn btn-secondary">@lang('buttons.back')</a>
    @endif
</div>
@endsection
