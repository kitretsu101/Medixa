<div {{ $attributes->merge(['class' => 'mt-3 flex flex-wrap items-center gap-3']) }} data-report-file-selection>
    <p data-selected-file role="status" aria-live="polite" class="min-w-0 break-all text-sm text-slate-600">No file selected.</p>
    <button type="button" data-remove-file class="hidden rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-sm font-semibold text-rose-700 hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600">Remove file</button>
</div>

@pushOnce('scripts', 'report-file-selection')
<script>
    document.querySelectorAll('[data-report-file-selection]').forEach(selection => {
        const input = selection.closest('form')?.querySelector('input[name="report_file"]');
        if (!input) return;
        const status = selection.querySelector('[data-selected-file]');
        const remove = selection.querySelector('[data-remove-file]');
        function updateSelection() {
            const file = input.files?.[0];
            status.textContent = file
                ? `Selected file: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`
                : 'No file selected.';
            remove.classList.toggle('hidden', !file);
        }
        input.addEventListener('change', updateSelection);
        remove.addEventListener('click', () => {
            input.value = '';
            updateSelection();
            input.focus();
        });
        updateSelection();
    });
</script>
@endPushOnce
