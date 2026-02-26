@extends('layouts.legacy')

@section('conteudo')
    <div class="main-website-interior user-management-panel">
        <h1 class="font-title-for-customization register-title" style="margin:0;text-align:center;">{{ __('pages.page_settings') }}</h1>
        <hr class="interior-title-line register-line-title" style="margin-bottom:18px;">
        <div style="display:flex;justify-content:flex-start;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
            @can('create_role')
                <a class="btn btn-lookcrim btn-sm" href="{{ route('settings.roles.create') }}">{{ __('pages.create_role') }}</a>
            @endcan
        </div>
        <p class="user-management-subtitle" style="text-align:center;">&nbsp;</p>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive lc-table-responsive">
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
                        @php
                            $permCount = $role->permissions->count();
                            $detailsId = 'lc-role-perms-' . \Illuminate\Support\Str::slug($role->name);
                            $preferredOrder = ['registers','management','roles','cities'];
                            $groups = $role->permissions
                                ->groupBy(fn ($p) => $p->category ?: 'other');
                            $categories = array_values(array_unique(array_merge($preferredOrder, $groups->keys()->all())));

                            $isPt = str_starts_with(app()->getLocale(), 'pt');
                            $activeLabel = $isPt
                                ? ($permCount === 1 ? 'permissão ativa' : 'permissões ativas')
                                : ($permCount === 1 ? 'active permission' : 'active permissions');
                            $showDetailsLabel = $isPt ? 'Ver detalhes' : 'Show details';
                            $hideDetailsLabel = $isPt ? 'Ocultar detalhes' : 'Hide details';
                        @endphp

                        @if ($permCount === 0)
                            <em>{{ __('pages.no_permissions') }}</em>
                        @else
                            <div class="lc-role-perm-summary">
                                <span class="lc-perm-count">
                                    <strong class="text-success">{{ $permCount }}</strong> {{ $activeLabel }}
                                </span>
                                <button
                                    type="button"
                                    class="btn btn-lookcrim btn-sm lc-perm-toggle"
                                    data-target="{{ $detailsId }}"
                                    data-label-show="{{ $showDetailsLabel }}"
                                    data-label-hide="{{ $hideDetailsLabel }}"
                                    aria-controls="{{ $detailsId }}"
                                    aria-expanded="false"
                                >{{ $showDetailsLabel }}</button>
                            </div>

                            <div id="{{ $detailsId }}" class="lc-role-perm-details" hidden>
                                @foreach($categories as $category)
                                    @continue(!$groups->has($category))
                                    @php
                                        $groupLabel = \Illuminate\Support\Facades\Lang::has('permissions.group.'.$category)
                                            ? __('permissions.group.'.$category)
                                            : ucwords(str_replace('_',' ', (string) $category));
                                        $perms = $groups->get($category);
                                    @endphp

                                    <div class="lc-perm-group">
                                        <div class="lc-perm-group-title"><strong>{{ $groupLabel }}</strong></div>
                                        <ul class="lc-perm-list list-unstyled">
                                            @foreach($perms as $permission)
                                                @php
                                                    $permName = $permission->name;
                                                    $permLabel = \Illuminate\Support\Facades\Lang::has('permissions.'.$permName)
                                                        ? __('permissions.'.$permName)
                                                        : ucwords(str_replace('_',' ', (string) $permName));
                                                @endphp
                                                <li class="lc-perm-item"><span class="lc-perm-check text-success">✔</span> {{ $permLabel }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($role->name !== 'admin')
                            @can('edit_role')
                                <a class="btn btn-outline-secondary lc-btn-edit btn-sm" href="{{ route('settings.roles.edit', $role->name) }}">{{ __('pages.edit') }}</a>
                            @endcan

                            @can('delete_role')
                                <form id="delete-role-form-{{ $role->name }}" action="{{ route('settings.roles.destroy', $role->name) }}" method="POST" style="display:inline-block;margin-left:6px;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-delete btn-sm lc-confirm-trigger" data-form-id="delete-role-form-{{ $role->name }}" data-title="{{ __('Confirm Action') }}" data-message="{{ __('pages.confirm_delete_role') }}">{{ __('pages.delete') }}</button>
                                </form>
                            @endcan
                        @else
                            <span style="color:#6b7280; font-size: 12px;">{{ __('pages.protected_role') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">{{ __('pages.no_roles_defined') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <!-- Confirmation modal -->
    <div id="lc-modal-backdrop" class="lc-modal-backdrop" role="dialog" aria-hidden="true">
        <div class="lc-modal" role="document">
            <h3 id="lc-modal-title">{{ __('Confirm Action') }}</h3>
            <p id="lc-modal-message">{{ __('Are you sure?') }}</p>
            <div class="lc-modal-actions">
                <button id="lc-modal-cancel" class="btn-outline-secondary lc-btn-edit" type="button">{{ __('Cancel') }}</button>
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

            // Toggle permission details in Roles index
            document.addEventListener('click', function (e) {
                var btn = e.target && e.target.closest ? e.target.closest('.lc-perm-toggle') : null;
                if (!btn) return;

                var targetId = btn.getAttribute('data-target');
                if (!targetId) return;

                var details = document.getElementById(targetId);
                if (!details) return;

                var isHidden = details.hasAttribute('hidden');
                var labelShow = btn.getAttribute('data-label-show') || 'Show details';
                var labelHide = btn.getAttribute('data-label-hide') || 'Hide details';
                if (isHidden) {
                    details.removeAttribute('hidden');
                    btn.setAttribute('aria-expanded', 'true');
                    btn.textContent = labelHide;
                } else {
                    details.setAttribute('hidden', '');
                    btn.setAttribute('aria-expanded', 'false');
                    btn.textContent = labelShow;
                }
            });
        })();
    </script>
@endsection
