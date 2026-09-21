document.querySelectorAll('[data-session-countdown]').forEach(clock => {
  const end = Date.parse(clock.dataset.sessionCountdown);
  if (!Number.isFinite(end)) return;
  const serverNow = Date.parse(clock.dataset.serverNow) || Date.now();
  const started = performance.now();
  const update = () => {
    const seconds = Math.max(0, Math.floor((end - (serverNow + performance.now() - started)) / 1000));
    clock.textContent = `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
    return seconds;
  };
  update();
  const timer = setInterval(() => { if (!update()) { clearInterval(timer); if(clock.dataset.autoFinalize === 'true') window.location.reload(); } }, 1000);
});
