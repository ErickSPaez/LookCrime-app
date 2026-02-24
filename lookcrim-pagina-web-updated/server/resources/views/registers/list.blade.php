@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.registers'). ' - LookCrim')

@section('conteudo')

<div class="main-website-interior container">
    <h1 class="font-title-for-customization register-title text-center mb-2">
        @lang('layout.registers')
    </h1>
    <hr class="interior-title-line register-line-title mb-3">

    <div class="row mb-3 align-items-center justify-content-between">
        <div class="col-12 col-md-auto mb-2 mb-md-0">
            @canany(['create_own_registers','create_registers'])
                <a class="btn btn-lookcrim btn-sm edit-text" href="{{ route('registers.create') }}">
                    @lang('buttons.add-register')
                </a>
            @endcanany
        </div>
        <div class="col-12 col-md-auto text-md-right">
            @include('registers.partials.view-toggle')
        </div>
    </div>

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

    <div class="mt-3">
        {{ $registers->links() }}
    </div>
</div>

@include('partials.registers.delete-modal')
@endsection
