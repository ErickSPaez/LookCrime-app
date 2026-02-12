@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.registers'). ' - LookCrim')

@section('conteudo')

<div class="main-website-interior">
    <h1 class="font-title-for-customization register-title" style="margin:0;text-align:center;">
        @lang('layout.registers')
    </h1>
    <hr class="interior-title-line register-line-title" style="margin-bottom:18px;">

    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
        <div>
            @canany(['create_own_registers','create_registers'])
                <a class="btn btn-lookcrim btn-sm edit-text" href="{{ route('registers.create') }}">
                    @lang('buttons.add-register')
                </a>
            @endcanany
        </div>
        <div style="margin-left:auto;">
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

    <div style="margin-top:14px;">
        {{ $registers->links() }}
    </div>
</div>

@include('partials.registers.delete-modal')
@endsection
