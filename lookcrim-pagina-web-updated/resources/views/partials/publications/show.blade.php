<div class="main-website-interior">

    <!-- Post Content Column -->
    <div class="col-lg-20">

        <!-- Title -->
        <h4 class="font-title-for-customization news-title">
            {{ $publications->title() }}
        </h4>
        <hr class="interior-title-line news-line-title">
        
        <div class="news-date complete-news">
            {{$publications->created_at->formatLocalized('%d/%m/%Y') }}
        </div>

        <div class="image-news-complete">
            @include('partials.publications.image')
        </div>

        <div class="news-content">
            {!! $publications->content() !!}
        </span>

        @if(Auth::check() && Auth::user()->admin)
            <div class="row">
                <div class="col-12 submit-text">
                    <a class="btn btn-lookcrim btn-sm edit-text" href="{{route('publications-edit', $publications->id)}}">
                        @lang('buttons.edit')
                    </a>

                    <a class="btn btn-lookcrim-white btn-sm edit-text" href="{{route('publications-delete', $publications->id)}}">
                        @lang('buttons.delete')
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
