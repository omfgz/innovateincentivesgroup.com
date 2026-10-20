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
| `website/.htaccess` | HTTPS redirect and rewrites |

To add a page, create `website/<path>/index.html` and it will be served at `/<path>/`.

**Deleting a file from `website/` deletes it from the live site** on the next deploy.

## Deploy secrets

Set in **Settings → Secrets and variables → Actions**: `SG_SSH_HOST`, `SG_SSH_PORT`, `SG_SSH_USER`, `SG_SSH_KEY` (private key, no passphrase), `SG_KNOWN_HOSTS` (pinned server host key).

The old WordPress site is kept at https://staging2.innovateincentivesgroup.com/.
