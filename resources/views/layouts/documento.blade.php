<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>
            @isset($title)
                {{ $title }} · {{ config('app.name', 'Educa') }}
            @else
                {{ config('app.name', 'Educa') }}
            @endisset
        </title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .documento-imprimible {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @media print {
                @page {
                    size: landscape;
                    margin: 10mm;
                }

                html,
                body {
                    background: #fff !important;
                    margin: 0;
                    padding: 0;
                }

                .no-print {
                    display: none !important;
                }

                .documento-imprimible {
                    box-shadow: none !important;
                    border: 0 !important;
                    padding: 0 !important;
                }
            }
        </style>
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased">
        {{ $slot }}
    </body>
</html>
