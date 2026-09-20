<footer class="mt-auto border-t border-[#21262d] bg-[#0d1117]">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">

            {{-- Brand --}}
            <div>
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-[#22a08c] to-[#188071] flex items-center justify-center">
                        <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <span class="font-display text-xl font-bold text-white">Medixa</span>
                </div>
                <p class="text-sm text-[#656d76] leading-relaxed">
                    A smart hospital OPD system designed to simplify patient care, doctor scheduling, and secure medical data exchange.
                </p>
            </div>

            {{-- Quick links --}}
            <div>
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Quick Links</h3>
                <ul class="space-y-2">
                    <li><a href="{{ route('home') }}" class="text-sm text-[#656d76] hover:text-[#22a08c] transition-colors duration-200">Home</a></li>
                    <li><a href="{{ route('doctors.index') }}" class="text-sm text-[#656d76] hover:text-[#22a08c] transition-colors duration-200">Find a Doctor</a></li>
                </ul>
            </div>

            {{-- Contact --}}
            <div>
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Emergency</h3>
                <p class="text-sm text-[#656d76]">24/7 Emergency Hotline</p>
                <p class="text-lg font-bold text-[#22a08c] mt-1">+880 1800-MEDIXA</p>
            </div>
        </div>

        <div class="mt-10 border-t border-[#21262d] pt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
            <p class="text-xs text-[#656d76]">&copy; {{ date('Y') }} Medixa. All rights reserved.</p>
            <p class="text-xs text-[#656d76]">Built with ❤️ on Laravel &amp; Tailwind CSS</p>
        </div>
    </div>
</footer>
