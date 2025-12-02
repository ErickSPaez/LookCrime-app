@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.theproject'). ' - LookCrim')

@section('conteudo')
@include('partials.project.show')
@endsection
