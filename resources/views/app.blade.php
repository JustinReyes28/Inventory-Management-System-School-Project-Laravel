<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'InvControl') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="dns-prefetch" href="https://fonts.bunny.net">

    {{-- React Fast Refresh preamble. @vitejs/plugin-react normally injects this via
         Vite's transformIndexHtml hook, but Laravel renders this Blade view itself,
         so that hook never runs and the app fails with "can't detect preamble". --}}
    @if (file_exists(public_path('hot')))
        <script type="module">
            import RefreshRuntime from '{{ \Illuminate\Support\Facades\Vite::asset('@react-refresh') }}';
            RefreshRuntime.injectIntoGlobalHook(window);
            window.$RefreshReg$ = () => {};
            window.$RefreshSig$ = () => (type) => type;
            window.__vite_plugin_react_preamble_installed__ = true;
        </script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
