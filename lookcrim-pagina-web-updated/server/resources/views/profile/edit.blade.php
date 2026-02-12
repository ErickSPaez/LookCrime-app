@extends('layouts.legacy')

@section('titulo_browser', __('auth.profile') . ' - LookCrim')

@section('pagestyles')
    {{-- Breeze/Tailwind styles for profile forms + Alpine modal --}}
    @vite(['resources/css/breeze.css'])

    <style>
        .lc-profile-wrap {
            width: min(92%, 980px);
            margin-left: auto;
            margin-right: auto;
            margin-top: 10px;
            margin-bottom: 18px;
        }
        .lc-profile-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }
        .lc-profile-card {
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            padding: 16px;
        }
    </style>
@endsection

@section('conteudo')
    <div class="main-website-interior">
        <h1 class="font-title-for-customization" style="margin:0;text-align:center;">
            {{ __('auth.profile') }}
        </h1>
        <hr class="interior-title-line" style="margin-bottom:18px;">

        <div class="lc-profile-wrap">
            <div class="lc-profile-cards">
                <div class="lc-profile-card">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="lc-profile-card">
                    @include('profile.partials.update-password-form')
                </div>

                <div class="lc-profile-card">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
@endsection
