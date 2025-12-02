<div class="col-md-12 list-item-publications"  data-aos="fade-up" data-aos-duration="1000">
	<div class="card mb-4 card-publications-item">
		<div class="card-body card-publications">
			<div class="container space-2 space-md-3">
				<div class="row align-items-center">
					<div class="col-lg-4 mb-9 mb-lg-0 no-padding-left">
						@if($publications->image != '')
							<div class="publications-image">
								@include('partials.publications.image')
							</div>
						@endif
					</div>
					<div class="col-lg-8 position-relative col-publications">
						<h2 class="title-research title-publications">
							{{ $publications->title() }}
						</h2>
							<div class="card-summary-content content-publications">
								@if(strlen($publications->content()) < 300)
									{!! strip_tags($publications->content()) !!}
								@endif
								@if(strlen($publications->content()) >= 300)
									{!! substr(strip_tags($publications->content()), 0, strpos(strip_tags($publications->content()), ' ', 260)) !!}...
								@endif
							</div>

							<a class="card-view-more" href="{{route('publication', $publications->id)}}" title="{{ $publications->title() }}">@lang('buttons.readmore')</a>

							@if(Auth::check() && Auth::user()->admin)
							<div class="row card-buttons">
								<a class="card-edit-buttons" href="{{route('publications-edit', $publications->id)}}">
									@lang('buttons.edit')
								</a>

								<a class="card-edit-buttons" href="{{route('publications-delete', $publications->id)}}">
									@lang('buttons.delete')
								</a>
							</div>
							@endif
						</div>
					</div>
			</div>
			<!--
			<div class="card-footer text-muted">
				@lang('buttons.created-at')
				{{$publications->created_at->formatLocalized('%d/%m/%Y') }}
				@lang('buttons.by')
				@lang('pages.global_author')
			</div>
		-->
		</div>
	</div>
</div>
