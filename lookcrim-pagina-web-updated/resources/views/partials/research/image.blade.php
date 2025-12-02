@if($research->image !== '')
<img src="{{ asset($research->image) }}" alt="{{ $research->title() }}" class="img-fluid">

<div class="modal fade" id="img{{$research->id}}" tabindex="-1" role="dialog" aria-labelledby="img{{$research->id}}label" aria-hidden="true">
	<div class="modal-dialog" role="document">
	    <div class="modal-content">
	        <div class="modal-header">
	            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        </div>
			<div class="modal-body">
				<img src="{{ asset($research->image) }}" alt="{{ $research->title() }}" class="img-fluid">
			</div>
	    </div>
	</div>
</div>
@endif
@if($research->get_embed_url() != '')
<div class="youtube-video">
	<iframe src="{{ $research->get_embed_url() }}" style="border: 0px; width: 700px; height: 394px;" allowfullscreen title="{{ $research->title() }}"></iframe>
</div>
@endif
