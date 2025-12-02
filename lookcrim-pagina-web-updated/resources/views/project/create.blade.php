@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.theproject'). ' - LookCrim')

@section('conteudo')
<div class="main-website-interior">
    <div>
        <h4 class="font-title-for-customization interior-title">
            @lang('pages.add-project')
        </h4>
        <hr class="interior-title-line">
    </div>
    
    <div class="row description">
    	<div class="col-xl-12">
    		<form method="POST" action="{{ route('project-store') }}">
    			@csrf
    			@include('partials.project.form')
    		</form>
    	</div>
    </div>
</div>
@endsection
