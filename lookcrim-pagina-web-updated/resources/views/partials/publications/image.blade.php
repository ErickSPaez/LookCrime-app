@php $imgUrl = method_exists($publications, 'image_url') ? $publications->image_url() : ($publications->image ? asset($publications->image) : null); @endphp
@if(!empty($imgUrl))
	<a href="#img{{$publications->id}}" data-toggle="modal">
		<img src="{{ $imgUrl }}" alt="" class="img-fluid" />
	</a>

	<div class="modal fade" id="img{{$publications->id}}" tabindex="-1" role="dialog" aria-labelledby="img{{$publications->id}}label" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body text-center">
					<img src="{{ $imgUrl }}" alt="" class="img-fluid" />
				</div>
			</div>
		</div>
	</div>
@endif
@if(!empty($publications->get_embed_url()))
	<div class="youtube-video">
		<iframe src="{{ $publications->get_embed_url() }}" style="border: 0px; width: 700px; height: 394px;" allowfullscreen title="{{ $publications->title() }}"></iframe>
	</div>
@endif
