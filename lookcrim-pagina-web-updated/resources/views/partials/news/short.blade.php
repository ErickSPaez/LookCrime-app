<div class="col-md-4 list-item-research"  data-aos="fade-up" data-aos-duration="1000">

	@if($news->image != '')
	<div class="card-image-content image-news">
		<img src="{{ asset($news->image) }}" alt="{{ $news->title() }}" class="img-fluid">
		<div class="card-image-shadow"></div>
	</div>
	@endif
	<div class="card-body card-with-margin card-news">
		<h2 class="title-research news-title">
			{{ $news->title() }}
		</h2>

		<!--
		<div class="card-summary-content">
			@if(strlen($news->content()) < 75)
				{!! strip_tags($news->content()) !!}
			@endif
			@if(strlen($news->content()) >= 75)
				{!! substr(strip_tags($news->content()), 0, strpos(strip_tags($news->content()), ' ', 70)) !!}...
			@endif
		</div>
		-->

		<span class="news-date">{{$news->created_at->formatLocalized('%d/%m/%Y') }}</span>

		<a class="card-view-more card-view-news" href="{{route('new', $news->id)}}" title="{{route('new', $news->id)}}">Ver Notícia</a>

		@if(Auth::check() && Auth::user()->admin)
			<div class="row card-buttons">
				@if (Route::has('news-edit'))
					<a class="card-edit-buttons" href="{{ route('news-edit', $news->id) }}">
						@lang('buttons.edit')
					</a>
				@endif

				@if (Route::has('news-delete'))
					<a class="card-edit-buttons" href="{{ route('news-delete', $news->id) }}">
						@lang('buttons.delete')
					</a>
				@endif
			</div>
		@endif
	</div>

	<!--
	<div class="card-footer text-muted">
		@lang('buttons.created-at')
		{{$news->created_at->formatLocalized('%d/%m/%Y') }}
		@lang('buttons.by')
		@lang('pages.global_author')
	</div>
	-->

</div>
