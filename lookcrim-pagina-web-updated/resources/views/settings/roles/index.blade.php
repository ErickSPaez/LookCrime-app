@extends('layouts.legacy')

@section('conteudo')
<div class="container">
    <h1 class="mb-3-form-title">{{ __('pages.page_settings') }} — {{ __('pages.roles') }}</h1>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <div style="margin-bottom:12px;">
        <a class="btn-lookcrim" href="{{ route('settings.roles.create') }}">{{ __('pages.create_role') }}</a>
    </div>
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
                <td>{{ $role->slug }}</td>
                <td>{{ $role->nameLocalized() }}</td>
                <td>
                    @php $p = $role->permissions ?? []; @endphp
                    @forelse($p as $k => $v)
                        <span class="badge" style="margin-right:6px; background: {{ $v ? '#2e7d32' : '#9e9e9e' }}; color:#fff;">{{ str_replace('_',' ', $k) }}</span>
                    @empty
                        <em>{{ __('pages.no_permissions') }}</em>
                    @endforelse
                </td>
                <td>
                    <a class="btn" href="{{ route('settings.roles.edit', $role->slug) }}">{{ __('pages.edit') }}</a>
                    <form action="{{ route('settings.roles.destroy', $role->slug) }}" method="POST" style="display:inline-block;margin-left:8px;" onsubmit="return confirm('{{ __('pages.confirm_delete_role') }}');">
                        @csrf
                        @method('DELETE')
                        <button class="btn-danger" type="submit">{{ __('pages.delete') }}</button>
                    </form>
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
