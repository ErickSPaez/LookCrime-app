@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.publications'). ' - LookCrim')

@section('conteudo')
@include('partials.publications.show')
@endsection
