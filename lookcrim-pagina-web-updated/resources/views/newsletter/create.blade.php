@extends('layouts.legacy')
@section('titulo_browser', __('Create Newsletter') . ' - LookCrim')
@section('conteudo')

<div class="container">
    <h1>{{ __('Create Newsletter') }}</h1>
    <form action="{{ route('newsletter-store') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="subject">{{ __('Subject') }}</label>
            <input type="text" name="subject" id="subject" class="form-control" value="{{ old('subject', $newsletter->subject ?? '') }}">
        </div>
        <div class="form-group">
            <label for="content">{{ __('Content') }}</label>
            <textarea name="content" id="content" rows="8" class="form-control">{{ old('content', $newsletter->content ?? '') }}</textarea>
        </div>
        <div class="form-group">
            <label for="image">{{ __('Image') }}</label>
            <input type="file" name="image" id="image" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
    </form>
</div>

@endsection
