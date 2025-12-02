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
		<label for="title_pt">{{ Lang::get('pages.title_pt') }}</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit" >
		<input type="text" name="title_pt" id="title_pt" value="{{ old('title_pt', isset($publications) ? $publications->title_pt : '') }}" style="width:100%" class="form-control">
	</div>
</div>

<div class="row">
	<div class="col-12 textarea-lang">
		<label for="title_en">{{ Lang::get('pages.title_en') }}</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit" style="width:100%">
		<input type="text" name="title_en" id="title_en" value="{{ old('title_en', isset($publications) ? $publications->title_en : '') }}" style="width:100%" class="form-control">
	</div>
</div>

<div class="row">
	<div class="col-12 textarea-lang">
		<label for="content_pt">{{ Lang::get('pages.content_pt') }}</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit">
		<textarea name="content_pt" id="content_pt" class="form-control">{{ old('content_pt', isset($publications) ? $publications->content_pt : '') }}</textarea>
	</div>
</div>

<div class="row">
	<div class="col-12 textarea-lang">
		<label for="content_en">{{ Lang::get('pages.content_en') }}</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit">
		<textarea name="content_en" id="content_en" class="form-control">{{ old('content_en', isset($publications) ? $publications->content_en : '') }}</textarea>
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
		<label for="embed_url">Endereço vídeo PT</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit">
		<input type="text" name="embed_url" id="embed_url" value="{{ old('embed_url', isset($publications) ? $publications->embed_url : '') }}" style="width:100%" class="form-control">
		<small class="form-text text-muted">No formato https://www.youtube.com/embed/&lt;ID&gt; OU https://player.vimeo.com/video/&lt;ID&gt;</small>
		<small class="form-text text-muted">Atenção: Substituir &lt;ID&gt; pelo código identificativo do vídeo</small>
	</div>
</div>
</br>

<div class="row">
	<div class="col-12 textarea-lang">
		<label for="embed_url_en">Endereço vídeo EN</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit">
		<input type="text" name="embed_url_en" id="embed_url_en" value="{{ old('embed_url_en', isset($publications) ? $publications->embed_url_en : '') }}" style="width:100%" class="form-control">
		<small class="form-text text-muted">No formato https://www.youtube.com/embed/&lt;ID&gt; OU https://player.vimeo.com/video/&lt;ID&gt;</small>
		<small class="form-text text-muted">Atenção: Substituir &lt;ID&gt; pelo código identificativo do vídeo</small>
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
		<button type="submit" class="btn btn-lookcrim">{{ Lang::get('buttons.submit') }}</button>
	</div>
</div>
