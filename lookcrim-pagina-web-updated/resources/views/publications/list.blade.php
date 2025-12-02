@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.publications'). ' - LookCrim')

@section('conteudo')

<div class="main-website-interior">
    <h1 class="font-title-for-customization interior-title">
        @lang('layout.publications')
    </h1>
    <hr class="interior-title-line">

    @if($publications[0] != null)
    <div class="row row-list-research">
        @each('partials.publications.short', $publications, 'publications')
    </div>

    @else
        <div class="col-12 warning-message">
            <span class="alert alert-danger" role="alert">@lang('pages.empty-page')</span>
        </div>
    @endif

    @if(Auth::check() && Auth::user()->admin)
        <div class="row research flex-align-center">
            <div class="col-xs-10 image">
                <a class="btn btn-lookcrim btn-sm edit-text" href="{{route('publications-create')}}">
                    @lang('buttons.add-publication')
                </a>
        </div>
    </div>
    @endif
</div>


{{ $publications->links()}}
@endsection
