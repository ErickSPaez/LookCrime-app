<div class="col-md-4 list-item-research"  data-aos="fade-up" data-aos-duration="1000">
		@if ($research->image !== '')
			<div class="card-image-content">
				<img src="{{ asset($research->image) }}" alt="{{ $research->title() }}" class="img-fluid">
				<div class="card-image-shadow"></div>
			</div>
		@endif

		<div class="card-body card-with-margin">
			<h2 class="title-research">
				{{ $research->title() }}
			</h2>

			<div class="card-summary-content">
				@if(strlen($research->content()) < 75)
					{!! strip_tags($research->content()) !!}
				@endif

				@if(strlen($research->content()) >= 75)
					{!! substr(strip_tags($research->content()), 0, strpos(strip_tags($research->content()), ' ', 72)) !!}...
				@endif
			</div>

			<a class="card-view-more" href="{{route('single-research', $research->id)}}" title="{{ $research->title() }}">@lang('buttons.readmore')</a>

			@if(Auth::check() && Auth::user()->admin)
				<div class="row card-buttons">
					<a class="card-edit-buttons" href="{{route('research-edit', $research->id)}}">
						@lang('buttons.edit')
					</a>

					<a class="card-edit-buttons" href="{{route('research-delete', $research->id)}}">
						@lang('buttons.delete')
					</a>
				</div>
			@endif
		</div>
</div>

<!--
	AINDA  A DECIDIR SE É PARA COLOCAR

	<div class="card-footer text-muted">
		@lang('buttons.created-at')
		{{$research->created_at->formatLocalized('%d/%m/%Y') }}
		@lang('buttons.by')
		@lang('pages.global_author')
	</div>
-->
