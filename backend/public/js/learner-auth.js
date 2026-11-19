document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-auth-open]');
    if (opener) {
        const dialog = document.getElementById('auth-' + opener.dataset.authOpen);
        if (!dialog || typeof dialog.showModal !== 'function') return;
        event.preventDefault();
        document.querySelectorAll('.auth-dialog[open]').forEach(open => open.close());
        syncPremiumFlag(dialog, opener);
        dialog.showModal();
    }
    const close = event.target.closest('[data-auth-close]');
    if (close) close.closest('dialog').close();
});

// Carry a `premium` query param from the clicked link into the dialog's
// form, so signing up from a page that asked to offer Premium (e.g. the
// Premium page) still shows the offer after the modal submit. Without this
// the modal would drop the flag: its form only carries the page's own
// `premium` param, which is normally absent, and the link itself is never
// navigated to because the click is intercepted above.
function syncPremiumFlag(dialog, opener) {
    const form = dialog.querySelector('form');
    if (!form) return;
    let premium = null;
    try {
        premium = new URL(opener.getAttribute('href') || '', window.location.origin).searchParams.get('premium');
    } catch (e) {
        premium = null;
    }
    const input = form.querySelector('input[name="premium"]');
    if (premium) {
        if (input) {
            input.value = premium;
        } else {
            const created = document.createElement('input');
            created.type = 'hidden';
            created.name = 'premium';
            created.value = premium;
            form.prepend(created);
        }
    } else if (input) {
        input.remove();
    }
}
