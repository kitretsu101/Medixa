import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const component = readFileSync(new URL('../../resources/views/components/report-file-selection.blade.php', import.meta.url), 'utf8');
const script = component.match(/<script>([\s\S]*?)<\/script>/)[1];

function selectionControl() {
    const hidden = new Set();
    const status = { textContent: '' };
    const remove = { events: {}, classList: { toggle(name, enabled) { enabled ? hidden.add(name) : hidden.delete(name); } }, addEventListener(name, callback) { this.events[name] = callback; } };
    const input = {
        files: [], events: {}, focused: false,
        set value(value) { assert.equal(value, ''); this.files = []; },
        addEventListener(name, callback) { this.events[name] = callback; },
        focus() { this.focused = true; },
    };
    const selection = {
        closest: () => ({ querySelector: () => input }),
        querySelector: selector => selector === '[data-selected-file]' ? status : remove,
    };
    runInNewContext(script, { document: { querySelectorAll: () => [selection] } });
    return { input, status, remove, hidden };
}

test('file selection displays its name and size without claiming the upload succeeded', () => {
    const page = selectionControl();
    assert.equal(page.status.textContent, 'No file selected.');
    assert.equal(page.hidden.has('hidden'), true);
    page.input.files = [{ name: '<report>.pdf', size: 3 * 1024 * 1024 }];
    page.input.events.change();
    assert.equal(page.status.textContent, 'Selected file: <report>.pdf (3.00 MB)');
    assert.equal(page.hidden.has('hidden'), false);
    assert.doesNotMatch(page.status.textContent, /uploaded successfully/i);
});

test('remove clears the file input and hides the remove button', () => {
    const page = selectionControl();
    page.input.files = [{ name: 'report.png', size: 1024 }];
    page.input.events.change();
    page.remove.events.click();
    assert.equal(page.input.files.length, 0);
    assert.equal(page.status.textContent, 'No file selected.');
    assert.equal(page.hidden.has('hidden'), true);
    assert.equal(page.input.focused, true);
});

test('another file can be selected after removal', () => {
    const page = selectionControl();
    page.input.files = [{ name: 'first.pdf', size: 1024 }];
    page.input.events.change();
    page.remove.events.click();
    page.input.files = [{ name: 'replacement.jpg', size: 1024 * 1024 }];
    page.input.events.change();
    assert.equal(page.status.textContent, 'Selected file: replacement.jpg (1.00 MB)');
    assert.equal(page.hidden.has('hidden'), false);
});
