import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const template = readFileSync(new URL('../../resources/views/partials/chatbot-widget.blade.php', import.meta.url), 'utf8');
const script = template.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replaceAll("{{ csrf_token() }}", 'csrf')
    .replaceAll("{{ route('chatbot.message') }}", '/chatbot/message')
    .replaceAll("{{ url('/chatbot/sessions') }}", '/chatbot/sessions');

function widget(fetch, { blockedStorage = false } = {}) {
    const elements = new Map();
    function element() {
        const classes = new Set();
        return {
            textContent: '', disabled: false, value: '', children: [],
            classList: { add: name => classes.add(name), remove: name => classes.delete(name), contains: name => classes.has(name) },
            appendChild(child) { this.children.push(child); },
            querySelectorAll: () => [], querySelector: () => null, focus() {},
            get innerHTML() { return this.markup ?? this.textContent; },
            set innerHTML(value) { this.markup = value; },
        };
    }
    for (const id of ['medixa-chat-input', 'medixa-chat-send-btn', 'medixa-typing-indicator', 'medixa-chat-status', 'medixa-chat-messages']) {
        elements.set(id, element());
    }
    const storage = new Map();
    const timers = new Map();
    const context = {
        document: { getElementById: id => elements.get(id) ?? null, querySelectorAll: () => [], querySelector: () => null, createElement: element },
        sessionStorage: {
            getItem(key) { if (blockedStorage) throw new Error('Storage blocked'); return storage.get(key) ?? null; },
            setItem(key, value) { if (blockedStorage) throw new Error('Storage blocked'); storage.set(key, value); },
            removeItem(key) { if (blockedStorage) throw new Error('Storage blocked'); storage.delete(key); },
        },
        fetch, AbortController,
        setTimeout(callback) { const id = timers.size + 1; timers.set(id, callback); return id; },
        clearTimeout(id) { timers.delete(id); },
    };
    runInNewContext(script, context);
    return { context, elements, timers, storage };
}

const response = (status, body) => ({ status, ok: status >= 200 && status < 300, json: async () => body });

test('chat remains usable when browser storage is blocked', async () => {
    const calls = [];
    const page = widget(async (url, options) => {
        calls.push(JSON.parse(options.body));
        return response(200, { reply: 'Departments: Cardiology', session_token: 'owned-token' });
    }, { blockedStorage: true });
    await page.context.postChatMessage('departments', true);
    await page.context.postChatMessage('doctors', true);
    assert.equal(calls[1].session_token, 'owned-token');
    assert.match(page.elements.get('medixa-chat-messages').children[1].innerHTML, /Departments: Cardiology/);
    assert.equal(page.elements.get('medixa-chat-send-btn').disabled, false);
});

test('expired CSRF session shows refresh guidance and releases loading state without retrying', async () => {
    let requests = 0;
    const page = widget(async () => { requests++; return response(419, {}); });
    await page.context.postChatMessage('hello', true);
    assert.match(page.elements.get('medixa-chat-status').textContent, /Refresh the page/);
    assert.equal(requests, 1);
    assert.equal(page.elements.get('medixa-chat-input').disabled, false);
    assert.equal(page.elements.get('medixa-typing-indicator').classList.contains('hidden'), true);
});

test('clearing chat handles a network failure and discards the local session token', async () => {
    const page = widget(async () => { throw new Error('Offline'); });
    page.storage.set('medixa_anon_session_token', 'old-token');
    await page.context.resetMedixaChat();
    assert.equal(page.context.storedSessionToken(), '');
    assert.match(page.elements.get('medixa-chat-status').textContent, /could not be reached/);
});

test('stalled request times out and duplicate submissions do not send a second request', async () => {
    let requests = 0;
    const page = widget((url, options) => {
        requests++;
        return new Promise((resolve, reject) => options.signal.addEventListener('abort', () => reject(new Error('Aborted'))));
    });
    const pending = page.context.postChatMessage('hello', true);
    await page.context.postChatMessage('duplicate', true);
    assert.equal(requests, 1);
    [...page.timers.values()][0]();
    await pending;
    assert.equal(page.elements.get('medixa-chat-send-btn').disabled, false);
    assert.match(page.elements.get('medixa-chat-messages').children[1].innerHTML, /could not be reached/);
    assert.equal(page.timers.size, 0);
});

test('expired chat token retries once without duplicating the message or losing booking confirmation', async () => {
    const calls = [];
    const page = widget(async (url, options) => {
        calls.push(JSON.parse(options.body));
        return calls.length === 1 ? response(404, {}) : response(200, { reply: 'Ready', session_token: 'new-token' });
    });
    page.storage.set('medixa_anon_session_token', 'expired-token');
    const confirmation = { doctor_id: 7, appointment_date: '2026-12-01', confirmed: true };
    await page.context.postChatMessage('confirm', true, true, confirmation);
    assert.equal(calls.length, 2);
    assert.equal(calls[1].session_token, null);
    assert.deepEqual(calls[1].confirm, confirmation);
    assert.equal(page.elements.get('medixa-chat-messages').children.length, 2);
    assert.equal(page.elements.get('medixa-chat-send-btn').disabled, false);
});
