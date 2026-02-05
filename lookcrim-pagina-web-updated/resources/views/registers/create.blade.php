@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.registers'). ' - LookCrim')

@section('conteudo')
<div class="main-website-interior">
    @php
        $lcUser = auth()->user();
        $lcCanViewRegisters = $lcUser && (
            $lcUser->can('view_any_registers') || $lcUser->can('view_all_registers') ||
            $lcUser->can('view_own_registers')
        );
        $lcBackUrl = $lcCanViewRegisters ? route('registers.index') : url('/');
    @endphp
    <h1 class="font-title-for-customization register-title" style="margin:0;text-align:center;">
        @lang('buttons.add-register')
    </h1>
    <hr class="interior-title-line register-line-title" style="margin-bottom:10px;">
    <div style="display:flex;justify-content:flex-end;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 18px 0;">
        <a class="btn btn-lookcrim-white btn-sm" href="{{ $lcBackUrl }}">{{ __('pages.back') }}</a>
    </div>

    <div class="row description">
        <div class="col-xl-12">
            <form method="POST" action="{{ route('registers.store') }}" enctype="multipart/form-data">
                @csrf
                @include('partials.registers.form')
            </form>
        </div>
    </div>
</div>

@endsection
