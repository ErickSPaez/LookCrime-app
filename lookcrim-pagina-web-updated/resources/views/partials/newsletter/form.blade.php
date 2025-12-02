@if (count($errors) > 0)
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row section">
	<div class="col-12 textarea-lang">
		<label for="subject">Assunto</label>
	</div>
    <div class="col-xl-12 col-sm-8 textarea-edit">
		<input type="text" name="subject" id="subject" value="{{ old('subject', isset($newsletter) ? $newsletter->subject : '') }}" class="form-control">
	</div>
</div>

<div class="row section">
    <div class="col-12 textarea-lang">
		<label for="content">Conteúdo</label>
	</div>
    <div class="col-xl-12 col-sm-8 textarea-edit">
		<textarea name="content" id="content" class="form-control">{{ old('content', isset($newsletter) ? $newsletter->content : '') }}</textarea>
	</div>
</div>

<div class="row section">
	<div class="col-12 textarea-lang">
		<label for="image">Imagem</label>
	</div>
    <div class="col-xl-12 col-sm-8 textarea-edit">
		<input type="file" name="image" id="image">
	</div>
</div>

<div class="section dummy"></div>
@php ($a = 0)
@foreach($newsletter->sections as $index => $section)
		@if($index == 0)
            <h1 class="font-title-for-customization interior-title news-newsletter">
                Notícias
            </h1>
			@php ($a++)
		@endif

		@if($index != 0 && $a == 0)
		<div class="row description">
            <h1 class="font-title-for-customization interior-title news-newsletter">
                Notícias
            </h1>
		</div>
			@php ($a++)
		@endif

	@include('partials.newsletter.section', [
		'index' => $index,
		'section' => $section,
	])
@endforeach

<div class="row section">
	<div class="col-xs-12 col-sm-4 form-label">
		<a class="admin-button add-section" href="#" data-newsletter-id="{{$newsletter->id}}" data-next-section="{{$newsletter->nextSeq()}}" >
            <span class="fa fa-lg fa-plus"></span> Adicionar notícia
        </a>
	</div>
</div>

<hr />

<div class="row">
	<div class="col-12 submit-text">
		<button type="submit" class="btn btn-lookcrim">{{ Lang::get('buttons.submit') }}</button>
	</div>
</div>
