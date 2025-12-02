@extends('layouts.legacy')
@section('titulo_browser', __('Edit Newsletter') . ' - LookCrim')
@section('conteudo')

<div class="container">
    <h1>{{ __('Edit Newsletter') }}</h1>
    <form action="{{ route('update-newsletter', ['id' => $newsletter->id]) }}" method="post" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="subject">{{ __('Subject') }}</label>
            <input type="text" name="subject" id="subject" class="form-control" value="{{ old('subject', $newsletter->subject ?? '') }}">
        </div>
        <div class="form-group">
            <label for="content">{{ __('Content') }}</label>
            <textarea name="content" id="content" rows="8" class="form-control">{{ old('content', $newsletter->content ?? '') }}</textarea>
        </div>

        <h3>{{ __('Sections') }}</h3>
        <div id="sections">
            @foreach($newsletter->sections as $section)
                @include('partials.newsletter.section', ['section' => $section, 'newsletter' => $newsletter])
            @endforeach
        </div>

        <div class="form-group">
            <label for="image">{{ __('Main image') }}</label>
            <input type="file" name="image" id="image" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
    </form>
</div>

@endsection
