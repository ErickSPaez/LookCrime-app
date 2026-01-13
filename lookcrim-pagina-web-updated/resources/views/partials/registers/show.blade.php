<div class="main-website-interior">

    <h4 class="font-title-for-customization register-title">{{ $register->title() }}</h4>
    <hr class="interior-title-line register-line-title">

    @php
        $lat = $register->lat_from_location ?? $register->latitude ?? null;
        $lng = $register->lng_from_location ?? $register->longitude ?? null;
        $authorName = $register->user->name ?? $register->user->email ?? null;
        $category = $register->category ?? null;
    @endphp

    <div class="row publication-show-grid">
        <div class="col-lg-6 mb-3">
            <div class="publication-media register-image-complete">
                @include('partials.registers.image')
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="publication-meta">
                <div class="meta-row register-date complete-register">{{ $register->created_at->formatLocalized('%d/%m/%Y') }}</div>
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

    <div class="register-content">
        {!! $register->content() !!}
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
