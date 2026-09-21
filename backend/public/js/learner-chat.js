(() => {
    const toggle = document.getElementById('study-chat-toggle');
    const panel = document.getElementById('study-chat-panel');
    if (!toggle || !panel) return;
    const input = document.getElementById('study-chat-input');
    const form = document.getElementById('study-chat-form');
    const status = document.getElementById('study-chat-status');
    const messages = document.getElementById('study-chat-messages');
    const close = () => { panel.hidden = true; toggle.setAttribute('aria-expanded', 'false'); toggle.focus(); };
    toggle.addEventListener('click', () => {
        if (!panel.hidden) return close();
        panel.hidden = false; toggle.setAttribute('aria-expanded', 'true'); input.focus();
    });
    document.getElementById('study-chat-close').addEventListener('click', close);
    panel.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
    const append = (text, kind) => {
        const bubble = document.createElement('p'); bubble.className = kind; bubble.textContent = text;
        messages.append(bubble); messages.scrollTop = messages.scrollHeight;
    };
    let pending = false;
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (pending || !input.value.trim()) return;
        const data = new FormData(form); const question = input.value;
        pending = true; form.querySelector('button').disabled = true; input.readOnly = true;
        status.textContent = form.dataset.thinking;
        try {
            const response = await fetch(form.action, { method: 'POST', body: data, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (response.redirected || !(response.headers.get('content-type') || '').includes('application/json')) throw new Error(form.dataset.sessionError);
            const body = await response.json();
            if (!response.ok) throw new Error(body.errors ? Object.values(body.errors).flat().join(' ') : body.message || form.dataset.sendError);
            append(question, 'widget-question'); append(body.answer, 'widget-reply'); input.value = ''; status.textContent = '';
        } catch (error) { status.textContent = error instanceof TypeError ? form.dataset.connectionError : error.message || form.dataset.connectionError; }
        finally { pending = false; input.readOnly = false; form.querySelector('button').disabled = false; if (!panel.hidden) input.focus(); }
    });
})();
