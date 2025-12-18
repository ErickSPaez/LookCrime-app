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
                    <div class="form-group">
                        <label class="form-label">{{ __('Role') }}</label>
                        <select class="form-input" name="role" id="role-select">
                            @php $selectedRole = old('role', 'user'); @endphp
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ $selectedRole === $role ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $role)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="align-items:center; flex:0 0 auto;">
                        <label class="form-label">{{ __('Admin') }}</label>
                        <input type="checkbox" name="admin" value="1" {{ old('admin') ? 'checked' : '' }}>
                        <small class="form-text text-muted">{{ __('Only needed if you want admin rights aside from role') }}</small>
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
