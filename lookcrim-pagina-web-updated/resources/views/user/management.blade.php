@extends('layouts.legacy')

@section('conteudo')
    <div class="container">
        <h1>{{ __('User Management') }}</h1>
        <p>{{ __('Basic users list (paged).') }}</p>

        <div style="margin-bottom:1rem;">
            <a href="{{ route('users.create') }}" class="btn btn-lookcrim">{{ __('Create User') }}</a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Email') }}</th>
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
                        <td>{{ $user->created_at ? $user->created_at->format('Y-m-d') : '-' }}</td>
                        <td>
                            <a href="{{ route('users.edit', $user->id) }}" class="btn">{{ __('Edit') }}</a>

                                <form id="ban-form-{{ $user->id }}" action="{{ route('users.ban', $user->id) }}" method="POST" style="display:inline">
                                @csrf
                                <button type="button" class="btn lc-confirm-trigger" data-form-id="ban-form-{{ $user->id }}" data-title="{{ $user->banned ? __('Unban user') : __('Ban user') }}" data-message="{{ $user->banned ? __('Are you sure you want to unban this user?') : __('Are you sure you want to ban this user?') }}">{{ $user->banned ? __('Unban') : __('Ban') }}</button>
                            </form>

                                <form id="pwd-form-{{ $user->id }}" action="{{ route('users.password.create', $user->id) }}" method="POST" style="display:inline;margin-left:6px;">
                                @csrf
                                <button type="button" class="btn lc-confirm-trigger" data-form-id="pwd-form-{{ $user->id }}" data-title="{{ __('Send Password Reset E-mail') }}" data-message="{{ __('Send password reset link to this user?') }}">{{ __('Send Password Reset E-mail') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">{{ __('No users registered.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

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
