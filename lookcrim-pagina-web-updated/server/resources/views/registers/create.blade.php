@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.registers'). ' - LookCrim')

@section('conteudo')
<div class="main-website-interior lc-register-form-page">
    @php
        $lcUser = auth()->user();
        $lcCanViewRegisters = $lcUser && (
            $lcUser->can('view_any_registers') || $lcUser->can('view_all_registers') ||
            $lcUser->can('view_own_registers')
        );
        $lcFallbackUrl = $lcCanViewRegisters ? route('registers.index') : url('/');
        $lcPrevious = url()->previous();
        $lcBackUrl = (is_string($lcPrevious) && str_starts_with($lcPrevious, url('/')))
            ? $lcPrevious
            : $lcFallbackUrl;
    @endphp
    <div class="lc-title-row">
        <a class="lc-back-link" href="{{ $lcBackUrl }}">&larr; {{ __('pages.back') }}</a>
        <h1 class="font-title-for-customization register-title" style="margin:0;text-align:center;">
            @lang('buttons.add-register')
        </h1>
        <span class="lc-back-link lc-back-link--spacer" aria-hidden="true">&larr; {{ __('pages.back') }}</span>
    </div>
    <hr class="interior-title-line register-line-title" style="margin-bottom:10px;">

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
