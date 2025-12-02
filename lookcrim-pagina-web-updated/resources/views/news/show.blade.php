@extends('layouts.legacy')

@section('titulo_browser', $news->title() . ' - LookCrim')

@section('conteudo')
    @include('partials.news.show')
@endsection
