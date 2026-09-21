document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-auth-open]');
    if (opener) {
        const dialog = document.getElementById('auth-' + opener.dataset.authOpen);
        if (!dialog || typeof dialog.showModal !== 'function') return;
        event.preventDefault();
        document.querySelectorAll('.auth-dialog[open]').forEach(open => open.close());
        dialog.showModal();
    }
    const close = event.target.closest('[data-auth-close]');
    if (close) close.closest('dialog').close();
});
