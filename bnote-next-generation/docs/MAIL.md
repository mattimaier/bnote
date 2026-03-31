# Outbound mail for BNote Next Generation (PHP)

Next Gen sends mail from **`bnote-next-generation/api/mail/`** with **PHPMailer** over **SMTP** (see `MailEnv`).

If SMTP is not configured, or BNote runs in **demo mode**, those emails are skipped. Password-reset links in mail need **`BNOTE_NEXT_GENERATION_PUBLIC_URL`** set to your real site address, or the message will explain that the link cannot be generated.

---

## Subsystem map

**Recipient policy:** **`MailRecipientPolicy`** ([`api/mail/MailRecipientPolicy.php`](../api/mail/MailRecipientPolicy.php)) applies to outbound Next Gen mail: delivery is skipped for `example.com` and `*.example.com` (reserved / placeholder domains).

| Flow | Entry point | Builder / notifier | Transactional policy? | Send API |
|------|-------------|--------------------|------------------------|----------|
| Password reset | `api/modules/auth.php` → `requestPasswordReset` | `PasswordResetMailBuilder` | No (system mail) | `NextGenMailer::send` |
| New registration → administrators | `api/nextgen_registration.php` | `RegistrationAdminNotifier` / `NewUserAdminMailBuilder` | No (system mail) | `NextGenMailer::sendBulk` |
| Rehearsal/concert participant added | `api/modules/rehearsals.php`, `api/modules/concerts.php` | `EventParticipantNotifier` / `EventParticipantInviteMailBuilder` | Yes (`NextGenMailPolicy`) | `NextGenMailer::sendBulk` |
| Rehearsal/concert event-info group mail | `api/modules/rehearsals.php`, `api/modules/concerts.php` (`emailInfoDraft/Preview/Send`) | `EventInfoMailService` / `EventInfoMailBuilder` | Yes (`NextGenMailPolicy`) | `NextGenMailer::send` |
| Generic email composer (module 7 / Kommunikation) | `api/modules/email.php` (`meta/draft/preview/send`) | `GenericEmailComposerService` / `GenericEmailComposerMailBuilder` | Yes (`NextGenMailPolicy`) | `NextGenMailer::send` |
| Task assignee create/update | `api/modules/tasks.php` | `TaskNotificationMailBuilder` | Yes | `NextGenMailer::send` |
| Entity comment added | `api/modules/comments.php` | `CommentDiscussionNotifier` / `CommentDiscussionMailBuilder` | Yes | `NextGenMailer::sendBulk` |

**Preview template IDs** (for `api/debug/mail_preview.php?template=…`): canonical list is **`MailPreviewRegistry::templates()`** in [`api/mail/MailPreviewRegistry.php`](../api/mail/MailPreviewRegistry.php) — `password_reset`, `new_user_admin`, `long_demo`, `comment_discussion_*`, `event_invite_*`, `task_assigned`, `task_updated`.

**Local JSON helper:** [`api/debug/mail_comment_recipients.php`](../api/debug/mail_comment_recipients.php) exposes who would receive discussion mail (same rules as `CommentDiscussionNotifier`).

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
# BNote Next Gen mail + public URL for links in email (no trailing slash on BNOTE_NEXT_GENERATION_PUBLIC_URL)
SetEnv MAIL_HOST smtp.strato.de
SetEnv MAIL_PORT 465
SetEnv MAIL_ENCRYPTION ssl
SetEnv MAIL_USERNAME mail@your-domain.de
SetEnv MAIL_PASSWORD 'your-mailbox-password'
SetEnv MAIL_FROM_ADDRESS mail@your-domain.de
SetEnv MAIL_FROM_NAME "Your band name"
SetEnv BNOTE_NEXT_GENERATION_PUBLIC_URL https://www.your-domain.de/bnote-next-generation
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
| `BNOTE_NEXT_GENERATION_PUBLIC_URL` | Base URL for links in mail | `https://domain/path` — **no** trailing `/` |
| `BNOTE_NEXT_GENERATION_MAIL_BULK_DELAY_MS` | Pause between each message when notifying many recipients (comment thread, new-user admins). Milliseconds; **`0`** = send back-to-back. If unset, defaults to **100** to reduce SMTP rate limits (e.g. ~50 recipients). | `150` or `0` |
| `BNOTE_NEXT_GENERATION_REMINDER_SECRET` | Shared secret for signed calls to `api/reminders_run.php` | random 32+ bytes |
| `BNOTE_NEXT_GENERATION_REMINDER_ALLOWED_SKEW_SECONDS` | Allowed timestamp skew for signed reminder endpoint requests | `300` |

Optional fallback instead of `BNOTE_NEXT_GENERATION_PUBLIC_URL`: set **`BNOTE_NEXT_GENERATION_PUBLIC_ORIGIN`** (e.g. `https://www.example.de`) and **`NEXT_PUBLIC_BASE_PATH`** (e.g. `/bnote-next-generation`); PHP combines them.

---

## Weekly summary reminders (static hosting)

If your host cannot run cron, use an **external scheduler**.

### How it works

1. Configure reminder behavior in the app (**Settings → Reminder Emails**, admin-only):
   - enabled on/off
   - weekday/time in UTC
   - recipient scope
   - event window days + max events
   - include votes/tasks + max counts
2. External scheduler sends a signed `POST` to:
   - `https://your-domain/.../bnote-next-generation/api/reminders_run.php`
3. Endpoint verifies:
   - HMAC signature
   - timestamp window
   - nonce replay protection
4. Service sends digest mails and stores per-user weekly run idempotency.

### Digest behavior (exact rules)

- Recipients are active users with `email_notification = 1` and a valid contact email.
- A digest is sent only when the user has at least one **future** rehearsal/concert (`future_event_count > 0`).
- Participation state does **not** block sending: fully responded users still receive the digest.
- **Upcoming events** section includes only events in the next **7 days**, sorted with pending-response events first, then by `replyUntil`, and limited by `max_events`.
- **Open responses** section includes only rehearsals/concerts with unset participation (`participation < 0`) within `event_window_days`, also limited by `max_events`.
- Votes/tasks are included only when enabled by config (`include_votes`, `include_tasks`) and are limited by `max_votes` / `max_tasks`.
- Empty sections are omitted from HTML and plain-text output.

### Scheduling responsibility (important)

- The external scheduler controls **request cadence** (when `POST`s are sent).
- App settings (`enabled`, `weekday_utc`, `time_utc`) control **due-time gating** (whether a given request should send now).
- Recommended operation:
  - run the external trigger more frequently (e.g. hourly),
  - keep weekly timing in app settings,
  - rely on app due-check + weekly idempotency to avoid duplicates.
- `force=true` bypasses due-time gating intentionally (operator/debug path).

### Signed request headers

- `X-Reminder-Timestamp` (unix timestamp, seconds)
- `X-Reminder-Nonce` (random unique value per request)
- `X-Reminder-Signature` (`sha256` HMAC hex)

Canonical string:

```text
METHOD|PATH|TIMESTAMP|NONCE|BODY
```

Signature:

```text
hex(hmac_sha256(BNOTE_NEXT_GENERATION_REMINDER_SECRET, canonical))
```

### GitHub Actions example (weekly UTC)

Store these in GitHub repository/environment secrets:
- `BNOTE_NEXT_GENERATION_REMINDER_ENDPOINT` (full HTTPS URL to `api/reminders_run.php`)
- `BNOTE_NEXT_GENERATION_REMINDER_SECRET` (same value as server `BNOTE_NEXT_GENERATION_REMINDER_SECRET`)

```yaml
name: Weekly Reminder Digest

on:
  schedule:
    - cron: "0 8 * * 1" # Monday 08:00 UTC
  workflow_dispatch:

jobs:
  run-reminder:
    runs-on: ubuntu-latest
    steps:
      - name: Call signed reminder endpoint
        env:
          ENDPOINT: ${{ secrets.BNOTE_NEXT_GENERATION_REMINDER_ENDPOINT }}
          SECRET: ${{ secrets.BNOTE_NEXT_GENERATION_REMINDER_SECRET }}
        run: |
          set -euo pipefail
          TS="$(date +%s)"
          NONCE="$(openssl rand -hex 16)"
          BODY='{"dryRun":false}'
          PATH_ONLY="$(python3 - <<'PY'
import os, urllib.parse
u = urllib.parse.urlparse(os.environ["ENDPOINT"])
print(u.path or "/")
PY
)"
          CANONICAL="POST|${PATH_ONLY}|${TS}|${NONCE}|${BODY}"
          SIG="$(printf '%s' "${CANONICAL}" | openssl dgst -sha256 -hmac "${SECRET}" -hex | sed 's/^.* //')"
          curl --fail --show-error --silent \
            -X POST "${ENDPOINT}" \
            -H "Content-Type: application/json" \
            -H "X-Reminder-Timestamp: ${TS}" \
            -H "X-Reminder-Nonce: ${NONCE}" \
            -H "X-Reminder-Signature: ${SIG}" \
            --data "${BODY}"
```

### Testing without scheduler wait

- **Settings → Reminder Emails** (admin): run dry-run or real run.
- **Developer tools**: reminder scheduler trigger panel (admin).
- **Mail template index / preview**: includes reminder digest templates for visual QA.

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
- The production **`./build.sh`** bundle **does not** include the **`api/debug/`** folder (loopback mail previews, `mail_config_check.php`, `mail_test_send.php`, etc.). Those files remain in the git repo; set **`NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS=1`** when running **`./build.sh`** to ship them for staging.
- If `.deploy.env` contains `BNOTE_NEXT_GENERATION_REMINDER_SECRET` and `BNOTE_NEXT_GENERATION_REMINDER_ALLOWED_SKEW_SECONDS`, `build.sh` writes them into **`api/config/mail.local.php`** in the deploy bundle (same as other `MAIL_*` runtime values).

---

## Local testing (developers only)

These scripts live under **`api/debug/`**, work only from **127.0.0.1** or **::1**, and are **not** shipped in the default **`./build.sh`** output (set **`NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS=1`** when building to include them):

1. **Config check** (JSON, no secrets except presence flags):  
   `http://127.0.0.1:…/bnote-next-generation/api/debug/mail_config_check.php`  
   (adjust path to match your local server.)

2. **Test send** — **required** query **`to`**; optional **`template`** (default `password_reset`) and **`locale`** (`en` / `de` / `es` / `fr`, default `en`). The message is built with **`MailPreviewRegistry::build()`** (same bodies as **`mail_preview.php`**), then addressed only to **`to`**.  
   Example: `…/api/debug/mail_test_send.php?to=you@example.com&template=event_invite_concert&locale=de`

3. **HTML preview** (no SMTP): open **`api/debug/mail_debug.php`** for a list of templates and locales, or call **`api/debug/mail_preview.php?template=password_reset&locale=en`**. Every supported `template=` id is listed in **`MailPreviewRegistry::templates()`** (see [Subsystem map](#subsystem-map-handover)); examples include **`long_demo`** (stress-test layout), **`comment_discussion_*`**, **`event_invite_*`**, **`task_assigned`** / **`task_updated`**. Example: `api/debug/mail_preview.php?template=comment_discussion_rehearsal_short&locale=de`  
   The JSON from **`mail_config_check.php`** includes a **`mailDebug`** object with paths under **`api/debug/`**. Logo uses a data URL in the browser; real sends use a CID attachment.

**Dark mode:** HTML mail sets `color-scheme: light dark`, meta `color-scheme` / `supported-color-schemes`, and **`@media (prefers-color-scheme: dark)`** using dark palette tokens in **`frontend/mail-design-tokens.json`** (aligned with FlyonUI `bnotedark`). Apple Mail and many iOS clients follow this; Gmail and other webmail may keep a light canvas or apply their own rules.

---

## Troubleshooting

- **No mail arrives:** spam folder; confirm PHP sees variables (not only your SSH shell); provider blocking outbound SMTP; wrong port/encryption pair.
- **Reset email without a button / “administrator must set BNOTE_NEXT_GENERATION_PUBLIC_URL”:** set **`BNOTE_NEXT_GENERATION_PUBLIC_URL`** to the **live** HTTPS URL users open in the browser (no trailing slash).
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
4. For any **fan-out to contacts** (not one-off system mail), decide whether the message is **transactional** (activity on rehearsals, concerts, tasks, entity discussion). If yes, gate recipients with **`NextGenMailPolicy::contactAllowsTransactionalNotification()`** — see the next section.

---

## Transactional mail: who receives it (contact vs user)

Some Next Gen mail goes to **contacts** attached to events or tasks. Recipient rules are centralized in **`api/mail/NextGenMailPolicy.php`**:

| Linked `user` row | `user.isActive` | Mail sent? |
|-------------------|-----------------|------------|
| **None** (contact only, no login) | — | **Yes** (subject to SMTP, demo mode, valid address, and feature-specific rules). |
| Present | **0** (inactive / deactivated) | **No** — inactive accounts must not receive transactional mail. |
| Present | **1** (active) | **Only if** the user has email notifications enabled (`email_notification` / `userEmailNotificationOn()`). |

**Features that use this policy today:** rehearsal/concert participation invites (`EventParticipantNotifier`), task assignee create/update mail (`api/modules/tasks.php`), and comment/discussion notifications (`CommentDiscussionNotifier`).

---

## Event-info group mail (hard behavior)

For the rehearsal/concert "Event-Info senden" flow (`emailInfo*` actions):

- **Navigation/UI:** composer is module-internal and query-driven (`?emailInfo=1`).
- **Recipients:** selected participants + additional contacts + manual emails are deduped and sent as one group mail.
- **Delivery addressing (hard requirement):**
  - `From` = system sender address from `MAIL_FROM_ADDRESS` (`MailEnv::fromAddress()`).
  - `To` = same system sender address (sender is recipient for the group mail envelope).
  - all selected recipients are in `BCC`.
- **Preview:** compose preview intentionally mimics a mail client header (`From/To/BCC/Subject`) and must reflect the same addressing semantics as send.
- **Footer sender line:** includes sender display name (`mail.footer.sentBy`), but no sender email in the footer copy.

---

## Generic composer mail (module 7 / Kommunikation)

For the generic email module (`api/modules/email.php`):

- **Permission model:** module id `7` (`Kommunikation`) is required (admin/superuser bypass remains).
- **Recipients:** selected groups are expanded via `contact_group`; explicit contact picks + manual emails are merged and deduped.
- **Subject handling:** prefix is fixed to `Band - BNote` (or `BNote` fallback). User edits only the trailing subject part.
- **Headline in mail body:** uses the user-written subject part (fallback to generic headline key if empty).
- **Footer copy:** keeps the existing sender/system line and appends sender display name (without email address).
- **Editor behavior for mail compose:** checklist mode is disabled in mail editors; empty EditorJS payloads (`{"blocks":[]...}`) are normalized to empty text instead of being shown/saved literally.

**Important:** `Systemdata::contactEmailNotificationOn()` returns **false** when the contact has **no** user. For transactional fan-out in Next Gen, use **`NextGenMailPolicy::contactAllowsTransactionalNotification()`** (or **`contactTransactionalMailDenyReason()`** for machine-readable skip reasons, e.g. `mail_comment_recipients.php` / `CommentDiscussionNotifier::describeRecipients`).

**Not gated by this policy:** password reset, self-registration / activation links, admin notices for new registrations, user activation email from admin — those are **system mail** and ignore the contact/user rules above.

---

## Comment / discussion notification (Next Gen API only)

When a user posts a comment via the **Next Gen** `comments` module (`api/index.php?module=comments&action=add`), **`CommentDiscussionNotifier`** sends one transactional mail per eligible recipient (personalized greeting, optional **entity header card** aligned with the app detail view—icon, title, type pill, date/time, location—then chat-style bubbles and **Open discussion**).

- **Entity context** is loaded in **`CommentDiscussionEntitySummary`** from `ProbenData` / `KonzerteData` / `StartData` (no extra API).
- **Deep links** use **`BNOTE_NEXT_GENERATION_PUBLIC_URL`** + `/entity?type=rehearsal|concert|vote&id=…&focus=comments` (same routing as the SPA entity page). Vote detail uses `type=vote`, not `/votes?id=`.
- Comment notification mail is handled entirely inside Next Gen by `CommentDiscussionNotifier`.
- Strings: `mail.commentDiscussion.*` and `mail.shell.headlineCommentDiscussion` in **`lang/*.json`**.

In the app, `?focus=comments` scrolls the discussion block into view on entity pages that use **`EntityChatLayout`**.

---

## Password reset (database)

The **`password_reset_token`** table is created automatically on the first password-reset request or completion (`PasswordResetSchema`), with the same DDL as **`api/migrations/password_reset_token.sql`** (FK first, fallback without FK if the server rejects it). You may run that SQL manually for controlled migrations.
