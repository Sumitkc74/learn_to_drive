// Auto-verify a pending Premium payment on the payment status page, so
// the account updates without the user pressing "Check payment status".
// Polls the verify endpoint every 15 seconds, up to 8 times (about 2
// minutes), staying under the 6-per-minute verify throttle. On a paid
// result the page reloads to show Premium as active. Failures and
// not-yet-paid results just keep polling; the manual button remains
// as a fallback after polling stops.
(() => {
  const el = document.querySelector('[data-payment-verify]');
  if (!el) return;
  const url = el.dataset.paymentVerify;
  const token = el.dataset.csrf;
  let attempts = 0;
  async function check() {
    attempts += 1;
    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: '{}',
      });
      if (response.ok) {
        const data = await response.json();
        if (data.paid) {
          window.location.reload();
          return;
        }
      }
    } catch (_) {
      // Gateway lookups can fail transiently; keep polling.
    }
    if (attempts < 8) setTimeout(check, 15000);
  }
  check();
})();
