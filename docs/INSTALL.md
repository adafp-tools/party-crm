# Party CRM — Installation Guide

This guide is for a party technician with basic Linux skills. It covers setting up a fresh VPS, installing Party CRM, and running your first login.

---

## What you need before you start

| Requirement | Minimum |
|---|---|
| VPS / server | Ubuntu 24.04 LTS, 2 CPU, 4 GB RAM, 40 GB SSD |
| Domain name | Pointed to your server's IP (A record) |
| SSH access | As root or a sudo user |
| Email address | For the first admin account |

---

## Step 1 — Run the server setup script

Download and run `install.sh` on your fresh server:

```bash
curl -fsSL https://releases.example.com/install.sh -o install.sh
# VERIFY the checksum before running:
sha256sum install.sh   # compare with the value published at releases.example.com
bash install.sh your-domain.org
```

The script installs and configures Nginx, PHP 8.3, MariaDB 11, Redis, Supervisor, and certbot. It applies security hardening (UFW firewall, fail2ban) and unpacks the Party CRM release archive. It is **idempotent** — safe to re-run.

When it finishes you will see:

```
✓ Party CRM installed. Visit https://your-domain.org/install to complete setup.
```

---

## Step 2 — Complete the web installer

Open `https://your-domain.org/install` in your browser. You will be guided through 5 steps:

1. **Requirements check** — the installer verifies PHP version, extensions, writable folders, and APP_KEY.
2. **Database** — enter the database credentials created by `install.sh`.
3. **Party settings** — full party name, abbreviation, membership number prefix, currency, timezone, and languages.
4. **Admin account** — the first national_admin user. Use a strong password (minimum 12 characters).
5. **Done** — the installer locks itself permanently.

> **Important:** After step 4 the installer shows APP_KEY and BLIND_INDEX_KEY.
> Copy both to a secure password manager immediately.
> Losing BLIND_INDEX_KEY means member search will stop working and cannot be recovered.

---

## Step 3 — First login

1. Go to `https://your-domain.org/login`.
2. Sign in with the admin email and password you created.
3. Set up **two-factor authentication** (2FA) immediately — the app will prompt you on the first login.
4. Navigate to **Settings** to upload the party logo and configure additional settings.

---

## Step 4 — Add more users

Go to **Users → Add user**. Assign roles:

| Role | Who gets it |
|---|---|
| `national_leadership` | Party president, secretary-general |
| `treasurer` | Finance team |
| `communications_officer` | Communications team |
| `regional_coordinator` | Regional heads |
| `branch_secretary` | Branch secretaries |

---

## Step 5 — Backups

The server setup script configures a nightly backup to `/home/deploy/backups/`. You must also copy these backups to a **second location** (a separate server or encrypted external storage) in the jurisdiction your party has approved for data storage.

Test the restore procedure before going live — see `docs/DEPLOY.md` for the restore steps.

---

## Updates

When ADAFP publishes a new version:

1. Download the release archive from the releases page.
2. Verify the SHA-256 checksum.
3. Run: `php artisan party:update /path/to/party-crm-X.Y.Z.zip`

The update command verifies the checksum, backs up the database, applies the new code, runs migrations, and rolls back automatically if anything fails.

---

## Troubleshooting

| Problem | Solution |
|---|---|
| White screen after install | Run `php artisan config:clear && php artisan cache:clear` |
| Login not working | Check `.env` has `SESSION_DRIVER=file` and `storage/` is writable |
| Emails not sending | Check `MAIL_*` settings in `.env` |
| Search not finding members | Verify `BLIND_INDEX_KEY` matches the value from installation |
| Queue jobs not running | Check `supervisorctl status party-crm-worker` |

For further help, contact ADAFP support.
