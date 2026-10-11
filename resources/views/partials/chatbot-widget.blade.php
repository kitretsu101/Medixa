@php
    $chatAppointmentsUrl = auth()->user()?->isPatient()
        ? route('patient.appointments.index')
        : route('login');
@endphp

<div
    id="medixa-chat-widget"
    class="fixed bottom-6 right-6 z-50 font-sans"
    data-appointments-url="{{ $chatAppointmentsUrl }}"
>
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
        </div>
        <div class="hidden text-left pr-1 sm:block">
            <div class="text-xs font-bold tracking-tight">Medixa Assistant</div>
            <div id="medixa-chat-launcher-note" class="text-[10px] text-stone-400 font-medium">General information</div>
        </div>
    </button>

    <div
        id="medixa-chat-box"
        class="hidden fixed bottom-6 right-6 w-[92vw] sm:w-[420px] h-[580px] max-h-[85vh] bg-white border border-stone-200 rounded-2xl shadow-2xl shadow-stone-300/70 flex flex-col overflow-hidden z-50"
        role="dialog"
        aria-labelledby="medixa-chat-title"
    >
        <div class="px-4 py-3 bg-[#f7f5f2] border-b border-stone-200 flex items-center justify-between gap-3">
            <div>
                <h4 id="medixa-chat-title" class="text-sm font-bold text-stone-800">Medixa Assistant</h4>
                <p id="medixa-chat-subtitle" class="text-[11px] text-stone-500">English or Bangla</p>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" onclick="resetMedixaChat()" class="px-2 py-1 rounded-lg text-[11px] font-semibold border border-stone-200 text-stone-600" id="medixa-chat-clear">Clear chat</button>
                <button type="button" data-chat-locale="en" onclick="setChatLocale('en')" class="px-2 py-1 rounded-lg text-[11px] font-semibold border border-stone-200 text-stone-600">EN</button>
                <button type="button" data-chat-locale="bn" onclick="setChatLocale('bn')" class="px-2 py-1 rounded-lg text-[11px] font-semibold border border-stone-200 text-stone-600">বাংলা</button>
                <button type="button" onclick="toggleMedixaChat()" class="w-8 h-8 rounded-lg text-stone-500 hover:bg-white flex items-center justify-center" aria-label="Close chat">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <p id="medixa-chat-disclaimer" class="px-4 py-2 text-[11px] leading-relaxed text-[#3f7f72] bg-[#eef7f4] border-b border-[#d7ebe4]">This assistant shares general information and does not replace a healthcare professional.</p>
        <p id="medixa-chat-privacy" class="px-4 py-2 text-[11px] leading-relaxed text-stone-500 bg-white border-b border-stone-100">Messages can be processed by the configured external AI provider. Medixa does not store the message text. Do not send your name, phone number, or medical record.</p>

        <div id="medixa-chat-messages" class="flex-1 p-4 overflow-y-auto space-y-4 text-sm bg-white">
            <div class="flex items-start gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-[#eef7f4] text-[#3f7f72] flex items-center justify-center text-xs font-bold shrink-0">M</div>
                <div id="medixa-chat-intro" class="bg-[#f7f5f2] border border-stone-200 rounded-2xl rounded-tl-sm px-4 py-3 text-stone-700 max-w-[85%] leading-relaxed">Hello. I can help with doctors, departments, and appointments.</div>
            </div>
            <div id="medixa-quick-prompts" class="flex flex-wrap gap-1.5 pl-9"></div>
        </div>

        <div id="medixa-typing-indicator" class="hidden px-5 py-2 text-xs text-stone-500 flex items-center gap-2 bg-[#f7f5f2] border-t border-stone-100">
            <span class="inline-flex gap-1" aria-hidden="true">
                <span class="w-1.5 h-1.5 bg-[#5eaea0] rounded-full animate-bounce"></span>
                <span class="w-1.5 h-1.5 bg-[#5eaea0] rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                <span class="w-1.5 h-1.5 bg-[#5eaea0] rounded-full animate-bounce" style="animation-delay: 300ms"></span>
            </span>
            <span id="medixa-typing-label">Preparing a reply...</span>
        </div>

        <div class="p-3 bg-[#f7f5f2] border-t border-stone-200">
            <p id="medixa-chat-status" class="hidden px-1 pb-2 text-[11px] text-rose-700" role="status"></p>
            <form id="medixa-chat-form" onsubmit="handleMedixaChatSubmit(event)" class="flex items-center gap-2">
                @csrf
                <input
                    type="text"
                    id="medixa-chat-input"
                    maxlength="2000"
                    autocomplete="off"
                    class="flex-1 bg-white border border-stone-200 rounded-xl px-4 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-[#5eaea0] focus:ring-1 focus:ring-[#5eaea0]"
                />
                <button
                    type="submit"
                    id="medixa-chat-send-btn"
                    class="w-10 h-10 rounded-xl bg-[#3f7f72] hover:bg-[#356e63] text-white flex items-center justify-center shrink-0"
                    aria-label="Send message"
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
    const CHAT_LOCALE_KEY = 'medixa_chat_locale';
    const CHAT_COPY = {
        en: {
            disclaimer: 'This assistant shares general information and does not replace a healthcare professional.',
            privacy: 'Messages can be processed by the configured external AI provider. Medixa does not store the message text. Do not send your name, phone number, or medical record.',
            clear: 'Clear chat',
            intro: 'Hello. I can help with doctors, departments, and appointments.',
            subtitle: 'English or Bangla',
            launcher: 'General information',
            placeholder: 'Type your question...',
            typing: 'Preparing a reply...',
            empty: 'Type a message to begin.',
            network: 'The assistant could not be reached. Please try again.',
            limited: 'Please wait a moment before sending another message.',
            invalid: 'Enter a message of 2,000 characters or fewer.',
            retry: 'Try again',
            sources: 'Sources',
            offers: 'Routine visits',
            offerNote: 'Nothing is booked until you confirm. This is not emergency care.',
            confirmed: 'Appointment confirmed',
            notice: 'Notice',
            appointments: 'View appointments',
            prompts: [
                ['Which doctors are available?', 'Available doctors'],
                ['What departments do you have?', 'Departments'],
                ['How do I book an appointment?', 'Booking help'],
            ],
        },
        bn: {
            disclaimer: 'এই সহকারী সাধারণ তথ্য দেয় এবং কোনো স্বাস্থ্যকর্মী বা চিকিৎসকের বিকল্প নয়।',
            privacy: 'বার্তা কনফিগার করা বাহ্যিক এআই সেবায় যেতে পারে। মেডিক্সা বার্তার লেখা সংরক্ষণ করে না। নাম, ফোন নম্বর বা চিকিৎসা নথি পাঠাবেন না।',
            clear: 'চ্যাট মুছুন',
            intro: 'হ্যালো। আমি চিকিৎসক, বিভাগ ও অ্যাপয়েন্টমেন্ট খুঁজে দিতে পারি।',
            subtitle: 'ইংরেজি বা বাংলা',
            launcher: 'সাধারণ তথ্য',
            placeholder: 'আপনার প্রশ্ন লিখুন...',
            typing: 'উত্তর তৈরি হচ্ছে...',
            empty: 'শুরু করতে একটি বার্তা লিখুন।',
            network: 'সহকারীর সাথে সংযোগ হয়নি। আবার চেষ্টা করুন।',
            limited: 'আরেকটি বার্তা পাঠানোর আগে একটু অপেক্ষা করুন।',
            invalid: 'বার্তা ২,০০০ অক্ষরের মধ্যে রাখুন।',
            retry: 'আবার চেষ্টা',
            sources: 'সূত্র',
            offers: 'নিয়মিত ভিজিট',
            offerNote: 'নিশ্চিত না করা পর্যন্ত কিছু বুক হয় না। এটি জরুরি সেবা নয়।',
            confirmed: 'অ্যাপয়েন্টমেন্ট নিশ্চিত',
            notice: 'নোটিশ',
            appointments: 'অ্যাপয়েন্টমেন্ট দেখুন',
            prompts: [
                ['কোন চিকিৎসক আছেন?', 'চিকিৎসক'],
                ['কোন বিভাগ আছে?', 'বিভাগ'],
                ['অ্যাপয়েন্টমেন্ট কীভাবে বুক করব?', 'বুকিং'],
            ],
        },
    };

    let pendingChatMessage = null;
    let chatStatusKey = null;

    function chatLocale() {
        return sessionStorage.getItem(CHAT_LOCALE_KEY) === 'bn' ? 'bn' : 'en';
    }

    function chatCopy() {
        return CHAT_COPY[chatLocale()];
    }

    function setChatLocale(locale) {
        sessionStorage.setItem(CHAT_LOCALE_KEY, locale === 'bn' ? 'bn' : 'en');
        applyChatLocale();
    }

    function applyChatLocale() {
        const copy = chatCopy();
        const disclaimer = document.getElementById('medixa-chat-disclaimer');
        const intro = document.getElementById('medixa-chat-intro');
        const subtitle = document.getElementById('medixa-chat-subtitle');
        const launcher = document.getElementById('medixa-chat-launcher-note');
        const input = document.getElementById('medixa-chat-input');
        const typing = document.getElementById('medixa-typing-label');
        const prompts = document.getElementById('medixa-quick-prompts');

        if (disclaimer) disclaimer.textContent = copy.disclaimer;
        const privacy = document.getElementById('medixa-chat-privacy');
        const clearButton = document.getElementById('medixa-chat-clear');
        if (privacy) privacy.textContent = copy.privacy;
        if (clearButton) clearButton.textContent = copy.clear;
        if (intro) intro.textContent = copy.intro;
        if (subtitle) subtitle.textContent = copy.subtitle;
        if (launcher) launcher.textContent = copy.launcher;
        if (input) input.placeholder = copy.placeholder;
        if (typing) typing.textContent = copy.typing;

        document.querySelectorAll('[data-chat-locale]').forEach((button) => {
            const selected = button.getAttribute('data-chat-locale') === chatLocale();
            button.classList.toggle('bg-[#3f7f72]', selected);
            button.classList.toggle('text-white', selected);
            button.classList.toggle('border-[#3f7f72]', selected);
        });

        if (chatStatusKey) {
            setChatStatus(chatStatusKey);
        }

        if (prompts && !prompts.classList.contains('hidden')) {
            prompts.replaceChildren();
            copy.prompts.forEach(([prompt, label]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'text-[11px] px-2.5 py-1 rounded-full bg-white border border-stone-200 text-[#3f7f72] hover:border-[#b7ddd4]';
                button.textContent = label;
                button.addEventListener('click', () => sendQuickPrompt(prompt));
                prompts.appendChild(button);
            });
        }
    }

    function storedSessionToken() {
        return sessionStorage.getItem(CHAT_SESSION_KEY) || '';
    }

    async function resetMedixaChat() {
        const token = storedSessionToken();
        sessionStorage.removeItem(CHAT_SESSION_KEY);
        const stream = document.getElementById('medixa-chat-messages');
        if (stream) {
            stream.querySelectorAll(':scope > div').forEach((node) => {
                if (node.id !== 'medixa-quick-prompts' && !node.querySelector('#medixa-chat-intro')) {
                    node.remove();
                }
            });
        }
        const prompts = document.getElementById('medixa-quick-prompts');
        if (prompts) prompts.classList.remove('hidden');
        pendingChatMessage = null;
        setChatStatus('');
        if (!token) return;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        await fetch('{{ url('/chatbot/sessions') }}/' + encodeURIComponent(token), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });
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

    function setChatStatus(key) {
        chatStatusKey = key;
        const status = document.getElementById('medixa-chat-status');
        if (!status) return;
        if (!key) {
            status.textContent = '';
            status.classList.add('hidden');
            return;
        }
        status.textContent = chatCopy()[key] || '';
        status.classList.remove('hidden');
    }

    function sourceLinksHtml(sources) {
        if (!Array.isArray(sources) || sources.length === 0) return '';
        const items = sources
            .filter((source) => source && typeof source.source_url === 'string' && source.source_url.startsWith('https://'))
            .map((source) => `<li><a href="${escapeHtml(source.source_url)}" class="underline" target="_blank" rel="noopener noreferrer">${escapeHtml(source.source_name || source.title || 'Source')}</a></li>`)
            .join('');
        if (!items) return '';
        return `<div class="mt-3 text-xs text-stone-500"><div class="font-semibold">${escapeHtml(chatCopy().sources)}</div><ul class="mt-1 space-y-1 text-[#3f7f72]">${items}</ul></div>`;
    }

    function offerButtonsHtml(offers) {
        if (!Array.isArray(offers) || offers.length === 0) return '';
        const copy = chatCopy();
        const items = offers.map((offer) => {
            const doctorId = Number(offer && offer.doctor_id);
            const date = offer && typeof offer.date === 'string' ? offer.date : '';
            if (!doctorId || !/^\d{4}-\d{2}-\d{2}$/.test(date)) return '';
            const label = `${offer.doctor_name || 'Doctor'} — ${date}`;
            return `<button type="button" class="medixa-offer mt-2 mr-2 rounded-full border border-[#b7ddd4] bg-white px-3 py-1 text-xs font-semibold text-[#3f7f72]" data-offer-doctor="${doctorId}" data-offer-date="${date}">${escapeHtml(label)}</button>`;
        }).join('');
        if (!items) return '';
        return `<div class="mt-3 text-xs text-stone-600"><div class="font-semibold">${escapeHtml(copy.offers)}</div><p class="mt-1">${escapeHtml(copy.offerNote)}</p><div class="mt-2">${items}</div></div>`;
    }

    function appendChatMessage(role, text, bookingData = null, retryable = false, sources = null, offers = null) {
        const stream = document.getElementById('medixa-chat-messages');
        if (!stream) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'flex items-start gap-2.5 ' + (role === 'user' ? 'justify-end' : '');

        if (role === 'user') {
            wrapper.innerHTML = `
                <div class="bg-[#3f7f72] text-white rounded-2xl rounded-tr-sm px-4 py-2.5 max-w-[85%] leading-relaxed">${escapeHtml(text)}</div>
            `;
        } else {
            const copy = chatCopy();
            let bookingHtml = '';
            if (bookingData && bookingData.success) {
                const url = document.getElementById('medixa-chat-widget')?.dataset.appointmentsUrl || '';
                bookingHtml = `
                    <div class="mt-3 p-3 bg-[#eef7f4] border border-[#d7ebe4] rounded-xl text-[#3f7f72] text-xs">
                        <div class="font-bold">${escapeHtml(copy.confirmed)}</div>
                        <div class="mt-1">${escapeHtml(bookingData.message || '')}</div>
                        <a href="${escapeHtml(url)}" class="inline-block mt-2 font-semibold underline">${escapeHtml(copy.appointments)}</a>
                    </div>
                `;
            } else if (bookingData && bookingData.message) {
                bookingHtml = `
                    <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-xs">
                        <div class="font-bold">${escapeHtml(copy.notice)}</div>
                        <div class="mt-1">${escapeHtml(bookingData.message)}</div>
                    </div>
                `;
            }

            const retryHtml = retryable
                ? `<button type="button" class="medixa-chat-retry mt-3 text-xs font-semibold text-[#3f7f72] underline">${escapeHtml(copy.retry)}</button>`
                : '';

            wrapper.innerHTML = `
                <div class="w-7 h-7 rounded-lg bg-[#eef7f4] text-[#3f7f72] flex items-center justify-center text-xs font-bold shrink-0">M</div>
                <div class="bg-[#f7f5f2] border border-stone-200 rounded-2xl rounded-tl-sm px-4 py-3 text-stone-700 max-w-[85%] leading-relaxed">
                    ${formatAiResponse(text)}
                    ${sourceLinksHtml(sources)}
                    ${offerButtonsHtml(offers)}
                    ${bookingHtml}
                    ${retryHtml}
                </div>
            `;
        }

        stream.appendChild(wrapper);
        wrapper.querySelectorAll('.medixa-offer').forEach((button) => {
            button.addEventListener('click', () => {
                postChatMessage('Confirm this routine appointment', true, true, {
                    doctor_id: Number(button.getAttribute('data-offer-doctor')),
                    appointment_date: button.getAttribute('data-offer-date'),
                    confirmed: true,
                });
            });
        });
        const retry = wrapper.querySelector('.medixa-chat-retry');
        if (retry) {
            retry.addEventListener('click', () => {
                if (pendingChatMessage) {
                    postChatMessage(pendingChatMessage, false);
                }
            });
        }
        stream.scrollTop = stream.scrollHeight;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
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

    function handleMedixaChatSubmit(e) {
        if (e && e.preventDefault) e.preventDefault();
        const input = document.getElementById('medixa-chat-input');
        if (!input) return;
        const message = input.value.trim();
        if (!message) {
            setChatStatus('empty');
            return;
        }
        input.value = '';
        postChatMessage(message, true);
    }

    async function postChatMessage(message, showUserBubble, allowSessionRetry = true, confirm = null) {
        const input = document.getElementById('medixa-chat-input');
        const sendBtn = document.getElementById('medixa-chat-send-btn');
        const typing = document.getElementById('medixa-typing-indicator');
        const quickPrompts = document.getElementById('medixa-quick-prompts');
        const copy = chatCopy();

        pendingChatMessage = message;
        setChatStatus('');
        if (quickPrompts) quickPrompts.classList.add('hidden');
        if (showUserBubble) appendChatMessage('user', message);
        if (input) input.disabled = true;
        if (sendBtn) sendBtn.disabled = true;
        if (typing) typing.classList.remove('hidden');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            const response = await fetch('{{ route('chatbot.message') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message: message,
                    session_token: storedSessionToken() || null,
                    locale: chatLocale(),
                    confirm: confirm
                })
            });

            let data = {};
            try {
                data = await response.json();
            } catch (parseError) {
                data = {};
            }

            if (response.status === 404 && allowSessionRetry && storedSessionToken()) {
                sessionStorage.removeItem(CHAT_SESSION_KEY);
                return postChatMessage(message, false, false, confirm);
            }

            if (data.session_token) {
                sessionStorage.setItem(CHAT_SESSION_KEY, data.session_token);
            }

            if (response.status === 429) {
                appendChatMessage('assistant', copy.limited, null, true);
                return;
            }

            if (response.status === 422) {
                setChatStatus('invalid');
                return;
            }

            if (!response.ok || data.error) {
                appendChatMessage('assistant', data.reply || copy.network, null, data.retryable !== false);
                return;
            }

            pendingChatMessage = null;
            appendChatMessage('assistant', data.reply || copy.network, data.booking, false, data.sources, data.offers);
        } catch (err) {
            appendChatMessage('assistant', copy.network, null, true);
        } finally {
            if (typing) typing.classList.add('hidden');
            if (input) input.disabled = false;
            if (sendBtn) sendBtn.disabled = false;
            if (input) input.focus();
        }
    }

    applyChatLocale();
</script>
