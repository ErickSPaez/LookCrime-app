@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.registers'). ' - LookCrim')

@section('conteudo')
    <div>
        <h4 class="font-title-for-customization">
            @lang('buttons.edit-title')
        </h4>
    </div>

<div class="main-website-interior">
    <div class="row description">
        <div class="col-xl-12">
            <form method="POST" action="{{ route('registers.update', $register->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('partials.registers.form')
            </form>
        </div>
    </div>
</div>
@endsection
