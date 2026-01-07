<div class="main-website-interior">

    <h4 class="font-title-for-customization news-title">{{ $publications->title() }}</h4>
    <hr class="interior-title-line news-line-title">

    @php
        $lat = $publications->lat_from_location ?? $publications->latitude ?? null;
        $lng = $publications->lng_from_location ?? $publications->longitude ?? null;
        $authorName = $publications->user->name ?? $publications->user->email ?? null;
        $category = $publications->category ?? null;
    @endphp

    <div class="row publication-show-grid">
        <div class="col-lg-6 mb-3">
            <div class="publication-media image-news-complete">
                @include('partials.publications.image')
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="publication-meta">
                <div class="meta-row news-date complete-news">{{ $publications->created_at->formatLocalized('%d/%m/%Y') }}</div>
                <div class="meta-row"><strong>{{ __('Author') }}:</strong> {{ $authorName ?? __('Unknown') }}</div>
                @if(!empty($category))
                    <div class="meta-row"><strong>{{ __('Category') }}:</strong> {{ $category }}</div>
                @endif
            </div>

            <div id="register-show-map"></div>
            @if(is_null($lat) || is_null($lng))
                <div class="text-muted" style="margin-top:8px;">{{ __('Location not available.') }}</div>
            @endif
        </div>
    </div>

    <div class="news-content">
        {!! $publications->content() !!}
    </div>

    @if(Auth::check() && (Auth::id() === ($publications->user_id ?? null) || Auth::user()->can('edit_all_registers')))
        <div class="row" style="margin-top:14px;">
            <div class="col-12 submit-text">
                <a class="btn btn-lookcrim btn-sm edit-text" href="{{ route('publications-edit', $publications->id) }}">
                    @lang('buttons.edit')
                </a>

                @can('delete_registers')
                    <a class="btn btn-lookcrim-white btn-sm edit-text" href="{{ route('publications-delete', $publications->id) }}">
                        @lang('buttons.delete')
                    </a>
                @endcan
            </div>
        </div>
    @endif
</div>
