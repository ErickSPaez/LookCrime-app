@extends('layouts.legacy')
@section('titulo_browser','Newsletters - LookCrim')
@section('conteudo')

<div class="container">
    <h1>{{ __('Newsletters') }}</h1>
    @if(Auth::check() && Auth::user()->admin)
        <a href="{{ route('create-newsletter') }}" class="btn btn-primary">{{ __('Create Newsletter') }}</a>
    @endif

    <ul class="list-group mt-3">
        @if($newsletters->isEmpty())
            <li class="list-group-item">
                <span class="text-muted">{{ __('No newsletters yet.') }}</span>
            </li>
        @else
            @foreach($newsletters as $n)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <a href="{{ route('preview-newsletter', ['id' => $n->id]) }}">{{ $n->subject }}</a>
                    <span>
                        @if(Auth::check() && Auth::user()->admin)
                            <a href="{{ route('edit-newsletter', ['id' => $n->id]) }}" class="btn btn-sm btn-secondary">{{ __('Edit') }}</a>
                            <a href="{{ route('send-newsletter', ['id' => $n->id]) }}" class="btn btn-sm btn-success">{{ __('Send') }}</a>
                        @endif
                    </span>
                </li>
            @endforeach
        @endif
    </ul>
</div>

@endsection
