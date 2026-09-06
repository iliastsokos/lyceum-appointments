<!DOCTYPE html>
<html lang="el">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="description" content="Ηλεκτρονική πλατφόρμα κλεισίματος ραντεβού γονέων – εκπαιδευτικών του 1ου ΓΕΛ Ραφήνας.">

        <title>{{ config('app.name') }} &middot; 1ο ΓΕΛ Ραφήνας</title>

        @include('partials.pwa-head')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans bg-surface min-h-dvh flex flex-col pt-safe">

        <div class="flex-1 flex flex-col items-center justify-center px-6 py-8 text-center">
            <div class="inline-flex items-center justify-center bg-primary rounded-2xl px-8 py-5 shadow-sm">
                <img src="{{ asset('images/logo-rafin.png') }}" alt="1ο ΓΕΛ Ραφήνας" class="h-12 sm:h-14 w-auto">
            </div>

            <h1 class="mt-6 text-2xl sm:text-3xl font-bold text-ink">Σύστημα Ραντεβού</h1>

            <p class="mt-3 w-full max-w-sm text-base text-body leading-relaxed">
                Ηλεκτρονική πλατφόρμα κλεισίματος ραντεβού ανάμεσα σε κηδεμόνες και εκπαιδευτικούς του σχολείου. Οι κωδικοί πρόσβασης δίνονται από τη Διεύθυνση.
            </p>

            <a href="{{ route('login') }}" class="mt-8 inline-flex items-center justify-center w-full max-w-xs px-6 py-3.5 bg-secondary rounded-xl font-semibold text-base text-white shadow-sm hover:bg-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 transition">
                Σύνδεση
            </a>
        </div>

        <footer class="pb-safe pb-4 px-6 text-center text-xs text-muted">
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
                <a href="/user-guides/odigos-kidemona.pdf" target="_blank" rel="noopener" class="hover:text-primary transition">📄 Οδηγός Κηδεμόνα</a>
                <a href="/user-guides/odigos-ekpaideftikou.pdf" target="_blank" rel="noopener" class="hover:text-primary transition">📄 Οδηγός Εκπαιδευτικού</a>
                <a href="https://lyk-rafin-new.att.sch.gr/" target="_blank" rel="noopener noreferrer" class="hover:text-primary transition">Ιστοσελίδα Σχολείου</a>
            </div>
            <p class="mt-2">&copy; {{ date('Y') }} 1ο ΓΕΛ Ραφήνας</p>
        </footer>
    </body>
</html>
