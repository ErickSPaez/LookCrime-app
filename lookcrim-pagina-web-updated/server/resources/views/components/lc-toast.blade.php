@props([
    'message' => null,
    'type' => 'success',
    'timeout' => 3500,
])

@php
    $toastId = 'lc-toast-' . uniqid();

    $bg = match ($type) {
        'success' => '#16a34a',
        'error' => '#dc2626',
        'warning' => '#d97706',
        default => '#111827',
    };
@endphp

@if ($message)
    <div
        id="{{ $toastId }}"
        role="status"
        aria-live="polite"
        style="
            position: fixed;
            left: 16px;
            top: 16px;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 360px;
            padding: 10px 12px;
            border-radius: 10px;
            color: #ffffff;
            background: {{ $bg }};
            box-shadow: 0 10px 22px rgba(0,0,0,0.18);
            font-size: 13px;
            line-height: 1.25;
            opacity: 1;
            transform: translateY(0);
            transition: opacity 240ms ease, transform 240ms ease;
        "
        data-timeout="{{ (int) $timeout }}"
    >
        <div style="flex: 1 1 auto; min-width: 0; word-break: break-word;">
            {{ $message }}
        </div>

        <button
            type="button"
            aria-label="Close notification"
            style="
                flex: 0 0 auto;
                width: 26px;
                height: 26px;
                border-radius: 999px;
                border: 1px solid rgba(255,255,255,0.35);
                background: rgba(255,255,255,0.12);
                color: #ffffff;
                cursor: pointer;
                line-height: 1;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 16px;
            "
            data-close
        >
            ×
        </button>
    </div>

    <script>
        (function () {
            var el = document.getElementById('{{ $toastId }}');
            if (!el) return;

            function closeToast() {
                if (!el) return;
                el.style.opacity = '0';
                el.style.transform = 'translateY(-6px)';
                window.setTimeout(function () {
                    if (el && el.parentNode) el.parentNode.removeChild(el);
                    el = null;
                }, 260);
            }

            var closeBtn = el.querySelector('[data-close]');
            if (closeBtn) closeBtn.addEventListener('click', closeToast);

            var timeout = parseInt(el.getAttribute('data-timeout') || '0', 10);
            if (!Number.isNaN(timeout) && timeout > 0) {
                window.setTimeout(closeToast, timeout);
            }
        })();
    </script>
@endif
