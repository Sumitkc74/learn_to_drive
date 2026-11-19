(() => {
    // One-time Premium offer after creating an account from the Premium
    // page. The dialog only exists when the `premium_offer` flash is set,
    // so opening it here shows it exactly once. Esc is native to
    // <dialog>; the close button and backdrop clicks close it too,
    // following the same pattern as school-dialog.js.
    const dialog = document.getElementById('premium-offer');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    dialog.showModal();

    document.addEventListener('click', (event) => {
        const close = event.target.closest('[data-premium-offer-close]');
        if (close) {
            close.closest('dialog').close();
            return;
        }
        // Backdrop click: the click target is the dialog element itself.
        if (event.target instanceof HTMLDialogElement && event.target.id === 'premium-offer') {
            event.target.close();
        }
    });
})();
