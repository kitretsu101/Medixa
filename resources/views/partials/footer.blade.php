<footer class="mt-auto border-t border-stone-200 bg-[#f3f0ea]">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-3">
            <div>
                <div class="mb-4 flex items-center gap-2.5">
                    <x-application-logo class="h-8 w-8" />
                    <span class="font-display text-xl font-semibold text-stone-800">Medixa</span>
                </div>
                <p class="text-sm leading-relaxed text-stone-500">
                    A quieter OPD desk: live queues, consent-scoped records, and reports that stay private.
                </p>
            </div>

            <div>
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-stone-700">Visit</h3>
                <ul class="space-y-2">
                    <li><a href="{{ route('home') }}" class="text-sm text-stone-500 hover:text-[#3f7f72]">Home</a></li>
                    <li><a href="{{ route('doctors.index') }}" class="text-sm text-stone-500 hover:text-[#3f7f72]">Find a doctor</a></li>
                </ul>
            </div>

            <div>
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-stone-700">Emergency</h3>
                <p class="text-sm text-stone-500">24/7 emergency line</p>
                <p class="mt-1 text-lg font-semibold text-[#3f7f72]">+880 1800-MEDIXA</p>
            </div>
        </div>

        <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-stone-200 pt-6 sm:flex-row">
            <p class="text-xs text-stone-400">&copy; {{ date('Y') }} Medixa</p>
            <p class="text-xs text-stone-400">Outpatient care, kept simple</p>
        </div>
    </div>
</footer>
