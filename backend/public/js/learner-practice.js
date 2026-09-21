(() => {
    const form = document.querySelector('[data-draft-url]');
    if (!form) return;
    const status = document.getElementById('draft-status');
    let pending = false, dirty = false, submitting = false;
    async function save() {
        if (pending || submitting || !dirty) return;
        pending = true; dirty = false;
        status.textContent = form.dataset.saving;
        try {
            const data = new FormData(form);
            data.set('_method', 'PATCH');
            const response = await fetch(form.dataset.draftUrl, { method: 'POST', body: data, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok || response.redirected) throw new Error();
            status.textContent = form.dataset.saved;
        } catch (_) {
            dirty = true;
            status.textContent = form.dataset.saveError;
            pending = false;
            return;
        }
        pending = false;
        if (dirty) save();
    }
    form.addEventListener('change', event => {
        if (event.target.matches('input[type=radio]')) { dirty = true; save(); }
    });
    form.addEventListener('submit', () => { submitting = true; });
    window.addEventListener('online', save);
    window.addEventListener('beforeunload', event => {
        if (!submitting && (dirty || pending)) { event.preventDefault(); event.returnValue = ''; }
    });
})();
