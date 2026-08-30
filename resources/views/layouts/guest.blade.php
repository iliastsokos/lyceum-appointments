<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @include('partials.pwa-head')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/" class="inline-flex items-center gap-3 bg-[#0e6e73] rounded-xl py-[15px] px-[30px] shadow-sm">
                    <span class="relative inline-flex items-center justify-center w-11 h-11 rounded-full bg-white ring-2 ring-[#f2952b]">
                        <span class="text-[#f2952b] font-extrabold text-lg leading-none">1<span class="align-super text-[9px]">ο</span></span>
                        <svg class="absolute -top-2 left-1/2 -translate-x-1/2 w-4 h-4 text-[#f2952b]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 3 1 8l11 5 9-4.09V17h2V8L12 3Z"/>
                            <path d="M5 10.18v3.64c0 .35.16.68.44.9C6.6 15.5 9 17 12 17s5.4-1.5 6.56-2.28c.28-.22.44-.55.44-.9v-3.64l-7 3.18-7-3.18Z"/>
                        </svg>
                    </span>
                    <span class="leading-tight">
                        <span class="block text-white font-bold text-lg tracking-tight">1ο ΓΕΛ Ραφήνας</span>
                        <span class="block text-[#bfe3e3] text-xs font-medium">Σύστημα Ραντεβού</span>
                    </span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
