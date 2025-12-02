@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.theproject'). ' - LookCrim')

@section('conteudo')

<h4 class="font-title-for-customization interior-title">
    @lang('pages.edit-project-title')
</h4>

<div class="main-website-interior">
    <div class="row description">
     	<div class="col-xl-12">
    		<form method="POST" action="{{ route('update-project') }}">
    			@csrf
    			@method('PUT')
    			@include('partials.project.form')
    		</form>
    	</div>
    </div>
</div>
@endsection
