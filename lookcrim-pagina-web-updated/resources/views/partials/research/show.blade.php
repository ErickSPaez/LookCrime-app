<div class="main-website-interior">

    <!-- Post Content Column -->
    <div class="col-lg-20">

        <!-- Title -->
        <h4 class="font-title-for-customization news-title">
            {{ $research->title() }}
        </h4>
        <hr class="interior-title-line news-line-title">

        <div class="news-date complete-news">
            {{$research->created_at->formatLocalized('%d/%m/%Y') }}
        </div>
        
        <div class="image-news-complete">
            @include('partials.research.image')
        </div>

        <div class="news-content">
            {!! $research->content() !!}
        </div>

        @if(Auth::check() && Auth::user()->admin)
        <div class="row">
            <div class="col-12 submit-text">
                <a class="btn btn-lookcrim btn-sm edit-text" href="{{route('research-delete', $research->id)}}">
                    @lang('buttons.delete')
                </a>
                <a class="btn btn-lookcrim btn-sm edit-text" href="{{route('research-edit', $research->id)}}">
                    @lang('buttons.edit')
                </a>
            </div>
        </div>
        @endif

    </div>
</div>
