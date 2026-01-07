@extends('layouts.legacy')

@section('conteudo')
<div class="container">
    <h1 class="mb-3-form-title">{{ __('pages.page_settings') }}</h1>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @can('create_role')
    <div style="margin-bottom:12px;">
        <a class="btn-lookcrim" href="{{ route('settings.roles.create') }}">{{ __('pages.create_role') }}</a>
    </div>
    @endcan
    <table class="table">
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
                        <a class="btn" href="{{ route('settings.roles.edit', $role->name) }}">{{ __('pages.edit') }}</a>
                    @endcan
                    @can('delete_role')
                        <form action="{{ route('settings.roles.destroy', $role->name) }}" method="POST" style="display:inline-block;margin-left:8px;" onsubmit="return confirm('{{ __('pages.confirm_delete_role') }}');">
                            @csrf
                            @method('DELETE')
                            <button class="btn-danger" type="submit">{{ __('pages.delete') }}</button>
                        </form>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="4">{{ __('pages.no_roles_defined') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    <a href="{{ route('users-list') }}" class="btn-secondary">{{ __('pages.back') }}</a>
</div>
@endsection
