(() => {
    'use strict';
    const labels = { question: 'Question', option1: 'Option A', option2: 'Option B', option3: 'Option C', option4: 'Option D', explanation: 'Explanation' };
    document.querySelectorAll('[data-question-translation]').forEach(panel => {
        const form = panel.closest('form'), start = panel.querySelector('[data-translation-start]');
        if (!form || !start) return;
        const direction = panel.querySelector('[data-translation-direction]');
        const status = panel.querySelector('[data-translation-status]');
        const cancel = panel.querySelector('[data-translation-cancel]');
        const preview = panel.querySelector('[data-translation-preview]');
        const fields = panel.querySelector('[data-translation-fields]');
        const originalFields = Object.fromEntries(Object.keys(labels).map(key => [key, form.elements.namedItem(key)]));
        if (Object.values(originalFields).some(field => !field)) return;
        direction.value = /[\u0900-\u097f]/u.test(originalFields.question.value) ? 'ne' : 'en';
        let controller = null, snapshot = null, editors = {}, busy = false, translatedLanguage = null;
        const snapshotNow = () => JSON.stringify(Object.fromEntries(Object.entries(originalFields).map(([key, field]) => [key, field.value])));
        function reset() { preview.hidden = true; fields.replaceChildren(); editors = {}; snapshot = null; }
        cancel.addEventListener('click', () => controller?.abort());
        panel.querySelector('[data-translation-discard]').addEventListener('click', () => { reset(); status.textContent = 'Translation discarded. Form unchanged.'; });
        form.addEventListener('submit', event => {
            if (busy) { event.preventDefault(); event.stopImmediatePropagation(); status.textContent = 'Wait for translation or cancel it before saving.'; }
        }, true);
        start.addEventListener('click', async () => {
            if (busy) return;
            reset();
            const originals = Object.fromEntries(Object.entries(originalFields).map(([key, field]) => [key, field.value]));
            if (!originals.question.trim()) { status.textContent = 'Enter a question first.'; return; }
            snapshot = snapshotNow();
                const language = direction.value;
            controller = new AbortController(); busy = true; start.disabled = true; direction.disabled = true; cancel.hidden = false;
            const results = {};
            try {
                for (const [key, value] of Object.entries(originals)) {
                    if (!value.trim()) { results[key] = ''; continue; }
                    status.textContent = 'Translating ' + labels[key].toLowerCase() + '…';
                    const response = await fetch(panel.dataset.url, {
                        method: 'POST', credentials: 'same-origin', signal: controller.signal,
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.elements.namedItem('_token').value },
                        body: JSON.stringify({ field: key, text: value, source_language: language })
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(response.status === 429 ? 'Translation limit reached. Try again later.' : (data.message || 'Translation could not complete.'));
                    if (data.field !== key || typeof data.text !== 'string' || !data.text.trim()) throw new Error('Incomplete translation received.');
                    results[key] = data.text;
                }
                if (snapshotNow() !== snapshot) throw new Error('The form changed during translation. Translate again to include your edits.');
                for (const key of Object.keys(labels)) {
                    const group = document.createElement('div'); group.className = 'form-group';
                    const heading = document.createElement('strong'); heading.textContent = labels[key]; group.append(heading);
                    const original = document.createElement('p'); original.className = 'small text-muted'; original.style.whiteSpace = 'pre-wrap'; original.textContent = 'Original: ' + (originals[key] || '(empty)'); group.append(original);
                    const label = document.createElement('label'); label.className = 'd-block'; label.textContent = 'Translated ' + labels[key].toLowerCase();
                    const editor = document.createElement('textarea'); editor.className = 'form-control'; editor.rows = key === 'question' || key === 'explanation' ? 3 : 2;
                    editor.value = results[key]; editor.lang = language === 'ne' ? 'en' : 'ne';
                    // Show all text even when expansion exceeds the saved field limit.
                    const limit = originalFields[key].maxLength;
                    const counter = document.createElement('small');
                    const count = () => { counter.textContent = Array.from(editor.value).length + ' / ' + limit + ' characters'; };
                    editor.addEventListener('input', count); count();
                    label.append(editor); group.append(label, counter); fields.append(group); editors[key] = editor;
                }
                translatedLanguage = language === 'ne' ? 'en' : 'ne';
                preview.hidden = false; status.textContent = 'Review all translated details, then apply them. Nothing has been saved.';
            } catch (error) {
                reset(); status.textContent = error.name === 'AbortError' ? 'Translation cancelled. Form unchanged.' : error.message + ' Form unchanged.';
            } finally { busy = false; start.disabled = false; direction.disabled = false; cancel.hidden = true; controller = null; }
        });
        panel.querySelector('[data-translation-apply]').addEventListener('click', () => {
            if (!snapshot || snapshotNow() !== snapshot) { status.textContent = 'The form has changed. Discard and translate again before applying.'; return; }
            for (const [key, editor] of Object.entries(editors)) {
                if (Array.from(editor.value).length > originalFields[key].maxLength || (key !== 'explanation' && originalFields[key].value.trim() && !editor.value.trim())) {
                    status.textContent = 'Check ' + labels[key] + ': keep its text within the displayed limit.'; editor.focus(); return;
                }
            }
            const options = ['option1', 'option2', 'option3', 'option4'].map(key => editors[key].value.trim()).filter(Boolean);
            if (new Set(options).size !== options.length) { status.textContent = 'Two translated options are identical. Correct them before applying.'; return; }
            for (const [key, editor] of Object.entries(editors)) { originalFields[key].value = editor.value; originalFields[key].dispatchEvent(new Event('input', { bubbles: true })); }
            const confirmation = form.elements.namedItem('confirmed'); if (confirmation) confirmation.checked = false;
            direction.value = translatedLanguage;
            reset(); status.textContent = 'Translation applied. Check the correct answer, then save or import the question.';
        });
    });
})();
