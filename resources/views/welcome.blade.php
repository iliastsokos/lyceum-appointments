<!DOCTYPE html>
<html lang="el">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Ηλεκτρονική πλατφόρμα κλεισίματος ραντεβού γονέων – εκπαιδευτικών του 1ου ΓΕΛ Ραφήνας.">

        <title>{{ config('app.name') }} &middot; 1ο ΓΕΛ Ραφήνας</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans text-slate-700" x-data="{ mobileMenuOpen: false }">

        {{-- ============================= HEADER ============================= --}}
        <header class="sticky top-0 z-40 bg-[#0e6e73] shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-18 py-2">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 shrink-0">
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

                    <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-white/90">
                        <a href="#how-it-works" class="hover:text-white transition">Πώς Λειτουργεί</a>
                        <a href="#for-you" class="hover:text-white transition">Για Όλους</a>
                        <a href="#contact" class="hover:text-white transition">Επικοινωνία</a>
                    </nav>

                    <div class="hidden md:flex items-center gap-3">
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-semibold text-white bg-[#f2952b] rounded-md shadow-sm hover:bg-[#e08419] transition">
                            Σύνδεση
                        </a>
                    </div>

                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white" aria-label="Μενού">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div x-show="mobileMenuOpen" x-cloak class="md:hidden pb-4 space-y-1">
                    <a href="#how-it-works" class="block px-3 py-2 rounded-md text-white/90 font-medium hover:bg-white/10">Πώς Λειτουργεί</a>
                    <a href="#for-you" class="block px-3 py-2 rounded-md text-white/90 font-medium hover:bg-white/10">Για Όλους</a>
                    <a href="#contact" class="block px-3 py-2 rounded-md text-white/90 font-medium hover:bg-white/10">Επικοινωνία</a>
                    <div class="pt-3 mt-3 border-t border-white/15 flex flex-col gap-2">
                        <a href="{{ route('login') }}" class="px-3 py-2 rounded-md text-white font-semibold text-center bg-[#f2952b]">Σύνδεση</a>
                    </div>
                </div>
            </div>
        </header>

        {{-- ============================= HERO ============================= --}}
        <section class="relative overflow-hidden bg-[#f4f7f7]">
            {{-- decorative hexagons --}}
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <div class="absolute -left-10 top-10 w-40 h-40 bg-[#f2952b]/10" style="clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);"></div>
                <div class="absolute right-10 -top-6 w-28 h-28 border-4 border-[#0e6e73]/10" style="clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);"></div>
                <div class="absolute right-1/4 bottom-0 w-52 h-52 bg-[#0e6e73]/5" style="clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);"></div>
                <div class="absolute left-1/3 bottom-10 w-16 h-16 border-4 border-[#f2952b]/20" style="clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="inline-block px-3 py-1 rounded-full bg-[#0e6e73]/10 text-[#0e6e73] text-xs font-bold uppercase tracking-wider">
                        ΓΕΛ Ραφήνας
                    </span>
                    <h1 class="mt-5 text-4xl sm:text-5xl font-extrabold text-slate-800 leading-tight">
                        Σύστημα προγραμματισμού συναντήσεων με τους/τις εκπαιδευτικούς
                    </h1>
                    <p class="mt-5 text-lg text-slate-600 max-w-xl">
                        Κλείστε ραντεβού με τους εκπαιδευτικούς του σχολείου σε λίγα κλικ, δείτε άμεσα ποιες ώρες είναι διαθέσιμες.
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-md bg-[#0e6e73] text-white font-semibold shadow-sm hover:bg-[#0b5a5e] transition">
                            Σύνδεση
                        </a>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">
                        Οι λογαριασμοί κηδεμόνων και εκπαιδευτικών δημιουργούνται από τη Διεύθυνση του σχολείου — θα λάβετε τα στοιχεία σύνδεσής σας από εκείνη.
                    </p>
                </div>

                <div class="relative">
                    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6 sm:p-8">
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="font-bold text-slate-800">Επιλέξτε ώρα</h3>
                            <span class="text-xs font-semibold text-[#0e6e73] bg-[#0e6e73]/10 px-2 py-1 rounded">Διαθέσιμες ώρες</span>
                        </div>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach (['10:00','10:05','10:10','10:15','10:20','10:25','10:30','10:35'] as $i => $time)
                                <div class="text-center text-sm font-medium rounded-md py-2 border {{ $i === 2 ? 'bg-red-50 text-red-600 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                    {{ $time }}
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-4 text-xs text-slate-400">Παράδειγμα επιλογής διαθέσιμων πεντάλεπτων ραντεβού.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================= HOW IT WORKS ============================= --}}
        <section id="how-it-works" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-800 uppercase tracking-wide">Πώς Λειτουργεί</h2>
                    <div class="mt-3 mx-auto w-16 h-1 bg-[#f2952b] rounded-full"></div>
                </div>

                <div class="mt-14 grid md:grid-cols-3 gap-8">
                    @php
                        $steps = [
                            ['n' => '1', 'title' => 'Λάβετε τα στοιχεία σας', 'text' => 'Η Διεύθυνση δημιουργεί τον λογαριασμό σας και σας δίνει προσωρινό κωδικό. Συνδέεστε και τον αλλάζετε με έναν δικό σας.'],
                            ['n' => '2', 'title' => 'Επιλέξτε ώρα', 'text' => 'Δείτε τη διαθεσιμότητα κάθε εκπαιδευτικού σε πραγματικό χρόνο και κλείστε ένα ραντεβού πέντε λεπτών με λίγα κλικ.'],
                            ['n' => '3', 'title' => 'Δείτε την ενημέρωση', 'text' => 'Εκπαιδευτικοί και κηδεμόνες βλέπουν αμέσως κάθε νέο ραντεβού ή ακύρωση μέσα στην πλατφόρμα.'],
                        ];
                    @endphp
                    @foreach ($steps as $step)
                        <div class="text-center px-4">
                            <div class="mx-auto w-14 h-14 rounded-full bg-[#0e6e73] text-white flex items-center justify-center text-xl font-extrabold shadow-sm">
                                {{ $step['n'] }}
                            </div>
                            <h3 class="mt-5 font-bold text-slate-800">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm text-slate-500">{{ $step['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ============================= FOR YOU ============================= --}}
        <section id="for-you" class="py-20 bg-[#f4f7f7]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-800 uppercase tracking-wide">Για Όλη τη Σχολική Κοινότητα</h2>
                    <div class="mt-3 mx-auto w-16 h-1 bg-[#f2952b] rounded-full"></div>
                </div>

                <div class="mt-14 grid md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-7">
                        <div class="w-11 h-11 rounded-lg bg-[#0e6e73]/10 flex items-center justify-center text-[#0e6e73]">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                        <h3 class="mt-4 font-bold text-slate-800">Για Κηδεμόνες</h3>
                        <ul class="mt-3 space-y-2 text-sm text-slate-500">
                            <li>&bull; Διαχειριστείτε όλα τα παιδιά σας από ένα σημείο</li>
                            <li>&bull; Κλείστε και ακυρώστε ραντεβού εύκολα</li>
                            <li>&bull; Ειδοποιήσεις για κάθε αλλαγή</li>
                        </ul>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-7">
                        <div class="w-11 h-11 rounded-lg bg-[#f2952b]/10 flex items-center justify-center text-[#f2952b]">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="mt-4 font-bold text-slate-800">Για Εκπαιδευτικούς</h3>
                        <ul class="mt-3 space-y-2 text-sm text-slate-500">
                            <li>&bull; Ορίστε τη διαθεσιμότητά σας ανά πέντε λεπτά</li>
                            <li>&bull; Δείτε όλα τα ραντεβού σας σε ένα πίνακα</li>
                            <li>&bull; Ειδοποιηθείτε για νέα ραντεβού και ακυρώσεις</li>
                        </ul>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-7">
                        <div class="w-11 h-11 rounded-lg bg-[#0e6e73]/10 flex items-center justify-center text-[#0e6e73]">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <h3 class="mt-4 font-bold text-slate-800">Για τη Διεύθυνση</h3>
                        <ul class="mt-3 space-y-2 text-sm text-slate-500">
                            <li>&bull; Διαχείριση λογαριασμών εκπαιδευτικών και κηδεμόνων</li>
                            <li>&bull; Μαζική εισαγωγή μέσω Excel</li>
                            <li>&bull; Στατιστικά στοιχεία και επίβλεψη</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================= CTA BAND ============================= --}}
        <section class="bg-[#0e6e73]">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14 text-center">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">Είστε έτοιμοι να ξεκινήσετε;</h2>
                <p class="mt-3 text-[#bfe3e3]">Συνδεθείτε με τα στοιχεία που σας έδωσε η Διεύθυνση και κλείστε το πρώτο σας ραντεβού σήμερα.</p>
                <div class="mt-7 flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-md bg-[#f2952b] text-white font-semibold shadow-sm hover:bg-[#e08419] transition">
                        Σύνδεση
                    </a>
                </div>
            </div>
        </section>

        {{-- ============================= FOOTER ============================= --}}
        <footer id="contact" class="bg-[#0b4d51] text-[#cfe6e6]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 grid sm:grid-cols-2 lg:grid-cols-4 gap-10">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="relative inline-flex items-center justify-center w-9 h-9 rounded-full bg-white ring-2 ring-[#f2952b]">
                            <span class="text-[#f2952b] font-extrabold text-sm leading-none">1<span class="align-super text-[7px]">ο</span></span>
                        </span>
                        <span class="text-white font-bold">ΓΕΛ Ραφήνας</span>
                    </div>
                    <p class="mt-4 text-sm leading-relaxed">
                        Ηλεκτρονική πλατφόρμα κλεισίματος ραντεβού του 1ου Γενικού Λυκείου Ραφήνας.
                    </p>
                </div>

                <div>
                    <h4 class="text-white font-bold text-sm uppercase tracking-wide">Σύνδεσμοι</h4>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="{{ route('login') }}" class="hover:text-white transition">Σύνδεση</a></li>
                        <li><a href="https://lyk-rafin-new.att.sch.gr/" target="_blank" rel="noopener noreferrer" class="hover:text-white transition">Επίσημη Ιστοσελίδα Σχολείου</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold text-sm uppercase tracking-wide">Στοιχεία Επικοινωνίας</h4>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li>Αγίου Χρυσοστόμου Σμύρνης 10, Ραφήνα, Τ.Κ. 19009</li>
                        <li><a href="tel:2294028948" class="hover:text-white transition">2294028948</a></li>
                        <li><a href="mailto:mail@lyk-rafin.att.sch.gr" class="hover:text-white transition">mail@lyk-rafin.att.sch.gr</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold text-sm uppercase tracking-wide">Ρόλοι</h4>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li>Κηδεμόνες</li>
                        <li>Εκπαιδευτικοί</li>
                        <li>Διοίκηση</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 text-xs text-[#9fc9c9] flex flex-col sm:flex-row items-center justify-between gap-2">
                    <span>&copy; {{ date('Y') }} 1ο ΓΕΛ Ραφήνας &mdash; Σύστημα Ραντεβού</span>
                    <span>Europe/Athens</span>
                </div>
            </div>
        </footer>
    </body>
</html>
