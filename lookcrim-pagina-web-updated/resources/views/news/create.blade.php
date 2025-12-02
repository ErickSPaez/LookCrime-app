@extends('layouts.legacy')

@section('titulo_browser', __('buttons.add-new') . ' - LookCrim')

@section('conteudo')
<div class="main-website-interior">
    <div>
        <h4 class="font-title-for-customization interior-title">@lang('buttons.add-new')</h4>
    </div>
    <div class="row description">
        <div class="col-xl-12">
            <form action="{{ route('news-store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('partials.news.form')
            </form>
        </div>
    </div>
</div>
@endsection
