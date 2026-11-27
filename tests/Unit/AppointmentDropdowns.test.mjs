import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const template = readFileSync(new URL('../../resources/views/patient/book-appointment.blade.php', import.meta.url), 'utf8');
const script = template.match(/<script>([\s\S]*?)<\/script>/)[1];

function bookingPage(hospitalValue = '', doctorValue = '') {
    const hospital = { value: hospitalValue, events: {}, addEventListener(name, callback) { this.events[name] = callback; } };
    const doctor = {
        value: doctorValue, events: {}, options: [{ value: '' }, { value: '7', dataset: { hospitalId: '1' } }, { value: '8', dataset: { hospitalId: '2' } }],
        addEventListener(name, callback) { this.events[name] = callback; },
        replaceChildren(option) { this.options = [option]; }, appendChild(option) { this.options.push(option); },
    };
    const status = { textContent: '' };
    runInNewContext(script, {
        document: { getElementById: id => ({ hospital_id: hospital, doctor_id: doctor, 'doctor-availability': status })[id] },
        Option: function (text, value) { this.text = text; this.value = value; },
    });
    return { hospital, doctor, status };
}

test('all assigned doctors load initially and hospital changes filter native options and reset selection', () => {
    const page = bookingPage();
    assert.deepEqual(page.doctor.options.map(option => option.value), ['', '7', '8']);
    page.hospital.value = '2'; page.hospital.events.change();
    assert.deepEqual(page.doctor.options.map(option => option.value), ['', '8']);
    assert.equal(page.doctor.value, '');
    page.hospital.value = '1'; page.hospital.events.change();
    assert.deepEqual(page.doctor.options.map(option => option.value), ['', '7']);
});

test('doctor selection fills matching hospital and old or deep-linked selection survives initial filtering', () => {
    const selected = bookingPage('2', '8');
    assert.equal(selected.doctor.value, '8');
    const page = bookingPage();
    page.doctor.value = '7'; page.doctor.events.change();
    assert.equal(page.hospital.value, '1');
    assert.equal(page.doctor.value, '7');
});

test('hospital without doctors shows a clear message and changing back restores options', () => {
    const page = bookingPage('3');
    assert.equal(page.doctor.disabled, true);
    assert.match(page.status.textContent, /No doctors are assigned/);
    page.hospital.value = '2'; page.hospital.events.change();
    assert.equal(page.doctor.disabled, false);
    assert.equal(page.status.textContent, '');
    assert.deepEqual(page.doctor.options.map(option => option.value), ['', '8']);
});
