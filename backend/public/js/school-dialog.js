(() => {
    // School detail dialogs on Find Schools. Follows the same delegated
    // pattern as learner-auth.js: a name button opens its dialog, the
    // close button closes it. Esc is native to <dialog>. Backdrop clicks
    // close too, and focus returns to the name that opened the dialog.
    let lastOpener = null;

    const openDialog = (dialog, opener) => {
        if (!dialog || typeof dialog.showModal !== 'function') return false;
        document.querySelectorAll('.school-dialog[open]').forEach(open => open.close());
        lastOpener = opener || null;
        dialog.showModal();
        return true;
    };

    // Called from map marker popups so a marker opens the same dialog.
    window.openSchoolDialog = (schoolId) => {
        const opener = document.querySelector('[data-open-school-dialog="school-' + schoolId + '"]');
        return openDialog(document.getElementById('school-' + schoolId), opener);
    };

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-open-school-dialog]');
        if (opener) {
            event.preventDefault();
            openDialog(document.getElementById(opener.dataset.openSchoolDialog), opener);
            return;
        }
        const close = event.target.closest('[data-school-close]');
        if (close) {
            close.closest('dialog').close();
            return;
        }
        // Backdrop click: the click target is the dialog element itself.
        if (event.target instanceof HTMLDialogElement && event.target.classList.contains('school-dialog')) {
            event.target.close();
        }
    });

    // The close event does not bubble, so listen in capture phase.
    document.addEventListener('close', (event) => {
        if (event.target instanceof HTMLDialogElement && event.target.classList.contains('school-dialog') && lastOpener) {
            lastOpener.focus();
            lastOpener = null;
        }
    }, true);
})();
