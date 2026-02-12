<div class="main-website-interior">

    @php
        $user = Auth::user();
        $isOwner = Auth::id() === ($register->user_id ?? null);
        $canEdit = Auth::check() && ($isOwner || ($user && $user->can('edit_all_registers')));
        $canDeleteAny = Auth::check() && $user && ($user->can('delete_any_registers') || $user->can('delete_registers'));
        $canDeleteOwn = Auth::check() && $user && $user->can('delete_own_registers');
        $canDelete = $canDeleteAny || ($isOwner && $canDeleteOwn);
    @endphp

    <h1 class="font-title-for-customization register-title" style="margin:0;text-align:center;">{{ $register->title() }}</h1>
    <hr class="interior-title-line register-line-title" style="margin-bottom:18px;">

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

    @if($canEdit || $canDelete)
        <div style="display:flex;justify-content:center;gap:8px;align-items:center;flex-wrap:wrap;margin-top:18px;">
            @if($canEdit)
                <a class="btn btn-lookcrim-white btn-sm edit-text" href="{{ route('registers.edit', $register->id) }}">
                    @lang('buttons.edit')
                </a>
            @endif

            @if($canDelete)
                <button
                    type="button"
                    class="btn btn-lookcrim btn-sm edit-text js-open-register-delete-modal"
                    data-register-id="{{ $register->id }}"
                    data-register-title="{{ $register->title() }}"
                >
                    @lang('buttons.delete')
                </button>
            @endif
        </div>
    @endif

</div>

@include('partials.registers.delete-modal')
