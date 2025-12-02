<div class="row part section section-box" id="section{{$section->id}}">
	<input type="hidden" name="{{ 'seq'.$section->id }}" value="{{ $section->seq }}" class="seq">
	<div class="col-xl-12">
		<div class="row title">
			<div class="col-xs-12 text-center">
				<span class="up-down-controls">
					<a href="#" class="move-up" data-section-id="{{$section->id}}">
						<span class="fas fa-1x fa-chevron-up color-black"></span>
					</a>
					<a href="#" class="move-down" data-section-id="{{$section->id}}">
						<span class="fas fa-1x fa-chevron-down color-black"></span>
					</a>
				</span>
				<span class="delete-controls">
					<a href="#" class="delete-section" data-newsletter-id="{{$newsletter->id}}" data-section-id="{{$section->id}}">
						<span class="far fa-1x fa-trash-alt color-black fa-with-margin"></span>
					</a>
				</span>
			</div>
		</div>
		<div class="row">
			<div class="col-12 textarea-lang">
				<label for="{{ 'section'.$section->id }}">Notícia</label>
			</div>
			<div class="col-xl-12 col-sm-8 textarea-edit">
				<textarea name="{{ 'section'.$section->id }}" id="{{ 'textmce'.$section->id }}" class="lastArea">{{ old('section'.$section->id, $section->content) }}</textarea>
			</div>
		</div>
		<div class="row">
			<div class="col-12 textarea-lang">
				<label for="{{ 'image'.$section->id }}">Imagem</label>
			</div>
			<div class="col-xl-12 col-sm-8 textarea-edit">
				<input type="file" name="{{ 'image'.$section->id }}" id="{{ 'image'.$section->id }}" />
			</div>
		</div>
	</div>
</div>
