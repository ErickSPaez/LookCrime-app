@if (count($errors) > 0)
	<div class="alert alert-danger">
		<ul>
			@foreach ($errors->all() as $error)
				<li>{{ $error }}</li>
			@endforeach
		</ul>
	</div>
@endif

<div class="form-group row">
	<label for="title" class="col-12 col-form-label">{{ __('pages.title') }}</label>
	<div class="col-12 col-md-8">
		<input type="text" name="title" id="title" value="{{ old('title', isset($register) ? $register->title() : '') }}" class="form-control">
	</div>
</div>

<div class="form-group row">
	<label for="content" class="col-12 col-form-label">{{ 'Content' }}</label>
	<div class="col-12 col-md-8">
		<textarea name="content" id="content" class="form-control">{{ old('content', isset($register) ? $register->content() : '') }}</textarea>
	</div>
</div>

<div class="form-group row">
	<label for="images" class="col-12 col-form-label">Imágenes (máx. 3)</label>
	<div class="col-12 col-md-8">
		<input type="file" name="images[]" id="images" class="form-control-file" accept="image/*" multiple>
	</div>
</div>

<div class="form-group row">
	<label for="embed_url" class="col-12 col-form-label">Video embed URL</label>
	<div class="col-12 col-md-8">
		<input type="text" name="embed_url" id="embed_url" value="{{ old('embed_url', isset($register) ? $register->get_embed_url() : '') }}" class="form-control">
		<small class="form-text text-muted">Formato: https://www.youtube.com/embed/&lt;ID&gt; o https://player.vimeo.com/video/&lt;ID&gt;</small>
	</div>
</div>

<div class="form-group row">
	<label for="category" class="col-12 col-form-label">Categoría</label>
	<div class="col-12 col-md-8">
		<select name="category" id="category" class="form-control">
			@php
				$cat = old('category', isset($register) ? $register->category : null);
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
	<div class="col-12 col-md-8">
		<div class="form-check">
			<input class="form-check-input" type="checkbox" name="private" id="private" value="1" {{ old('private', isset($register) ? $register->private : 0) == 1 ? 'checked' : '' }}>
			<label class="form-check-label" for="private">@lang('pages.private')</label>
		</div>
		@if ($errors->has('private'))
			<div class="invalid-feedback d-block">
				<strong>{{ $errors->first('private') }}</strong>
			</div>
		@endif
	</div>
</div>

<div class="form-group row">
	<div class="col-12">
		@include('registers.partials.map')
	</div>
</div>

<div class="form-group row">
	<div class="col-12 col-md-8">
		<button type="submit" class="btn btn-lookcrim btn-block btn-sm">{{ Lang::get('buttons.submit') }}</button>
	</div>
</div>
