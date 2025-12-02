@if($news->image !== '')
<a href="#img{{$news->id}}" data-toggle="modal">
	<img src="{{ asset($news->image) }}" alt="{{ $news->title() }}" class="img-fluid">
</a>

<div class="modal fade" id="img{{$news->id}}" tabindex="-1" role="dialog" aria-labelledby="img{{$news->id}}label" aria-hidden="true">
	<div class="modal-dialog" role="document">
	    <div class="modal-content">
	        <div class="modal-header">
	            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        </div>
			<div class="modal-body">
				<img src="{{ asset($news->image) }}" alt="{{ $news->title() }}" class="img-fluid">
			</div>
	    </div>
	</div>
</div>
@endif
@if($news->get_embed_url() != '')
	<div class="youtube-video">
	<iframe src="{{$news->get_embed_url()}}" style="border: 0px; width: 700px; height: 394px;" allowfullscreen title="{{$news->title()}}"></iframe>
	</div>
@endif
