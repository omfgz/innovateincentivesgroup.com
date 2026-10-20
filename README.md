# innovateincentivesgroup.com

Static HTML site for [innovateincentivesgroup.com](https://innovateincentivesgroup.com/), hosted on SiteGround.

## How to make changes

1. Edit files in `website/` (on a branch, ideally, and open a pull request).
2. Merge into `main`.
3. GitHub Actions deploys automatically: it uploads `website/` to the server, flushes the SiteGround cache, then checks that every page returns 200. Watch progress in the **Actions** tab.

You can also redeploy at any time from **Actions → Deploy to SiteGround → Run workflow**.

## Layout

`website/` mirrors the live site exactly:

| File | URL |
| --- | --- |
| `website/index.html` | `/` and `/services/` (same page, via `.htaccess`) |
| `website/services/<slug>/index.html` | `/services/<slug>/` |
| `website/why-iig/`, `faqs/`, `privacy-policy/`, `sred-calculator/` | Company and information pages |
| `website/api/sred-lead.php` | SR&ED calculator lead capture (PHP) |
| `website/assets/email/` | Images used in outgoing emails |
| `website/.htaccess` | HTTPS redirect and rewrites |

Each page is self-contained (its own CSS and embedded logo). The shared header, footer and contact form are repeated in every page, so a change to them must be made in all pages.

## SR&ED calculator leads

The calculator at `/sred-calculator/` runs entirely in the browser. When a visitor gets an estimate, `api/sred-lead.php`:

- emails the lead to `info@innovateincentivesgroup.com`,
- sends the visitor a thank-you email from `noreply@innovateincentivesgroup.com`,
- appends the lead to `~/www/innovateincentivesgroup.com/private/sred-leads/leads.csv` on the server (outside the web root; deploys never touch it).

Email addresses and the thank-you template are set at the top of `api/sred-lead.php`. Leads captured before the move are in the WordPress database on staging2 (WP admin → SR&ED Leads).

To add a page, create `website/<path>/index.html` and it will be served at `/<path>/`.

**Deleting a file from `website/` deletes it from the live site** on the next deploy.

## Deploy secrets

Set in **Settings → Secrets and variables → Actions**: `SG_SSH_HOST`, `SG_SSH_PORT`, `SG_SSH_USER`, `SG_SSH_KEY` (private key, no passphrase), `SG_KNOWN_HOSTS` (pinned server host key).

The old WordPress site is kept at https://staging2.innovateincentivesgroup.com/.
