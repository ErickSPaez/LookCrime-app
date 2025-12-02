@extends('layouts.legacy')

@section('titulo_browser', __('layout.newsevents') . ' - LookCrim')

@section('conteudo')

<div class="main-website-interior">
    <h1 class="font-title-for-customization interior-title">@lang('layout.newsevents')</h1>
    <hr class="interior-title-line">

    @if($news->count() > 0)
        <div class="row row-list-research">
            @foreach($news as $n)
                @include('partials.news.short', ['news' => $n])
            @endforeach
        </div>
    @else
        <div class="col-12 warning-message">
            <span class="alert alert-danger" role="alert">@lang('pages.empty-page')</span>
        </div>
    @endif

    @if(Auth::check() && Auth::user()->admin)
    <div class="row research flex-align-center mt-3">
        <div class="col-xs-10 image">
            @if (Route::has('news-create'))
            <a class="btn btn-lookcrim btn-sm edit-text" href="{{ route('news-create') }}">@lang('buttons.add-new')</a>
            @endif
        </div>
    </div>
    @endif

</div>

{{ $news->links() }}
@endsection
