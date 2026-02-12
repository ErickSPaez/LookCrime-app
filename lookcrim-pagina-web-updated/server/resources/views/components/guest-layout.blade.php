<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding-top:24px;padding-bottom:24px;background:#ffffff;">
            <div style="text-align:center;margin-bottom:16px;">
                <a href="/">
                    <x-application-logo />
                </a>
            </div>

                <div style="width:100%;max-width:420px;padding:1.25rem;background:#ffffff;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1),0 4px 6px -2px rgba(0,0,0,0.05);border-radius:0.5rem;">
                    {{ $slot }}
                </div>
        </div>
    </body>
</html>
