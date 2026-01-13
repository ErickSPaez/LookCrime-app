@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.registers'). ' - LookCrim')

@section('conteudo')
<div class="main-website-interior">
    <div>
        <h4 class="font-title-for-customization interior-title">
            @lang('buttons.add-register')
        </h4>
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
