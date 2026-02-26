@if (count($errors) > 0)
	<div class="alert alert-danger">
		<ul>
			@foreach ($errors->all() as $error)
				<li>{{ $error }}</li>
			@endforeach
		</ul>
	</div>
@endif

<div class="form-group">
	<label for="title" class="col-form-label">{{ __('pages.title') }}</label>
	<input type="text" name="title" id="title" value="{{ old('title', isset($register) ? $register->title() : '') }}" class="form-control">
</div>

<div class="form-group">
	<label for="content" class="col-form-label">{{ 'Content' }}</label>
	<textarea name="content" id="content" class="form-control" rows="7">{{ old('content', isset($register) ? $register->content() : '') }}</textarea>
</div>

<div class="lc-register-form-two-col">
	<div class="form-group">
		<label for="images" class="col-form-label">Imágenes (máx. 3)</label>
		<input type="file" name="images[]" id="images" class="form-control-file" accept="image/*" multiple>
	</div>

	<div class="form-group">
		<label for="category" class="col-form-label">Categoría</label>
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

<div class="form-group">
	<label for="embed_url" class="col-form-label">Video embed URL</label>
	<input type="text" name="embed_url" id="embed_url" value="{{ old('embed_url', isset($register) ? $register->get_embed_url() : '') }}" class="form-control">
	<small class="form-text text-muted">Formato: https://www.youtube.com/embed/&lt;ID&gt; o https://player.vimeo.com/video/&lt;ID&gt;</small>
</div>

<div class="form-group">
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

<div class="form-group">
	@include('registers.partials.map')
</div>

<div class="lc-register-form-submit">
	<button type="submit" class="btn btn-lookcrim btn-sm">{{ Lang::get('buttons.submit') }}</button>
</div>
