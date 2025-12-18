@extends('layouts.legacy')

@section('conteudo')
    <div class="container">
        <h1 class="mb-3-form-title">{{ __('Edit User') }} #{{ $user->id }}</h1>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="form-card">
            <form action="{{ route('users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input class="form-input" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Email') }}</label>
                        <input class="form-input" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('New Password (leave blank to keep)') }}</label>
                        <input class="form-input" type="password" name="password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Confirm Password') }}</label>
                        <input class="form-input" type="password" name="password_confirmation">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('Nickname') }}</label>
                        <input class="form-input" type="text" name="nickname" value="{{ old('nickname', $user->nickname) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Institution') }}</label>
                        <input class="form-input" type="text" name="institution" value="{{ old('institution', $user->institution) }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="width:100%">
                        <label class="form-label">{{ __('Role') }}</label>
                        <select class="form-input" name="role" id="role-select">
                            @php $selectedRole = old('role', $user->role ?? 'user'); @endphp
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ $selectedRole === $role ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $role)) }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">{{ __('pages.permissions_from_role') }}</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn-lookcrim" type="submit">{{ __('Save') }}</button>
                    <a href="{{ route('users-list') }}" class="btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    @section('pagescripts')
    <script>
    (function(){
        const roleDefinitions = @json($roleDefinitions);
        const roleSelect = document.getElementById('role-select');

        function applyRoleDefaults(role) {
            // no per-user checkboxes in this UI; function kept to sync admin checkbox if needed in future
            const adminCheckbox = document.querySelector('input[name="admin"]');
            if (adminCheckbox) {
                adminCheckbox.checked = (role === 'super_usuario');
            }
        }

        roleSelect && roleSelect.addEventListener('change', function(){
            applyRoleDefaults(this.value);
        });
    })();
    </script>
    @endsection
@endsection
