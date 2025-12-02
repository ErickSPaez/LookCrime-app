<div class="main-website-interior">

    <!-- Post Content Column -->
    <div class="col-lg-20">

        <!-- Title -->
        <h4 class="font-title-for-customization news-title">
            {{ $news->title() }}
        </h4>
        <hr class="interior-title-line news-line-title">

        <!-- Date/Time -->
        <div class="news-date complete-news">
            <span>{{$news->created_at->formatLocalized('%d/%m/%Y') }}</span>
        </div>

        <!-- Preview Image -->
        <div class="image-news-complete">
            @include('partials.news.image')
        </div>

        <div class="news-content">
            {!! $news->content() !!}
        </div>

        @if(Auth::check() && Auth::user()->admin)
        <div class="row">
            <div class="col-12 submit-text">
                @if (Route::has('news-edit'))
                <a class="btn btn-lookcrim btn-sm edit-text" href="{{ route('news-edit', $news->id) }}">
                    @lang('buttons.edit')
                </a>
                @endif

                @if (Route::has('news-delete'))
                <a class="btn btn-lookcrim-white btn-sm edit-text" href="{{ route('news-delete', $news->id) }}">
                    @lang('buttons.delete')
                </a>
                @endif
            </div>
        </div>
        @endif

    </div>
</div>
