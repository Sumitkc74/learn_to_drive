(() => {
  const root = document.documentElement;
  let saved;
  try { saved = localStorage.getItem('learner-theme'); } catch (_) {}
  const system = window.matchMedia('(prefers-color-scheme: dark)');
  const apply = dark => {
    root.dataset.theme = dark ? 'dark' : 'light';
    document.querySelectorAll('[data-theme-toggle]').forEach(button => button.setAttribute('aria-pressed', String(dark)));
  };
  apply(saved ? saved === 'dark' : system.matches);
  document.addEventListener('DOMContentLoaded', () => {
    apply(root.dataset.theme === 'dark');
    document.querySelectorAll('[data-theme-toggle]').forEach(button => button.addEventListener('click', () => {
      saved = root.dataset.theme === 'dark' ? 'light' : 'dark';
      apply(saved === 'dark');
      try { localStorage.setItem('learner-theme', saved); } catch (_) {}
    }));
  });
  system.addEventListener('change', event => { if (!saved) apply(event.matches); });
})();
