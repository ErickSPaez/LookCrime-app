@php
    $isMap = request()->routeIs('registers.map') || request()->is('map');
    $listUrl = route('registers.index');
    $mapUrl = route('registers.map');
@endphp

<div class="d-flex align-items-center justify-content-end" style="margin: 0 0 12px 0;">
    <div class="btn-group lc-view-toggle" role="group" aria-label="{{ __('pages.view_toggle_aria') }}">
        <a
            href="{{ $listUrl }}"
            class="btn btn-sm {{ $isMap ? 'btn-lookcrim-white lc-toggle-inactive' : 'btn-lookcrim lc-toggle-active' }}"
            aria-current="{{ $isMap ? 'false' : 'page' }}"
        >
            {{ __('pages.view_list') }}
        </a>
        <a
            href="{{ $mapUrl }}"
            class="btn btn-sm {{ $isMap ? 'btn-lookcrim lc-toggle-active' : 'btn-lookcrim-white lc-toggle-inactive' }}"
            aria-current="{{ $isMap ? 'page' : 'false' }}"
        >
            {{ __('pages.view_map') }}
        </a>
    </div>
</div>
