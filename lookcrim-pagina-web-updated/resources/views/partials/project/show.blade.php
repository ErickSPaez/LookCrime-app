<div class="main-website-interior">
	<!-- Post Content Column -->
	<div class="col-lg-20">

		<!-- Title -->
		<h4 class="font-title-for-customization interior-title">
			@lang('layout.theproject')
		</h4>
		<hr class="interior-title-line">

		<!-- Post Content -->
		<p class="lead">
			@if($project != null)
				{!! $project->content() !!}
			@else
				<span class="alert alert-danger" role="alert">@lang('pages.empty-page')</span>
			@endif
		</p>
		<div class="project-logos">
			<a class="col-4 col-logos-homepage" href="http://opvcufp.com" target="_blank">
				<img src="{{ asset('img/logos/logo-opvc.png') }}" alt="Homepage Image">
			</a>

			<a class="col-4 col-logos-homepage last-logo" href="http://fct.pt" target="_blank">
				<img src="{{ asset('img/logos/logo-fct.png') }}" alt="Homepage Image">
			</a>

			<a class="col-4 col-logos-homepage">
				<img src="{{ asset('img/logos/logo-rp.png') }}" alt="Homepage Image">
			</a>
		</div>
		<center>
			@if(Auth::check() && Auth::user()->admin && $project !=null)
				@if (Route::has('edit-project'))
					<a class="btn btn-lookcrim btn-sm edit-text" href="{{ route('edit-project') }}">
						@lang('buttons.edit')
					</a>
				@endif
			@endif

			@if(Auth::check() && Auth::user()->admin && $project ==null)

				@if (Route::has('create-project'))
					<a class="btn btn-lookcrim btn-sm edit-text" href="{{ route('create-project') }}">
						@lang('buttons.create')
					</a>
				@endif
			@endif
		</center>

	</div>
</div>
