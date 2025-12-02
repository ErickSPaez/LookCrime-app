<div class="main-website-interior">
<!-- Post Content Column -->
	<div class="col-lg-20">
		<h1 class="font-title-for-customization interior-title">
			@lang('layout.people')
		</h1>
		<hr class="interior-title-line">

		<!-- Post Content -->
		<p class="lead">
			@if($team != null)
				{!! $team->content() !!}
			@else
				<span class="alert alert-danger" role="alert">@lang('pages.empty-page')</span>
			@endif
		</p>

		<center>
		@if(Auth::check() && Auth::user()->admin && $team !=null)
			<a class="btn btn-lookcrim btn-sm edit-text" href="{{route('team-edit')}}">
				@lang('buttons.edit')
			</a>
		@endif
		</center>

		@if(Auth::check() && Auth::user()->admin && $team ==null)
			<center>
				<a class="btn btn-lookcrim btn-sm edit-text" href="{{route('team-create')}}">
					@lang('buttons.create')
				</a>
			</center>
		@endif

	</div>
</div>
