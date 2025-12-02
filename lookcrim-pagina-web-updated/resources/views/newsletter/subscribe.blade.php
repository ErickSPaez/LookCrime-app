@extends('layouts.legacy')
@section('titulo_browser','Subscribirse - Newsletter')
@section('conteudo')

<div class="container">
    <h1>Subscribirse a la Newsletter</h1>
    <form action="{{ route('newsletter-subscribe') }}" method="post">
        @csrf
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Subscribirse</button>
    </form>
</div>

@endsection
