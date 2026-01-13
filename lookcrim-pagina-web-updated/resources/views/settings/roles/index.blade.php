@extends('layouts.legacy')

@section('conteudo')
    <div class="main-website-interior user-management-panel">
        <h1 class="font-title-for-customization register-title" style="margin-bottom:0;">{{ __('pages.page_settings') }}</h1>
        <hr class="interior-title-line register-line-title" style="margin-bottom:18px;">
        <p class="user-management-subtitle">&nbsp;</p>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @can('create_role')
            <div style="margin-bottom:1rem;">
                <a class="btn btn-lookcrim" href="{{ route('settings.roles.create') }}">{{ __('pages.create_role') }}</a>
            </div>
        @endcan

        <table class="table table-wrapper">
            <thead>
                <tr>
                    <th>Slug</th>
                    <th>{{ __('pages.name') }}</th>
                    <th>{{ __('pages.permissions') }}</th>
                    <th>{{ __('pages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($roles as $role)
                <tr>
                    <td>{{ $role->name }}</td>
                    <td>{{ $role->nameLocalized() }}</td>
                    <td>
                        @forelse($role->permissions as $permission)
                            <span class="badge" style="margin-right:6px; background: #2e7d32; color:#fff;">{{ str_replace('_',' ', $permission->name) }}</span>
                        @empty
                            <em>{{ __('pages.no_permissions') }}</em>
                        @endforelse
                    </td>
                    <td>
                        @can('edit_role')
                            <a class="btn btn-lookcrim-white btn-sm" href="{{ route('settings.roles.edit', $role->name) }}">{{ __('pages.edit') }}</a>
                        @endcan

                        @can('delete_role')
                            <form id="delete-role-form-{{ $role->name }}" action="{{ route('settings.roles.destroy', $role->name) }}" method="POST" style="display:inline-block;margin-left:6px;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-lookcrim btn-sm lc-confirm-trigger" data-form-id="delete-role-form-{{ $role->name }}" data-title="{{ __('Confirm Action') }}" data-message="{{ __('pages.confirm_delete_role') }}">{{ __('pages.delete') }}</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">{{ __('pages.no_roles_defined') }}</td></tr>
            @endforelse
            </tbody>
        </table>

        <a href="{{ route('users-list') }}" class="btn btn-lookcrim-white btn-sm">{{ __('pages.back') }}</a>
    </div>

    <!-- Confirmation modal -->
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

            document.addEventListener('keydown', function(e){
                if (e.key === 'Escape' && backdrop.classList.contains('show')) hideModal();
            });
        })();
    </script>
@endsection
