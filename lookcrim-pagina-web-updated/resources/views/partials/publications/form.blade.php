@if (count($errors) > 0)
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

</br>
<div class="row">
	<div class="col-12 textarea-lang">
		<label for="title">{{ __('pages.title') }}</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit">
		<input type="text" name="title" id="title" value="{{ old('title', isset($publications) ? $publications->title() : '') }}" style="width:100%" class="form-control">
	</div>
</div>

<div class="row">
	<div class="col-12 textarea-lang">
		<label for="content">{{ 'Content' }}</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit">
		<textarea name="content" id="content" class="form-control">{{ old('content', isset($publications) ? $publications->content() : '') }}</textarea>
	</div>
</div>

<div class="row">
	<div class="col-12 textarea-lang">
		<label for="image">Imagem</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit">
		<input type="file" name="image" id="image" class="form-control-file">
	</div>
</div>

<div class="row">
	<div class="col-12 textarea-lang">
		<label for="embed_url">Video embed URL</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit">
		<input type="text" name="embed_url" id="embed_url" value="{{ old('embed_url', isset($publications) ? $publications->get_embed_url() : '') }}" style="width:100%" class="form-control">
		<small class="form-text text-muted">Formato: https://www.youtube.com/embed/&lt;ID&gt; o https://player.vimeo.com/video/&lt;ID&gt;</small>
	</div>
</div>

<div class="row" style="margin-top:12px;">
	<div class="col-12 textarea-lang">
		<label for="category">Categoría</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit">
		<select name="category" id="category" class="form-control" style="width:100%">
			@php
				$cat = old('category', isset($publications) ? $publications->category : null);
			@endphp
			<option value="">-- {{ __('pages.categories') }} --</option>
			<option value="robo" {{ $cat == 'robo' ? 'selected' : '' }}>{{ __('pages.robo') }}</option>
			<option value="poco_iluminacion" {{ $cat == 'poco_iluminacion' ? 'selected' : '' }}>{{ __('pages.poco_iluminacion') }}</option>
			<option value="zona_insegura" {{ $cat == 'zona_insegura' ? 'selected' : '' }}>{{ __('pages.zona_insegura') }}</option>
			<option value="zona_transitada" {{ $cat == 'zona_transitada' ? 'selected' : '' }}>{{ __('pages.zona_transitada') }}</option>
			<option value="construccion" {{ $cat == 'construccion' ? 'selected' : '' }}>{{ __('pages.construccion') }}</option>
			<option value="otro" {{ $cat == 'otro' ? 'selected' : '' }}>{{ __('pages.otro') }}</option>
		</select>
	</div>
</div>

<div class="form-group row">
	<div class="col-md-6">
			<input type="checkbox" name="private" id="private" value="1" {{ old('private', isset($publications) ? $publications->private : 0) == 1 ? 'checked' : '' }}>

		@if ($errors->has('private'))
			<span class="invalid-feedback" role="alert">
				<strong>{{ $errors->first('private') }}</strong>
			</span>
		@endif
        <label for="private" class="col-md-4 col-form-label check-box-label">@lang('pages.private')</label>

	</div>
</div>

<div class="row">
	<div class="col-12 submit-text">
		{{-- Map for selecting publication location --}}
		@include('publications.partials.map')

		<button type="submit" class="btn btn-lookcrim">{{ Lang::get('buttons.submit') }}</button>
	</div>
</div>
