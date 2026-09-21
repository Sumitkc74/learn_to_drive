> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Premium checkout: Khalti and eSewa

Implemented using [Khalti Web Checkout](https://docs.khalti.com/khalti-epayment/) and [eSewa ePay](https://developer.esewa.com.np/pages/Epay). Merchant onboarding and sandbox/live credentials must be obtained from each provider.

Payments default to disabled. No real payment has been made or verified during development. Price and duration are deliberately unset until the owner selects them.

## Configuration

Set these in `.env`:

```dotenv
PREMIUM_PAYMENTS_ENABLED=false
PREMIUM_PAYMENTS_LIVE=false
PREMIUM_PRICE_PAISA=0
PREMIUM_ACCESS_DAYS=0
KHALTI_SECRET_KEY=
ESEWA_PRODUCT_CODE=
ESEWA_SECRET_KEY=
```

Set the price in integer paisa (100 paisa = NPR 1), duration in days, and test credentials. Set enabled to true for sandbox testing. The minimum configured total is NPR 10. Live mode must remain false until the merchant accounts are approved and sandbox acceptance testing is complete. Use a separate sandbox database; test transactions grant test access on that installation. Test checkout and verification are blocked when APP_ENV is production.

Run `php artisan migrate` and `php artisan config:clear`. Set APP_URL to the accessible application URL. Use HTTPS for production. Configure gateway merchant settings as required by the provider. The application sends a per-order return URL `/learn/premium/payments/{uuid}/return`. eSewa failure redirects go to the payment status page.

## Payment behavior

The Premium page displays the configured total and access duration. Prices are read on the server and copied to the order; browser-supplied amounts are ignored. eSewa forms are HMAC-signed on the server. Khalti initiation stores the provider pidx.

Return-page parameters are not proof of payment. Verification queries the provider directly using the stored order identity, validates the completed status, amount and identifiers, then credits access inside a database transaction. The callback payload itself is ignored, so a forged success redirect cannot grant access. Repeat verification does not extend access twice. Verified renewals extend remaining paid access. Expiry is enforced on each Premium access check without needing a scheduled job.

Purchases grant `premium_until`; they do not change a user's role. Existing admin-granted PremiumUser roles remain unlimited until an admin changes the role back. All Premium entry points use `User::hasPremium()`.

Learners can view only their own payment history and manually recheck pending payments. If a return is interrupted or the learner has signed out, sign in and use Premium → Payment history & status. If money was deducted, do not pay again before checking status. Pending status or gateway errors do not grant access.

## Operational limits

These are one-time purchases, not recurring subscriptions. No automated refunds, refund webhooks, or background settlement reconciliation are implemented. Refunds must be handled in the gateway merchant dashboard and any corresponding access adjustment handled by an administrator. Do not assume this integration automatically revokes access after a later refund. Pricing is a single final total; confirm any tax/accounting requirements before enabling production checkout.

Before going live, test successful, cancelled, failed, delayed, repeated and mismatched transactions through each real sandbox. Automated tests mock provider HTTP responses and cannot verify merchant credentials or provider onboarding. Run `php artisan test --filter=PremiumPaymentTest`.
