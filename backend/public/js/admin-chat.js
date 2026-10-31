(() => {
    const panel = document.getElementById('adminChatPanel');
    if (!panel) return;
    const toggle = document.getElementById('adminChatToggle');
    const input = document.getElementById('adminChatMessage');
    const form = document.getElementById('adminChatForm');
    const history = document.getElementById('adminChatHistory');
    const status = document.getElementById('adminChatStatus');
    const send = document.getElementById('adminChatSend');
    let busy = false;
    history.addEventListener('click', event => {
        const button = event.target.closest('[data-chat-ticket]');
        if (!button || busy) return;
        input.value = `Draft a reply to ticket #${button.dataset.chatTicket}`;
        form.requestSubmit();
    });
    const setOpen = open => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        if (open) { input.focus(); history.scrollTop = history.scrollHeight; }
        else toggle.focus();
    };
    toggle.addEventListener('click', () => setOpen(panel.hidden));
    document.getElementById('adminChatClose').addEventListener('click', () => setOpen(false));
    panel.addEventListener('keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); setOpen(false); }
    });
    if (new URLSearchParams(location.search).get('chat') === 'open') setOpen(true);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || !input.value.trim()) return;
        busy = true;
        send.disabled = true;
        input.readOnly = true;
        status.textContent = 'Thinking...';
        try {
            const response = await fetch(form.action, {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            });
            if (response.redirected || [401, 403, 419].includes(response.status)) {
                throw new Error('Your session has expired or access changed. Refresh and sign in again.');
            }
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Unable to send. Please try again.');
            history.replaceChildren();
            for (const entry of data.history) {
                const message = document.createElement('div');
                message.className = 'admin-chat__message';
                const label = document.createElement('strong');
                label.textContent = entry.role === 'user' ? 'You' : 'AI assistant';
                const text = document.createElement('p');
                text.textContent = entry.text;
                message.append(label, text);
                for (const source of entry.sources || []) {
                    const link = document.createElement('a');
                    link.href = source.url;
                    link.textContent = source.label;
                    message.append(link, document.createElement('br'));
                    if (!source.id) continue;
                    const choose = document.createElement('button');
                    choose.type = 'button';
                    choose.dataset.chatTicket = source.id;
                    choose.textContent = `Draft reply for #${source.id}`;
                    message.append(choose);
                }
                history.append(message);
            }
            input.value = '';
            history.scrollTop = history.scrollHeight;
            status.textContent = '';
        } catch (error) {
            status.textContent = error instanceof SyntaxError ? 'Unable to send. Please refresh and try again.' : error.message;
        } finally {
            busy = false;
            send.disabled = false;
            input.readOnly = false;
        }
    });
})();
