@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.publications'). ' - LookCrim')

@section('conteudo')
<div class="main-website-interior">
        <div class="font-title-for-customization interior-title">
            @lang('buttons.want-to-delete') "{{ $publications->title() }}"?
        </div>
    <hr class="interior-title-line delete-page">

    <div class="col-12">
        <div class="delete-buttons-page">
            <form method="POST" action="{{ route('publications-delete', $publications->id) }}">
                @csrf
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn btn-lookcrim btn-sm edit-text delete-buttons">{{ Lang::get('buttons.confirm') }}</button>
            </form>
            <form method="POST" action="{{ route('publications-delete', $publications->id) }}">
                @csrf
                <input type="hidden" name="confirm" value="no">
                <button type="submit" class="btn btn-lookcrim-white btn-sm delete-buttons">{{ Lang::get('buttons.cancel') }}</button>
            </form>
    	</div>
    </div>
</div>
@endsection
