<div class="col-md-12 list-item-publications"  data-aos="fade-up" data-aos-duration="1000">
	<div class="card mb-4 card-publications-item">
		<div class="card-body card-publications">
			<div class="px-0">
				<div class="row no-gutters align-items-center">
					<div class="col-12 col-md-4 mb-3 mb-md-0 no-padding-left">
						@if(!empty($register->image_url()))
							<div class="publications-image text-center text-md-left">
								@include('partials.registers.image', ['mode' => 'cover'])
							</div>
						@endif
					</div>
					<div class="col-12 col-md-8 position-relative col-publications px-3 px-md-2">
						<h2 class="title-research title-publications">
							{{ $register->title() }}
						</h2>

						@php
							$user = Auth::user();
							$isOwner = Auth::id() === ($register->user_id ?? null);
							$canEdit = Auth::check() && ($isOwner || ($user && $user->can('edit_all_registers')));
							$canDeleteAny = Auth::check() && $user && ($user->can('delete_any_registers') || $user->can('delete_registers'));
							$canDeleteOwn = Auth::check() && $user && $user->can('delete_own_registers');
							$canDelete = $canDeleteAny || ($isOwner && $canDeleteOwn);
						@endphp
							<div class="card-summary-content content-publications">
								@if(strlen($register->content()) < 300)
									{!! strip_tags($register->content()) !!}
								@endif
								@if(strlen($register->content()) >= 300)
									{!! substr(strip_tags($register->content()), 0, strpos(strip_tags($register->content()), ' ', 260)) !!}...
								@endif
							</div>

							<a class="card-view-more d-inline-block mt-2" href="{{ route('registers.show', $register->id) }}" title="{{ $register->title() }}">@lang('buttons.readmore')</a>

							@if($canEdit || $canDelete)
							<div class="d-flex mt-2 flex-wrap justify-content-start justify-content-md-end">
								@if($canEdit)
									<a class="btn btn-sm btn-outline-secondary mr-2 mb-1" href="{{ route('registers.edit', $register->id) }}">
										@lang('buttons.edit')
									</a>
								@endif

								@if($canDelete)
									<button
										type="button"
										class="btn btn-sm btn-outline-danger mb-1 js-open-register-delete-modal"
										data-register-id="{{ $register->id }}"
										data-register-title="{{ $register->title() }}"
									>
										@lang('buttons.delete')
									</button>
								@endif
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
