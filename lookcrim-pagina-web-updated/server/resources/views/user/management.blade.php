@extends('layouts.legacy')

@section('conteudo')
    <div class="main-website-interior user-management-panel">
        <h1 class="font-title-for-customization register-title" style="margin:0;text-align:center;">{{ __('pages.management_title') }}</h1>
        <hr class="interior-title-line register-line-title" style="margin-bottom:18px;">
        <p class="user-management-subtitle" style="text-align:center;">{{ __('pages.management_subtitle') }}</p>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @can('admin')
        <div style="margin-bottom:1rem;">
            <a href="{{ route('users.create') }}" class="btn btn-lookcrim">{{ __('Create User') }}</a>
        </div>

        <form action="{{ route('users.mail.test') }}" method="POST" style="margin-bottom:1rem;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @csrf
            <label for="test_email" style="margin:0;">{{ __('Send test email to') }}</label>
            <input id="test_email" name="test_email" type="email" value="{{ old('test_email') }}" class="form-control" style="max-width:320px;" placeholder="{{ __('Email') }}" required>
            <button type="submit" class="btn btn-lookcrim">{{ __('Send test email') }}</button>
        </form>
        @endcan

        <div class="table-responsive lc-table-responsive">
        <table class="table table-wrapper">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Role') }}</th>
                    <th>{{ __('pages.city') }}</th>
                    <th>{{ __('buttons.created-at') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name ?? $user->nome ?? '-' }}</td>
                        <td>{{ $user->email ?? '-' }}</td>
                        <td>
                            @php
                                $roleNames = $user->roles->pluck('name');
                                $displayRole = $roleNames->contains('admin') ? 'admin' : ($roleNames->first() ?: 'user');
                            @endphp
                            {{ $displayRole }}
                        </td>
                        <td>{{ $user->city?->name ?? '—' }}</td>
                        <td>{{ $user->created_at ? $user->created_at->format('Y-m-d') : '-' }}</td>
                        <td>
                            @can('edit_user')
                                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-lookcrim-white btn-sm">{{ __('Edit') }}</a>
                            @endcan

                            @can('ban_user')
                                @if($user->id !== auth()->id())
                                    <form id="ban-form-{{ $user->id }}" action="{{ route('users.ban', $user->id) }}" method="POST" style="display:inline">
                                        @csrf
                                        <button type="button" class="btn {{ $user->banned ? 'btn-secondary' : 'btn-lookcrim' }} btn-sm lc-confirm-trigger" data-form-id="ban-form-{{ $user->id }}" data-title="{{ $user->banned ? __('Unban user') : __('Ban user') }}" data-message="{{ $user->banned ? __('Are you sure you want to unban this user?') : __('Are you sure you want to ban this user?') }}">{{ $user->banned ? __('Unban') : __('Ban') }}</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">{{ __('No users registered.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        {{ $users->links() }}
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
                    btn.addEventListener('click', function(e){
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
                    if (currentForm) {
                        currentForm.submit();
                    }
                    hideModal();
                });

                // close modal on ESC
                document.addEventListener('keydown', function(e){
                    if (e.key === 'Escape' && backdrop.classList.contains('show')) hideModal();
                });
            })();
        </script>
@endsection
