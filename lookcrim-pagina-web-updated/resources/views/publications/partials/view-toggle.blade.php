@php
    $isMap = request()->routeIs('publications-map') || request()->is('map');
    $listUrl = route('publications');
    $mapUrl = route('publications-map');
@endphp

<div class="d-flex align-items-center justify-content-end" style="margin: -6px 0 12px 0;">
    <div class="btn-group" role="group" aria-label="{{ __('pages.view_toggle_aria') }}">
        <a
            href="{{ $listUrl }}"
            class="btn btn-sm {{ $isMap ? 'btn-secondary' : 'btn-lookcrim' }}"
            aria-current="{{ $isMap ? 'false' : 'page' }}"
        >
            {{ __('pages.view_list') }}
        </a>
        <a
            href="{{ $mapUrl }}"
            class="btn btn-sm {{ $isMap ? 'btn-lookcrim' : 'btn-secondary' }}"
            aria-current="{{ $isMap ? 'page' : 'false' }}"
        >
            {{ __('pages.view_map') }}
        </a>
    </div>
</div>
