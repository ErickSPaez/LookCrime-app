@extends('layouts.app')

@section('content')
<div class="container">
    <h1>@lang('layout.people') - @lang('buttons.edit')</h1>

    <form method="POST" action="{{ route('team-update') }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="content_pt">@lang('labels.content_pt')</label>
            <textarea id="content_pt" name="content_pt" class="form-control">{{ old('content_pt', $team->content_pt ?? '') }}</textarea>
        </div>

        <div class="form-group">
            <label for="content_en">@lang('labels.content_en')</label>
            <textarea id="content_en" name="content_en" class="form-control">{{ old('content_en', $team->content_en ?? '') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">@lang('buttons.save')</button>
    </form>
</div>
@endsection
