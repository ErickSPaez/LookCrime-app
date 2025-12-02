@extends('layouts.legacy')
@section('titulo_browser',$newsletter->subject ?? 'Newsletter')
@section('conteudo')

<div class="container">
    <h1>{{ $newsletter->subject }}</h1>
    @if(!empty($newsletter->image))
        <img src="{{ asset($newsletter->image) }}" alt="" style="max-width:100%; height:auto;" />
    @endif
    <div class="mt-3">
        {!! $newsletter->content !!}
    </div>

    <h3>Secciones</h3>
    @foreach($newsletter->sections as $section)
        <div class="newsletter-section">
            <h4>Sección {{ $section->seq }}</h4>
            <div>{!! $section->content !!}</div>
            @if($section->image)
                <img src="{{ asset($section->image) }}" alt="" style="max-width:200px; height:auto;">
            @endif
        </div>
    @endforeach

    @if(Auth::check() && Auth::user()->admin)
        <a href="{{ route('edit-newsletter', ['id' => $newsletter->id]) }}" class="btn btn-secondary">Editar</a>
        <a href="{{ route('send-newsletter', ['id' => $newsletter->id]) }}" class="btn btn-success">Enviar</a>
    @endif
</div>

@endsection
