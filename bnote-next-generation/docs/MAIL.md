# Outbound mail for BNote Next Generation (PHP)

Next Gen sends mail from **`bnote-next-generation/api/mail/`** with **PHPMailer** (password reset, registration, etc.). Legacy `BNote/src/logic/Mailing.php` is not used for these features.

If SMTP is not configured, or BNote runs in **demo mode**, those emails are skipped. Password-reset links in mail need **`NEXTGEN_PUBLIC_URL`** set to your real site address, or the message will explain that the link cannot be generated.

---

## Where settings go (important)

- Configure mail **on the web server**, in the environment **visible to PHP** (`getenv`). Typical methods: Apache **`SetEnv`** in **`.htaccess`** or vhost, or **php-fpm** / **Nginx** env directives.
- **Do not** put `MAIL_PASSWORD` in git, in the Next.js repo as a committed file, or in any **`NEXT_PUBLIC_*`** variable (those are exposed to the browser).
- **`BNote/config/config.xml`** controls **DemoMode** and company data, **not** SMTP credentials for Next Gen mail.

---

## Before you start

Gather:

1. SMTP **host**, **port**, and whether your provider uses **TLS** (often port 587) or **SSL / implicit TLS** (often port 465).
2. Mailbox **login** (usually the full email address) and **password**.
3. The address you want as **From** (often the same mailbox).
4. The **exact HTTPS URL** visitors use to open the app: scheme + domain + path, **no trailing slash**  
   Example: `https://www.example.de/bnote-next-generation`

---

## Default tutorial: STRATO shared hosting

On Strato **PowerWeb** / shared hosting you usually **cannot** edit `httpd.conf`. Use a **`.htaccess`** file in the folder that contains your deployed app (the directory that includes **`api/`** and the static site), e.g. **`bnote-next-generation/.htaccess`**.

### Strato outbound SMTP

| Setting | Value |
|--------|--------|
| Server | `smtp.strato.de` |
| Port + encryption (recommended) | **465** and **`ssl`** (SMTPS) |
| Alternative | **587** and **`tls`** (STARTTLS) |
| Username | Full mailbox address (e.g. `mail@your-domain.de`) |
| Password | That mailbox’s password |

Official Strato help (verify if wording changes): [`.htaccess` anpassen](https://www.strato.de/faq/hosting/wie-kann-ich-die-htaccess-anpassen/) and Strato’s email/SMTP documentation for your product.

### Example `.htaccess` (placeholders only)

Replace every `…` with your real values. **Do not commit** this file with real passwords to a public repository.

```apache
# BNote Next Gen mail + public URL for links in email (no trailing slash on NEXTGEN_PUBLIC_URL)
SetEnv MAIL_HOST smtp.strato.de
SetEnv MAIL_PORT 465
SetEnv MAIL_ENCRYPTION ssl
SetEnv MAIL_USERNAME mail@your-domain.de
SetEnv MAIL_PASSWORD 'your-mailbox-password'
SetEnv MAIL_FROM_ADDRESS mail@your-domain.de
SetEnv MAIL_FROM_NAME "Your band name"
SetEnv NEXTGEN_PUBLIC_URL https://www.your-domain.de/bnote-next-generation
```

If the mailbox password contains spaces or special characters, wrap the value in **single quotes** as shown, or escape per [Apache SetEnv](https://httpd.apache.org/docs/current/mod/mod_env.html#setenv) rules.

After saving, reload the site and trigger a test (e.g. password reset). If **`getenv('MAIL_HOST')`** is still empty in PHP, your host may not allow `SetEnv` in `.htaccess`; contact **Strato support** or check whether overrides are enabled for your package.

### Environment variables (reference)

| Variable | Purpose | Strato example (no secrets) |
|----------|---------|------------------------------|
| `MAIL_HOST` | SMTP server | `smtp.strato.de` |
| `MAIL_PORT` | SMTP port | `465` (with `ssl`) or `587` (with `tls`) |
| `MAIL_ENCRYPTION` | PHPMailer mode | `ssl` or `tls` |
| `MAIL_USERNAME` | SMTP auth user | Full email address |
| `MAIL_PASSWORD` | SMTP auth password | (your mailbox password) |
| `MAIL_FROM_ADDRESS` | From header | Usually same as mailbox |
| `MAIL_FROM_NAME` | From display name | e.g. band name |
| `NEXTGEN_PUBLIC_URL` | Base URL for links in mail | `https://domain/path` — **no** trailing `/` |

Optional fallback instead of `NEXTGEN_PUBLIC_URL`: set **`NEXTGEN_PUBLIC_ORIGIN`** (e.g. `https://www.example.de`) and **`NEXT_PUBLIC_BASE_PATH`** (e.g. `/bnote-next-generation`); PHP combines them.

---

## Other hosting (VPS, dedicated, other providers)

- **Apache:** use `SetEnv` in the **virtual host** or `httpd.conf`, then restart Apache.
- **Nginx + php-fpm:** pass variables with **`fastcgi_param`** or in the **pool** file as `env[MAIL_HOST]=…`; restart php-fpm (and nginx if needed).

Ask your host **which mechanism exposes environment variables to PHP** if unsure.

---

## Demo mode

In **`BNote/config/config.xml`**, set **`<DemoMode>False</DemoMode>`** so Next Gen can send real mail. In demo mode, public mail is skipped; password reset may expose `dev_reset_url` in JSON for local testing only.

---

## Build and deploy

- Run **`./build.sh`** from `bnote-next-generation/`. The script runs **`composer install`** in **`api/`** so **`vendor/`** (PHPMailer) is included in the upload bundle.
- The production bundle **does not** include local diagnostic scripts **`mail_test_send.php`** and **`mail_config_check.php`** (they stay in the git repo for developers only).

---

## Local testing (developers only)

These scripts work only from **127.0.0.1** or **::1** and are **not** shipped in the official build:

1. **Config check** (JSON, no secrets except presence flags):  
   `http://127.0.0.1:…/bnote-next-generation/api/mail_config_check.php`  
   (adjust path to match your local server.)

2. **Test send** — **required** query parameter **`to`**:  
   `…/api/mail_test_send.php?to=you@example.com`

---

## Troubleshooting

- **No mail arrives:** spam folder; confirm PHP sees variables (not only your SSH shell); provider blocking outbound SMTP; wrong port/encryption pair.
- **Reset email without a button / “administrator must set NEXTGEN_PUBLIC_URL”:** set **`NEXTGEN_PUBLIC_URL`** to the **live** HTTPS URL users open in the browser (no trailing slash).
- **“PHPMailer not installed” on server:** deploy a full **`./build.sh`** output that includes **`api/vendor/`**, or run **`composer install --no-dev`** on the server inside **`api/`** (if you have SSH).

---

## Developer: install PHPMailer without `./build.sh`

From **`bnote-next-generation/api/`**:

```bash
composer install
```

Uses **`composer.lock`**. **`vendor/`** is gitignored.

---

## Copy (i18n)

Templates and subjects live in **`bnote-next-generation/lang/<locale>.json`** under keys like `mail.passwordReset.*`. The mail layer uses `SystemData::getLang()` with fallback `en`.

---

## Adding a notification

1. Add `mail.*` strings to all `lang/*.json` files.
2. Add a small builder under `api/mail/builders/` that returns a `NextGenMailMessage`.
3. Call `NextGenMailer` from feature code; treat `false` as non-fatal and log.

---

## Password reset (database)

The **`password_reset_token`** table is created automatically on the first password-reset request or completion (`PasswordResetSchema`), with the same DDL as **`api/migrations/password_reset_token.sql`** (FK first, fallback without FK if the server rejects it). You may run that SQL manually for controlled migrations.
