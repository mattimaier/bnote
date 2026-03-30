# Next Gen outbound mail (PHP)

Outbound email for the Next.js companion app is sent from **`bnote-next-generation/api/mail/`** using **PHPMailer**. Legacy `BNote/src/logic/Mailing.php` is not used for these sends.

## Install

From `bnote-next-generation/api/`:

```bash
composer install
```

This installs `phpmailer/phpmailer` per `composer.json`. The `vendor/` directory is gitignored.

## Environment variables

| Variable | Purpose |
|----------|---------|
| `MAIL_HOST` | SMTP host |
| `MAIL_PORT` | SMTP port (e.g. 587) |
| `MAIL_USERNAME` | SMTP user |
| `MAIL_PASSWORD` | SMTP password |
| `MAIL_ENCRYPTION` | e.g. `tls` or `ssl` (PHPMailer) |
| `MAIL_FROM_ADDRESS` | From address |
| `MAIL_FROM_NAME` | From display name |
| `NEXTGEN_PUBLIC_URL` | Public base URL for links in mail (fallback: `NEXTGEN_PUBLIC_ORIGIN` + `NEXT_PUBLIC_BASE_PATH`) |

If required variables are missing, or the app runs in demo mode, sends are skipped (no exception to callers).

## Copy (i18n)

Templates and subjects live in **`bnote-next-generation/lang/<locale>.json`** under keys like `mail.newUserAdmin.*`. The mail layer uses `SystemData::getLang()` with fallback `en`.

## Adding a notification

1. Add `mail.*` strings to all `lang/*.json` files.
2. Add a small builder under `api/mail/builders/` that returns a `NextGenMailMessage`.
3. Call `NextGenMailer` from feature code; treat `false` as non-fatal and log.
