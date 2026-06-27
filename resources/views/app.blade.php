<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'LMS SMK 11 Maret') }}</title>

        <!-- SEO -->
        <meta name="description" content="Learning Management System SMK 11 Maret — Monitoring KBM Real-Time, Presensi QR, Penilaian Kurikulum Merdeka.">
        <meta name="theme-color" content="#0B0F1A">

        <!-- Tema Dinamis dari Database -->
        @php
            try {
                $setting = \Illuminate\Support\Facades\DB::table('app_settings')->first();
                $bg = $setting ? $setting->color_bg : '#0B0F1A';
                $sidebar = $setting ? $setting->color_sidebar : '#111827';
                $card = $setting ? $setting->color_card : '#1A2035';
                $branchName = $setting ? $setting->branch_name : config('app.name', 'LMS SMK 11 Maret');
            } catch (\Exception $e) {
                $bg = '#0B0F1A';
                $sidebar = '#111827';
                $card = '#1A2035';
                $branchName = config('app.name', 'LMS SMK 11 Maret');
            }
        @endphp
        <style>
            :root {
                --bg: {{ $bg }} !important;
                --sidebar: {{ $sidebar }} !important;
                --card: {{ $card }} !important;
            }
        </style>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Inertia Head -->
        @inertiaHead

        <!-- Vite Assets -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- MathJax for chemical and math formula rendering -->
        <script>
            window.MathJax = {
                tex: {
                    inlineMath: [['$', '$'], ['\\(', '\\)']],
                    displayMath: [['$$', '$$'], ['\\[', '\\]']],
                    processEscapes: true,
                    processEnvironments: true
                },
                options: {
                    skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
                }
            };
        </script>
        <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" id="MathJax-script" async></script>
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
