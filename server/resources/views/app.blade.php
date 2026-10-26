<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light dark">
        <meta name="theme-color" content="#FFFFFF">
        <meta name="application-name" content="MARKADO">
        <meta name="description" content="MARKADO secure academic assessment workspace">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/branding/favicon-32x32.png" type="image/png" sizes="32x32">
        <link rel="apple-touch-icon" href="/branding/apple-touch-icon.png" sizes="180x180">

        <script>
            (() => {
                try {
                    const stored = localStorage.getItem('markado-theme');
                    const preference = ['light', 'dark', 'system'].includes(stored) ? stored : 'system';
                    const dark = preference === 'dark'
                        || (preference === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
                    const root = document.documentElement;
                    root.classList.toggle('light', !dark);
                    root.classList.toggle('dark', dark);
                    root.dataset.theme = dark ? 'dark' : 'light';
                    root.dataset.themePreference = preference;
                    document.querySelector('meta[name="theme-color"]')
                        ?.setAttribute('content', dark ? '#0A0A0A' : '#FFFFFF');
                    if (!matchMedia('(prefers-reduced-motion: reduce)').matches) root.classList.add('motion-ok');
                } catch (error) {
                    // The default light theme remains usable when storage is unavailable.
                }
            })();
        </script>

        <title inertia>{{ config('app.name', 'MARKADO') }}</title>

        @viteReactRefresh
        @vite('resources/js/app.jsx')
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
