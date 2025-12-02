@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.publications'). ' - LookCrim')

@section('conteudo')
    <div>
        <h4 class="font-title-for-customization">
            @lang('buttons.edit-title')
        </h4>
    </div>

<div class="main-website-interior">
    <div class="row description">
    	<div class="col-xl-12">
			<form method="POST" action="{{ route('publications-update', $publications->id) }}" enctype="multipart/form-data">
    			@csrf
    			@method('PUT')
    			@include('partials.publications.form')
    		</form>
    	</div>
    </div>
</div>
@endsection
