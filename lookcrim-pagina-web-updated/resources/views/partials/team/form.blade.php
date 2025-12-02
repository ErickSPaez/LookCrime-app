@if (count($errors) > 0)
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
	<div class="col-12 textarea-lang">
		<label for="content_pt">{{ Lang::get('pages.content_pt') }}</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit">
		<textarea name="content_pt" id="content_pt" class="form-control">{{ old('content_pt', isset($team) ? $team->content_pt : '') }}</textarea>
	</div>
</div>

<div class="row">
	<div class="col-12 textarea-lang">
		<label for="content_en">{{ Lang::get('pages.content_en') }}</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit">
		<textarea name="content_en" id="content_en" class="form-control">{{ old('content_en', isset($team) ? $team->content_en : '') }}</textarea>
	</div>
</div>

<div class="row">
	<div class="col-12 submit-text">
		<button type="submit" class="btn btn-lookcrim">{{ Lang::get('buttons.submit') }}</button>
	</div>
</div>
