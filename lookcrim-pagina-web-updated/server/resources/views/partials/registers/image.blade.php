@php
	$mode = $mode ?? 'gallery';
	$images = null;
	try {
		$images = $register->images ?? null;
	} catch (\Throwable $e) {
		$images = null;
	}

	$imagesCount = (is_countable($images) ? count($images) : 0);
	$legacyImgUrl = method_exists($register, 'image_url') ? $register->image_url() : ($register->image ? asset($register->image) : null);
@endphp

@if($imagesCount > 0)
	@if($mode === 'cover')
		@php
			$first = $images->sortBy(['sort_order', 'id'])->first();
			$url = ($first && method_exists($first, 'url')) ? $first->url() : null;
			$extraCount = max(0, $imagesCount - 1);
		@endphp
		@if(!empty($url))
			<div class="position-relative" style="display:inline-block;width:100%;">
				<a href="#img{{$register->id}}_0" data-toggle="modal">
					<img src="{{ $url }}" alt="" class="img-fluid" />
				</a>
				@if($extraCount > 0)
					<span class="badge badge-dark position-absolute" style="right:8px;bottom:8px;">+{{ $extraCount }}</span>
				@endif
			</div>

			<div class="modal fade" id="img{{$register->id}}_0" tabindex="-1" role="dialog" aria-labelledby="img{{$register->id}}_0label" aria-hidden="true">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						</div>
						<div class="modal-body text-center">
							<img src="{{ $url }}" alt="" class="img-fluid" />
						</div>
					</div>
				</div>
			</div>
		@endif
	@else
		@foreach($images->take(3) as $idx => $img)
			@php $url = method_exists($img, 'url') ? $img->url() : null; @endphp
			@if(!empty($url))
				<a href="#img{{$register->id}}_{{$idx}}" data-toggle="modal">
					<img src="{{ $url }}" alt="" class="img-fluid" />
				</a>

				<div class="modal fade" id="img{{$register->id}}_{{$idx}}" tabindex="-1" role="dialog" aria-labelledby="img{{$register->id}}_{{$idx}}label" aria-hidden="true">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							</div>
							<div class="modal-body text-center">
								<img src="{{ $url }}" alt="" class="img-fluid" />
							</div>
						</div>
					</div>
				</div>
			@endif
		@endforeach
	@endif
@elseif(!empty($legacyImgUrl))
	<a href="#img{{$register->id}}" data-toggle="modal">
		<img src="{{ $legacyImgUrl }}" alt="" class="img-fluid" />
	</a>

	<div class="modal fade" id="img{{$register->id}}" tabindex="-1" role="dialog" aria-labelledby="img{{$register->id}}label" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body text-center">
					<img src="{{ $legacyImgUrl }}" alt="" class="img-fluid" />
				</div>
			</div>
		</div>
	</div>
@endif
@if(!empty($register->get_embed_url()))
	<div class="youtube-video">
		<iframe src="{{ $register->get_embed_url() }}" style="border: 0px; width: 100%; max-width: 100%; height: 394px;" allowfullscreen title="{{ $register->title() }}"></iframe>
	</div>
@endif
