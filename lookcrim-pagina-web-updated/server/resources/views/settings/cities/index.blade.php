@extends('layouts.legacy')

@section('conteudo')
    <div class="main-website-interior user-management-panel">
        <h1 class="font-title-for-customization register-title" style="margin:0;text-align:center;">{{ __('pages.city_settings_title') }}</h1>
        <hr class="interior-title-line register-line-title" style="margin-bottom:18px;">
        <div style="display:flex;justify-content:flex-start;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
            @can('create_city')
                <a class="btn btn-lookcrim btn-sm" href="{{ route('settings.city.create') }}">{{ __('pages.create_city') }}</a>
            @endcan
        </div>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <table class="table table-wrapper">
            <thead>
                <tr>
                    <th>Slug</th>
                    <th>{{ __('pages.name') }}</th>
                    <th>{{ __('pages.city_center') }}</th>
                    <th>{{ __('pages.radius_km') }}</th>
                    <th>{{ __('buttons.created-at') }}</th>
                    <th>{{ __('pages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($cities as $city)
                <tr>
                    <td>{{ $city->slug }}</td>
                    <td>{{ $city->name }}</td>
                    <td>{{ number_format((float)$city->center_lat, 5) }}, {{ number_format((float)$city->center_lng, 5) }}</td>
                    <td>{{ number_format(((int)$city->radius_m)/1000, 2) }}</td>
                    <td>{{ $city->created_at?->format('Y-m-d') ?? '-' }}</td>
                    <td>
                        @can('edit_city')
                            <a class="btn btn-lookcrim-white btn-sm" href="{{ route('settings.city.edit', $city->slug) }}">{{ __('pages.edit') }}</a>
                        @endcan

                        @can('delete_city')
                            <form id="delete-city-form-{{ $city->slug }}" action="{{ route('settings.city.destroy', $city->slug) }}" method="POST" style="display:inline-block;margin-left:6px;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-lookcrim btn-sm lc-confirm-trigger" data-form-id="delete-city-form-{{ $city->slug }}" data-title="{{ __('Confirm Action') }}" data-message="{{ __('pages.confirm_delete_city') }}">{{ __('pages.delete') }}</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('pages.no_cities_defined') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Confirmation modal (reused) -->
    <div id="lc-modal-backdrop" class="lc-modal-backdrop" role="dialog" aria-hidden="true">
        <div class="lc-modal" role="document">
            <h3 id="lc-modal-title">{{ __('Confirm Action') }}</h3>
            <p id="lc-modal-message">{{ __('Are you sure?') }}</p>
            <div class="lc-modal-actions">
                <button id="lc-modal-cancel" class="lc-btn-cancel" type="button">{{ __('Cancel') }}</button>
                <button id="lc-modal-confirm" class="lc-btn-primary" type="button">{{ __('Confirm') }}</button>
            </div>
        </div>
    </div>

    <script>
        (function(){
            const backdrop = document.getElementById('lc-modal-backdrop');
            const titleEl = document.getElementById('lc-modal-title');
            const msgEl = document.getElementById('lc-modal-message');
            const btnConfirm = document.getElementById('lc-modal-confirm');
            const btnCancel = document.getElementById('lc-modal-cancel');
            let currentForm = null;

            const defaultTitle = @json(__('Confirm Action'));
            const defaultMessage = @json(__('Are you sure?'));

            function showModal(title, message, form) {
                titleEl.textContent = title || defaultTitle;
                msgEl.textContent = message || defaultMessage;
                currentForm = form;
                backdrop.classList.add('show');
                backdrop.setAttribute('aria-hidden','false');
            }

            function hideModal() {
                backdrop.classList.remove('show');
                backdrop.setAttribute('aria-hidden','true');
                currentForm = null;
            }

            document.querySelectorAll('.lc-confirm-trigger').forEach(function(btn){
                btn.addEventListener('click', function(){
                    const formId = this.getAttribute('data-form-id');
                    const title = this.getAttribute('data-title') || defaultTitle;
                    const message = this.getAttribute('data-message') || defaultMessage;
                    const form = document.getElementById(formId);
                    if (!form) return;
                    showModal(title, message, form);
                });
            });

            btnCancel.addEventListener('click', function(){ hideModal(); });
            btnConfirm.addEventListener('click', function(){
                if (currentForm) currentForm.submit();
                hideModal();
            });
        })();
    </script>
@endsection
