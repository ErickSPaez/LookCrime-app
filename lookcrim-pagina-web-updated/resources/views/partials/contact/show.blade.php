<div class="main-website-interior">
	<!-- Post Content Column -->
	<div class="col-lg-20">

		<!-- Title -->
		<h4 class="font-title-for-customization interior-title">
			@lang('layout.contact')
		</h4>
		<hr class="interior-title-line">

		<!-- Post Content -->
		@if($contact != null)
			{!! $contact->content() !!}
				<div class="row row-list-research">

					<div class="col-md-3 list-item-research"  data-aos="fade-up">
						<div class="card-body card-with-margin">
				              <img src="https://image.flaticon.com/icons/svg/214/214320.svg" class="icon-contact"></img>
							<div class="card-summary-content contacts-content">
								{!! strip_tags($contact->address) !!}
							</div>
						</div>
					</div>

					<div class="col-md-3 list-item-research"  data-aos="fade-up">
						<div class="card-body card-with-margin">
							<img src="https://image.flaticon.com/icons/svg/148/148956.svg" class="icon-contact"></img>
							<div class="card-summary-content contacts-content">
								{!! strip_tags($contact->office) !!}
							</div>
						</div>
					</div>

					<div class="col-md-3 list-item-research"  data-aos="fade-up">
						<div class="card-body card-with-margin">
							<img src="https://image.flaticon.com/icons/svg/134/134951.svg" class="icon-contact" />
							<div class="card-summary-content contacts-content">
								{!! strip_tags($contact->phone) !!}
							</div>
						</div>
					</div>


					<div class="col-md-3 list-item-research"  data-aos="fade-up">
						<div class="card-body card-with-margin">
							<img src="https://image.flaticon.com/icons/svg/214/214316.svg" class="icon-contact" />
							<div class="card-summary-content contacts-content">
								<a href="mailto:webmaster@lookcrim.com" class="default-link-contacts">
									{!! strip_tags($contact->email) !!}
								</a>
							</div>
						</div>
					</div>

					<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3003.2471644915718!2d-8.613256684580875!3d41.172775979284474!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd24644e96bfbb8d%3A0x1b56312fb4975696!2sUniversidade+Fernando+Pessoa!5e0!3m2!1spt-PT!2spt!4v1554721809499!5m2!1spt-PT!2spt" width="100%" height="450" frameborder="0" style="border:0" allowfullscreen></iframe>

					<div class="col-12 warning-message">
						@if(Auth::check() && Auth::user()->admin && $contact != null)
							<a class="btn btn-lookcrim btn-sm edit-text" href="{{route('contact-edit')}}">
								@lang('buttons.edit')
							</a>
						@endif

						@if(Auth::check() && Auth::user()->admin && $contact ==null)
							<a class="btn btn-lookcrim btn-sm edit-text" href="{{route('contact-create')}}">
								@lang('buttons.create')
							</a>
						@endif
					</div>
				</div>
			@else
				<span class="alert alert-danger" role="alert">@lang('pages.empty-page')</span>
				@if(Auth::check() && Auth::user()->admin && $contact ==null)

					<div class="col-12 warning-message">
						<a class="btn btn-lookcrim btn-sm edit-text" href="{{route('contact-create')}}">
							@lang('buttons.create')
						</a>
					</div>
				@endif

			@endif
	</div>
</div>
