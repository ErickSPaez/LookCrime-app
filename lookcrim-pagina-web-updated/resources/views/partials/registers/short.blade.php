<div class="col-md-12 list-item-publications"  data-aos="fade-up" data-aos-duration="1000">
	<div class="card mb-4 card-publications-item">
		<div class="card-body card-publications">
			<div class="container space-2 space-md-3">
				<div class="row align-items-center">
					<div class="col-lg-4 mb-9 mb-lg-0 no-padding-left">
						@if(!empty($register->image_url()))
							<div class="publications-image">
								@include('partials.registers.image', ['mode' => 'cover'])
							</div>
						@endif
					</div>
					<div class="col-lg-8 position-relative col-publications">
						<h2 class="title-research title-publications">
							{{ $register->title() }}
						</h2>
							<div class="card-summary-content content-publications">
								@if(strlen($register->content()) < 300)
									{!! strip_tags($register->content()) !!}
								@endif
								@if(strlen($register->content()) >= 300)
									{!! substr(strip_tags($register->content()), 0, strpos(strip_tags($register->content()), ' ', 260)) !!}...
								@endif
							</div>

							<a class="card-view-more" href="{{ route('registers.show', $register->id) }}" title="{{ $register->title() }}">@lang('buttons.readmore')</a>

							@if(Auth::check() && (Auth::id() === ($register->user_id ?? null) || Auth::user()->can('edit_all_registers')))
							<div class="row card-buttons">
								<a class="card-edit-buttons" href="{{ route('registers.edit', $register->id) }}">
									@lang('buttons.edit')
								</a>

								@can('delete_registers')
								<a class="card-edit-buttons" href="{{ route('registers.delete.confirm', $register->id) }}">
									@lang('buttons.delete')
								</a>
								@endcan
							</div>
							@endif
						</div>
					</div>
			</div>
			<!--
			<div class="card-footer text-muted">
				@lang('buttons.created-at')
				{{$register->created_at->formatLocalized('%d/%m/%Y') }}
				@lang('buttons.by')
				@lang('pages.global_author')
			</div>
		-->
		</div>
	</div>
</div>
