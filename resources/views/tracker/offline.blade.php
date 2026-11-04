<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Offline — Medixa Medicine Tracker</title>
    <link rel="manifest" href="/manifest-tracker.json">
    <meta name="theme-color" content="#168071">
    @vite(['resources/css/app.css'])
</head>
<body class="h-full flex items-center justify-center p-6 text-center font-sans bg-slate-50">
    <div class="max-w-md w-full bg-white rounded-3xl p-8 shadow-xl border border-slate-100">
        <div class="w-20 h-20 mx-auto rounded-2xl bg-teal-50 border border-teal-100 flex items-center justify-center text-teal-600 mb-6">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 4.243a9 9 0 01-6.364-2.636 9 9 0 010-12.728m0 0l2.829 2.829m-2.829-2.829L3 3m5.657 5.657a5 5 0 017.07 0" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 mb-2">You are currently offline</h2>
        <p class="text-sm text-slate-600 mb-6 leading-relaxed">
            Your Medixa Medicine Tracker app is ready. Once your internet connection is restored, your doses and Recovery Garden will synchronize automatically.
        </p>
        <button onclick="window.location.reload()" class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-teal-600 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-teal-600/25 hover:bg-teal-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            Try Reconnecting
        </button>
    </div>
</body>
</html>

