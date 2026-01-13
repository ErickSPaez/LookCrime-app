<div class="main-website-interior">

    <h4 class="font-title-for-customization register-title">{{ $register->title() }}</h4>
    <hr class="interior-title-line register-line-title">

    @php
        $lat = $register->lat_from_location ?? $register->latitude ?? null;
        $lng = $register->lng_from_location ?? $register->longitude ?? null;
        $authorName = $register->user->name ?? $register->user->email ?? null;
        $category = $register->category ?? null;

        $categoryLabel = null;
        if (!empty($category)) {
            $translated = __('pages.' . $category);
            $categoryLabel = ($translated === ('pages.' . $category)) ? $category : $translated;
        }
    @endphp

    <div class="register-narrow">
        <div class="register-meta-bar">
            <div class="register-meta-left">
                <div class="register-author">
                    <img class="register-author-photo" src="{{ asset('img/user-photo.jpg') }}" alt="" />
                    <div class="register-author-name">{{ $authorName ?? __('Unknown') }}</div>
                </div>

                <div class="register-date">{{ $register->created_at->formatLocalized('%d/%m/%Y') }}</div>
            </div>

            <div class="register-meta-right">
                @if(!empty($categoryLabel))
                    <div class="register-category"><strong>{{ __('pages.category') }}:</strong> {{ $categoryLabel }}</div>
                @endif
            </div>
        </div>

        <div class="register-description">
            {!! $register->content() !!}
        </div>
    </div>

    <div class="register-media-center">
        @include('partials.registers.gallery')
    </div>

    <div class="register-map-block">
        <div id="register-show-map"></div>
        @if(is_null($lat) || is_null($lng))
            <div class="text-muted" style="margin-top:8px;">{{ __('Location not available.') }}</div>
        @endif
    </div>

    @if(Auth::check() && (Auth::id() === ($register->user_id ?? null) || Auth::user()->can('edit_all_registers')))
        <div class="row" style="margin-top:14px;">
            <div class="col-12 submit-text">
                <a class="btn btn-lookcrim btn-sm edit-text" href="{{ route('registers.edit', $register->id) }}">
                    @lang('buttons.edit')
                </a>

                @can('delete_registers')
                    <a class="btn btn-lookcrim-white btn-sm edit-text" href="{{ route('registers.delete.confirm', $register->id) }}">
                        @lang('buttons.delete')
                    </a>
                @endcan
            </div>
        </div>
    @endif
</div>
