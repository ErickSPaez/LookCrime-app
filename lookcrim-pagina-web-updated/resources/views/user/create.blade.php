@extends('layouts.legacy')

@section('conteudo')
    <div class="container">
        <h1 class="mb-3-form-title">{{ __('Create User') }}</h1>

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
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input class="form-input" type="text" name="name" value="{{ old('name') }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Email') }}</label>
                        <input class="form-input" type="email" name="email" value="{{ old('email') }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('Password') }}</label>
                        <input class="form-input" type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Confirm Password') }}</label>
                        <input class="form-input" type="password" name="password_confirmation" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('Nickname') }}</label>
                        <input class="form-input" type="text" name="nickname" value="{{ old('nickname') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Institution') }}</label>
                        <input class="form-input" type="text" name="institution" value="{{ old('institution') }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="width:100%">
                        <label class="form-label">{{ __('Role') }}</label>
                        <select class="form-input" name="role" id="role-select">
                            @php $selectedRole = old('role', 'user'); @endphp
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ $selectedRole === $role ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $role)) }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">{{ __('pages.permissions_from_role') }}</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="width:100%">
                        <label class="form-label">{{ __('pages.permissions') }}</label>
                        <div id="permissions-list" class="lc-permissions-grid">
                            @php $roleDefaults = $roleDefinitions[$selectedRole] ?? []; @endphp
                            @foreach($permissionsList as $perm)
                                @if($perm === 'manage_users')
                                    @continue
                                @endif
                                @php
                                    $isChecked = old("permissions.$perm", $roleDefaults[$perm] ?? false);
                                @endphp
                                <div class="permission-item">
                                    <label>
                                        <input type="checkbox" name="permissions[{{ $perm }}]" value="1" {{ $isChecked ? 'checked' : '' }}>
                                        {{ ucfirst(str_replace('_',' ', $perm)) }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn-lookcrim" type="submit">{{ __('Create') }}</button>
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
            const roleDefaults = roleDefinitions[role] || {};
            Object.keys(roleDefaults).forEach(function(k){
                const checkbox = document.querySelector('input[name="permissions[' + k + ']" ]');
                if (checkbox) checkbox.checked = !!roleDefaults[k];
            });
            const adminCheckbox = document.querySelector('input[name="admin"]');
            if (adminCheckbox) {
                adminCheckbox.checked = (role === 'super_usuario');
            }
        }

        roleSelect && roleSelect.addEventListener('change', function(){
            applyRoleDefaults(this.value);
        });

        // initial sync (in case of server defaults)
        applyRoleDefaults(roleSelect ? roleSelect.value : 'user');
    })();
    </script>
    @endsection
@endsection
