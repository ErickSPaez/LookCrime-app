@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.registers'). ' - LookCrim')

@section('conteudo')

<div class="main-website-interior">
    <h1 class="font-title-for-customization interior-title">
        @lang('layout.registers')
    </h1>
    <hr class="interior-title-line">

    @include('registers.partials.view-toggle')

    @if(count($registers) > 0)
    <div class="row row-list-research">
        @each('partials.registers.short', $registers, 'register')
    </div>

    @else
        <div class="col-12" style="margin: 12px 0;">
            <div class="lc-empty-state" style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:240px;text-align:center;">
                <div style="font-size:1.1rem;color:#333;margin-bottom:6px;">@lang('pages.empty-page')</div>
                <div style="font-size:0.98rem;color:#555;">@lang('pages.empty-page-cta')</div>
            </div>
        </div>
    @endif

    @auth
        <div class="row research flex-align-center">
            <div class="col-xs-10 image">
                <a class="btn btn-lookcrim btn-sm edit-text" href="{{ route('registers.create') }}">
                    @lang('buttons.add-register')
                </a>
        </div>
    </div>
    @endauth
</div>

{{ $registers->links()}}
@endsection
