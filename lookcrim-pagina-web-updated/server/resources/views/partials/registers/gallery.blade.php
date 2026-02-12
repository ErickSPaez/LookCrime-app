@php
	$images = null;
	try {
		$images = $register->images ?? null;
	} catch (\Throwable $e) {
		$images = null;
	}

	$imagesCount = (is_countable($images) ? count($images) : 0);
	$legacyImgUrl = method_exists($register, 'image_url') ? $register->image_url() : ($register->image ? asset($register->image) : null);

	$urls = [];
	if ($imagesCount > 0) {
		foreach ($images->take(3) as $img) {
			$url = method_exists($img, 'url') ? $img->url() : null;
			if (!empty($url)) {
				$urls[] = $url;
			}
		}
	} elseif (!empty($legacyImgUrl)) {
		$urls[] = $legacyImgUrl;
	}
@endphp

@if(count($urls) > 0)
	<div class="register-gallery js-register-gallery" data-count="{{ count($urls) }}">
		<button type="button" class="register-gallery-nav register-gallery-prev js-register-gallery-prev" aria-label="{{ __('Previous image') }}">
			<span aria-hidden="true">&lsaquo;</span>
		</button>

		<div class="register-gallery-stage">
			@foreach($urls as $i => $url)
				<img
					src="{{ $url }}"
					alt=""
					class="register-gallery-slide js-register-gallery-slide {{ $i === 0 ? 'is-active' : '' }}"
					data-index="{{ $i }}"
				/>
			@endforeach
		</div>

		<button type="button" class="register-gallery-nav register-gallery-next js-register-gallery-next" aria-label="{{ __('Next image') }}">
			<span aria-hidden="true">&rsaquo;</span>
		</button>

		<div class="register-gallery-counter js-register-gallery-counter" aria-live="polite"></div>
	</div>
@endif

@if(!empty($register->get_embed_url()))
	<div class="register-embed" style="margin-top:14px;">
		<div class="youtube-video">
			<iframe src="{{ $register->get_embed_url() }}" style="border: 0px; width: 100%; max-width: 100%; height: 394px;" allowfullscreen title="{{ $register->title() }}"></iframe>
		</div>
	</div>
@endif
