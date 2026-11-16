import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { setImmediate } from 'node:timers/promises';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const template = readFileSync(new URL('../../resources/views/patient/partials/live-serial.blade.php', import.meta.url), 'utf8');
const script = template.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace('{{ $activeAppointment->doctor_id }}', '7')
    .replace('{{ (int) $activeAppointment->serial_number }}', '8')
    .replace('@json($doctorQueue?->updated_at?->toIso8601String())', '"2026-11-16T10:00:00Z"')
    .replace("@json(route('patient.queue.status'))", '"/patient/queue/status"');

function queuePage() {
    const elements = new Map([
        ['queue-doctor-status', { textContent: 'Doctor Present' }],
        ['queue-current-serial', { textContent: '#3' }],
        ['queue-presence-badge', { innerHTML: 'In Chamber' }],
        ['queue-patients-ahead', { textContent: '5' }],
    ]);
    const requests = [];
    let ready;
    let poll;
    let listener;

    runInNewContext(script, {
        document: {
            addEventListener(name, callback) {
                assert.equal(name, 'DOMContentLoaded');
                ready = callback;
            },
            getElementById: (id) => elements.get(id) ?? null,
        },
        window: {
            Echo: {
                channel(name) {
                    assert.equal(name, 'queue.doctor.7');
                    return {
                        listen(event, callback) {
                            assert.equal(event, '.queue.updated');
                            listener = callback;
                        },
                    };
                },
            },
        },
        setInterval(callback, milliseconds) {
            assert.equal(milliseconds, 8000);
            poll = callback;
        },
        fetch(url, options) {
            return new Promise((resolve, reject) => requests.push({ url, options, resolve, reject }));
        },
    });
    ready();

    return {
        elements,
        requests,
        poll: () => poll(),
        event: (updated_at, doctor_present = false) => listener({ updated_at, doctor_present, current_serial: 4 }),
        async respond(index, data = {}) {
            requests[index].resolve({
                ok: true,
                json: async () => ({ active: true, current_serial: 4, doctor_present: false, ...data }),
            });
            await setImmediate();
        },
    };
}

test('an older event cannot restore present after a newer away event', async () => {
    const page = queuePage();

    page.event('2026-11-16T10:00:02Z');
    await page.respond(0);
    page.event('2026-11-16T10:00:01Z', true);

    assert.equal(page.requests.length, 1);
    assert.equal(page.elements.get('queue-doctor-status').textContent, 'Doctor Away');
    assert.match(page.elements.get('queue-presence-badge').innerHTML, /Doctor Away/);
});

test('events older than the rendered queue are ignored', () => {
    const page = queuePage();

    page.event('2026-11-16T09:59:59Z');

    assert.equal(page.requests.length, 0);
    assert.equal(page.elements.get('queue-doctor-status').textContent, 'Doctor Present');
});

test('a poll overtaken by an event is discarded and refreshed once', async () => {
    const page = queuePage();
    page.poll();
    page.event('2026-11-16T10:00:01Z');
    page.event('2026-11-16T10:00:02Z');

    await page.respond(0, { current_serial: 99, doctor_present: true });

    assert.equal(page.elements.get('queue-current-serial').textContent, '#3');
    assert.equal(page.requests.length, 2);
    await page.respond(1);
    assert.equal(page.elements.get('queue-current-serial').textContent, '#4');
    assert.equal(page.elements.get('queue-doctor-status').textContent, 'Doctor Away');
});

test('an event arriving while the response body is loading invalidates that poll', async () => {
    const page = queuePage();
    page.poll();
    let body;
    page.requests[0].resolve({ ok: true, json: () => new Promise((resolve) => { body = resolve; }) });
    await setImmediate();

    page.event('2026-11-16T10:00:01Z');
    body({ active: true, current_serial: 99, doctor_present: true });
    await setImmediate();

    assert.equal(page.elements.get('queue-current-serial').textContent, '#3');
    assert.equal(page.requests.length, 2);
    await page.respond(1);
    assert.equal(page.elements.get('queue-doctor-status').textContent, 'Doctor Away');
});

test('intervals do not create overlapping polls', async () => {
    const page = queuePage();
    page.poll();
    page.poll();
    page.poll();

    assert.equal(page.requests.length, 1);
    await page.respond(0);
    page.poll();
    assert.equal(page.requests.length, 2);
    await page.respond(1);
});

test('conflicting events in the same second use current server state', async () => {
    const page = queuePage();
    page.event('2026-11-16T10:00:01Z', false);
    await page.respond(0);

    page.event('2026-11-16T10:00:01Z', true);

    assert.equal(page.elements.get('queue-doctor-status').textContent, 'Doctor Away');
    assert.equal(page.requests.length, 2);
    await page.respond(1);
    assert.equal(page.elements.get('queue-doctor-status').textContent, 'Doctor Away');
});

test('a delayed event cannot overwrite a newer state already obtained by polling', async () => {
    const page = queuePage();
    page.poll();
    await page.respond(0);

    page.event('2026-11-16T10:00:01Z', true);

    assert.equal(page.elements.get('queue-doctor-status').textContent, 'Doctor Away');
    await page.respond(1);
    assert.equal(page.elements.get('queue-doctor-status').textContent, 'Doctor Away');
});

test('polling still updates queue details without broadcasts and bypasses cached responses', async () => {
    const page = queuePage();
    page.poll();

    await page.respond(0, { patients_ahead: 2 });

    assert.equal(page.requests[0].url, '/patient/queue/status');
    assert.equal(page.requests[0].options.cache, 'no-store');
    assert.equal(page.elements.get('queue-doctor-status').textContent, 'Doctor Away');
    assert.equal(page.elements.get('queue-patients-ahead').textContent, 2);
});

test('a failed request releases the polling guard for the next attempt', async () => {
    const page = queuePage();
    page.poll();
    page.requests[0].reject(new Error('offline'));
    await setImmediate();

    page.poll();
    assert.equal(page.requests.length, 2);
    await page.respond(1);
    assert.equal(page.elements.get('queue-doctor-status').textContent, 'Doctor Away');
});

test('inactive responses and malformed event timestamps leave the display unchanged', async () => {
    const page = queuePage();
    page.event('invalid');
    assert.equal(page.requests.length, 0);
    page.poll();

    await page.respond(0, { active: false });

    assert.equal(page.elements.get('queue-doctor-status').textContent, 'Doctor Present');
});
