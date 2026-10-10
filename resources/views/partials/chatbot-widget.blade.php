<!-- Medixa AI Chatbot Floating Widget (Identity-Blind by Design) -->
<div id="medixa-chat-widget" class="fixed bottom-6 right-6 z-50 font-sans" x-data="{ open: false }">

    <!-- Toggle Launcher Button -->
    <button
        id="medixa-chat-launcher"
        type="button"
        onclick="toggleMedixaChat()"
        class="group relative flex items-center gap-3 px-4 py-3 bg-white text-stone-700 rounded-full shadow-lg shadow-stone-300/60 border border-stone-200 hover:border-[#b7ddd4] hover:text-[#3f7f72] transition"
        aria-label="Open Medixa Assistant"
    >
        <div class="relative flex items-center justify-center w-8 h-8 rounded-full bg-[#eef7f4]">
            <svg class="w-5 h-5 text-[#3f7f72]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 rounded-full animate-ping"></span>
            <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 rounded-full"></span>
        </div>
        <div class="hidden text-left pr-1 lg:block">
            <div class="text-xs font-bold tracking-tight">Medixa Assistant</div>
            <div class="text-[10px] text-stone-400 flex items-center gap-1 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-300"></span> Identity-Blind AI
            </div>
        </div>
    </button>

    <!-- Chat Box Window -->
    <div
        id="medixa-chat-box"
        class="hidden fixed bottom-6 right-6 w-[92vw] sm:w-[420px] h-[580px] max-h-[85vh] bg-[#161b22] border border-[#30363d] rounded-2xl shadow-2xl shadow-black/80 flex flex-col overflow-hidden backdrop-blur-xl transition-all duration-300 z-50"
    >
        <!-- Header -->
        <div class="px-5 py-4 bg-gradient-to-r from-[#0d1117] via-[#161b22] to-[#0d1117] border-b border-[#30363d] flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#22a08c] to-[#188071] flex items-center justify-center text-white shadow-md shadow-[#22a08c]/20">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="text-sm font-bold text-white tracking-tight">Medixa Assistant</h4>
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-[#22a08c]/20 text-[#3ebba6] border border-[#22a08c]/30">
                            GPT-4o Mini
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 text-[11px] text-[#9198a1] mt-0.5">
                        <svg class="w-3 h-3 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"/>
                        </svg>
                        <span>Identity-blind by design</span>
                    </div>
                </div>
            </div>

            <button
                type="button"
                onclick="toggleMedixaChat()"
                class="w-8 h-8 rounded-lg text-[#9198a1] hover:text-white hover:bg-[#21262d] flex items-center justify-center transition"
                aria-label="Close Chat"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Privacy Assurance Banner -->
        <div class="px-4 py-2 bg-[#0d1117]/80 border-b border-[#21262d] text-[11px] text-[#9198a1] flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
            <span>Zero personal data transmitted. Queries use anonymous hospital metadata only.</span>
        </div>

        <!-- Chat Message Stream -->
        <div id="medixa-chat-messages" class="flex-1 p-4 overflow-y-auto space-y-4 text-xs sm:text-sm">
            <!-- Assistant Intro Message -->
            <div class="flex items-start gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-[#22a08c]/20 border border-[#22a08c]/40 text-[#3ebba6] flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
                    M
                </div>
                <div class="bg-[#21262d] border border-[#30363d] rounded-2xl rounded-tl-sm px-4 py-3 text-[#d1d7de] max-w-[85%] leading-relaxed shadow-sm">
                    Hello! I'm your Medixa Assistant. You can ask me about available doctors, clinical departments, or open OPD consultation slots.
                </div>
            </div>

            <!-- Quick Suggestion Pills -->
            <div id="medixa-quick-prompts" class="flex flex-wrap gap-1.5 pt-1 pl-9">
                <button type="button" onclick="sendQuickPrompt('Which doctors are available?')" class="text-[11px] px-2.5 py-1 rounded-full bg-[#161b22] border border-[#30363d] text-[#3ebba6] hover:bg-[#22a08c]/10 hover:border-[#22a08c]/40 transition">
                    Available doctors
                </button>
                <button type="button" onclick="sendQuickPrompt('What departments do you have?')" class="text-[11px] px-2.5 py-1 rounded-full bg-[#161b22] border border-[#30363d] text-[#3ebba6] hover:bg-[#22a08c]/10 hover:border-[#22a08c]/40 transition">
                    Departments
                </button>
                <button type="button" onclick="sendQuickPrompt('How do I book an appointment?')" class="text-[11px] px-2.5 py-1 rounded-full bg-[#161b22] border border-[#30363d] text-[#3ebba6] hover:bg-[#22a08c]/10 hover:border-[#22a08c]/40 transition">
                    Booking help
                </button>
            </div>
        </div>

        <!-- Typing Indicator (hidden by default) -->
        <div id="medixa-typing-indicator" class="hidden px-5 py-2 text-xs text-[#9198a1] flex items-center gap-2 bg-[#161b22]">
            <div class="flex gap-1">
                <span class="w-1.5 h-1.5 bg-[#3ebba6] rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                <span class="w-1.5 h-1.5 bg-[#3ebba6] rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                <span class="w-1.5 h-1.5 bg-[#3ebba6] rounded-full animate-bounce" style="animation-delay: 300ms"></span>
            </div>
            <span>Medixa AI is generating response...</span>
        </div>

        <!-- Input Bar -->
        <div class="p-3 bg-[#0d1117] border-t border-[#30363d]">
            <form id="medixa-chat-form" onsubmit="handleMedixaChatSubmit(event)" class="flex items-center gap-2">
                @csrf
                <input
                    type="text"
                    id="medixa-chat-input"
                    placeholder="Type your question..."
                    autocomplete="off"
                    class="flex-1 bg-[#161b22] border border-[#30363d] rounded-xl px-4 py-2.5 text-xs sm:text-sm text-white placeholder-[#656d76] focus:outline-none focus:border-[#22a08c] focus:ring-1 focus:ring-[#22a08c] transition"
                />
                <button
                    type="submit"
                    id="medixa-chat-send-btn"
                    class="w-10 h-10 rounded-xl bg-[#22a08c] hover:bg-[#188071] active:scale-95 text-white flex items-center justify-center transition shadow-md shadow-[#22a08c]/25 shrink-0"
                    aria-label="Send Message"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const CHAT_SESSION_KEY = 'medixa_anon_session_token';

    function getOrCreateSessionToken() {
        let token = sessionStorage.getItem(CHAT_SESSION_KEY);
        if (!token) {
            token = 'sess_' + Math.random().toString(36).substring(2, 15) + '_' + Date.now();
            sessionStorage.setItem(CHAT_SESSION_KEY, token);
        }
        return token;
    }

    function toggleMedixaChat() {
        const box = document.getElementById('medixa-chat-box');
        const launcher = document.getElementById('medixa-chat-launcher');
        if (!box) return;

        const isHidden = box.classList.contains('hidden');
        if (isHidden) {
            box.classList.remove('hidden');
            if (launcher) launcher.classList.add('hidden');
            setTimeout(() => {
                const input = document.getElementById('medixa-chat-input');
                if (input) input.focus();
            }, 100);
        } else {
            box.classList.add('hidden');
            if (launcher) launcher.classList.remove('hidden');
        }
    }

    function appendChatMessage(role, text, bookingData = null) {
        const stream = document.getElementById('medixa-chat-messages');
        if (!stream) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'flex items-start gap-2.5 ' + (role === 'user' ? 'justify-end' : '');

        if (role === 'user') {
            wrapper.innerHTML = `
                <div class="bg-gradient-to-r from-[#22a08c] to-[#188071] text-white rounded-2xl rounded-tr-sm px-4 py-2.5 max-w-[85%] leading-relaxed shadow-sm">
                    ${escapeHtml(text)}
                </div>
            `;
        } else {
            let bookingHtml = '';
            if (bookingData && bookingData.success) {
                bookingHtml = `
                    <div class="mt-3 p-3 bg-emerald-950/60 border border-emerald-500/30 rounded-xl text-emerald-200 text-xs">
                        <div class="font-bold flex items-center gap-1.5 text-emerald-300">
                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Appointment Confirmed
                        </div>
                        <div class="mt-1">${escapeHtml(bookingData.message)}</div>
                        <a href="{{ route('login') }}" class="inline-block mt-2 font-semibold text-[#3ebba6] hover:underline">View in Patient Dashboard &rarr;</a>
                    </div>
                `;
            } else if (bookingData && bookingData.message) {
                bookingHtml = `
                    <div class="mt-3 p-3 bg-amber-950/60 border border-amber-500/30 rounded-xl text-amber-200 text-xs">
                        <div class="font-bold text-amber-300">Notice</div>
                        <div class="mt-1">${escapeHtml(bookingData.message)}</div>
                    </div>
                `;
            }

            wrapper.innerHTML = `
                <div class="w-7 h-7 rounded-lg bg-[#22a08c]/20 border border-[#22a08c]/40 text-[#3ebba6] flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
                    M
                </div>
                <div class="bg-[#21262d] border border-[#30363d] rounded-2xl rounded-tl-sm px-4 py-3 text-[#d1d7de] max-w-[85%] leading-relaxed shadow-sm">
                    ${formatAiResponse(text)}
                    ${bookingHtml}
                </div>
            `;
        }

        stream.appendChild(wrapper);
        stream.scrollTop = stream.scrollHeight;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatAiResponse(text) {
        return escapeHtml(text)
            .replace(/\n\n/g, '<br><br>')
            .replace(/\n/g, '<br>')
            .replace(/•\s/g, '&bull; ');
    }

    function sendQuickPrompt(promptText) {
        const input = document.getElementById('medixa-chat-input');
        if (input) {
            input.value = promptText;
            handleMedixaChatSubmit(new Event('submit'));
        }
    }

    async function handleMedixaChatSubmit(e) {
        if (e && e.preventDefault) e.preventDefault();

        const input = document.getElementById('medixa-chat-input');
        const sendBtn = document.getElementById('medixa-chat-send-btn');
        const typing = document.getElementById('medixa-typing-indicator');
        const quickPrompts = document.getElementById('medixa-quick-prompts');

        if (!input) return;
        const message = input.value.trim();
        if (!message) return;

        // Hide quick prompts once user begins typing
        if (quickPrompts) quickPrompts.classList.add('hidden');

        // Append user speech bubble
        appendChatMessage('user', message);
        input.value = '';
        input.disabled = true;
        if (sendBtn) sendBtn.disabled = true;
        if (typing) typing.classList.remove('hidden');

        const sessionToken = getOrCreateSessionToken();

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                              '{{ csrf_token() }}';

            const response = await fetch('{{ route('chatbot.message') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message: message,
                    session_token: sessionToken
                })
            });

            const data = await response.json();

            if (data.session_token) {
                sessionStorage.setItem(CHAT_SESSION_KEY, data.session_token);
            }

            appendChatMessage('assistant', data.reply, data.booking);
        } catch (err) {
            console.error('Chatbot error:', err);
            appendChatMessage('assistant', 'I apologize, an error occurred while connecting to the assistant. Please try again.');
        } finally {
            if (typing) typing.classList.add('hidden');
            input.disabled = false;
            if (sendBtn) sendBtn.disabled = false;
            input.focus();
        }
    }
</script>
