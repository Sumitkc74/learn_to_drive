(() => {
    const menus = [...document.querySelectorAll('.language-switch, .account-menu')];
    document.addEventListener('click', event => {
        menus.forEach(menu => { if (!menu.contains(event.target)) menu.open = false; });
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        menus.forEach(menu => {
            if (menu.open) {
                const restore = menu.contains(document.activeElement);
                menu.open = false;
                if (restore) menu.querySelector('summary').focus();
            }
        });
    });
})();
