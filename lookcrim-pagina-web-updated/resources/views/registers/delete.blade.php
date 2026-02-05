@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.registers'). ' - LookCrim')

@section('pagestyles')
    <style>
        .lc-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 22px;
            z-index: 9999;
        }
        .lc-delete-modal {
            position: relative;
            width: min(560px, 96vw);
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 18px 55px rgba(0,0,0,0.28);
            border: 1px solid rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .lc-delete-modal-body {
            padding: 26px 22px 22px 22px;
            text-align: center;
        }
        .lc-delete-close {
            position: absolute;
            top: 12px;
            left: 12px;
            width: 34px;
            height: 34px;
            border-radius: 17px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #111;
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(0,0,0,0.08);
            font-size: 22px;
            line-height: 1;
        }
        .lc-delete-close:hover {
            background: #fff;
            border-color: rgba(0,0,0,0.14);
        }
        .lc-delete-title {
            font-size: 1.65rem;
            font-weight: 600;
            color: rgb(123,30,33);
            margin: 6px 0 8px 0;
        }
        .lc-delete-line {
            width: min(72%, 420px);
            height: 1px;
            background: rgb(123,30,33);
            margin: 10px auto 18px auto;
            opacity: 0.7;
        }
        .lc-delete-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        .lc-delete-actions form { margin: 0; }
        .lc-delete-actions .btn { min-width: 108px; }
    </style>
@endsection

@section('conteudo')
@php
    $lcCloseUrl = route('registers.show', $register->id);
@endphp

<div class="lc-modal-overlay" id="lc-delete-overlay" aria-hidden="false">
    <div class="lc-delete-modal" role="dialog" aria-modal="true" aria-labelledby="lc-delete-title">
        <a class="lc-delete-close" href="{{ $lcCloseUrl }}" aria-label="{{ __('Close') }}">&times;</a>

        <div class="lc-delete-modal-body">
            <div id="lc-delete-title" class="font-title-for-customization lc-delete-title">
                @lang('buttons.want-to-delete') "{{ $register->title() }}"?
            </div>
            <div class="lc-delete-line" aria-hidden="true"></div>

            <div class="lc-delete-actions">
                <form method="POST" action="{{ route('registers.delete', $register->id) }}">
                    @csrf
                    <input type="hidden" name="confirm" value="yes">
                    <button type="submit" class="btn btn-lookcrim-white btn-sm">{{ Lang::get('buttons.confirm') }}</button>
                </form>

                <form method="POST" action="{{ route('registers.delete', $register->id) }}">
                    @csrf
                    <input type="hidden" name="confirm" value="no">
                    <button type="submit" class="btn btn-lookcrim btn-sm">{{ Lang::get('buttons.cancel') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('pagescripts')
    <script>
        (function(){
            var overlay = document.getElementById('lc-delete-overlay');
            if (!overlay) return;

            // Close on click outside the dialog
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    window.location.href = @json($lcCloseUrl);
                }
            });

            // Close on ESC
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    window.location.href = @json($lcCloseUrl);
                }
            });
        })();
    </script>
@endsection
