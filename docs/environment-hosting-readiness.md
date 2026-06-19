# Environment and Hosting Readiness Report

Task: A1.4 Environment and hosting readiness check

Status: Complete for A1.5 local scaffold; Composer fixed for project use; MySQL confirmed; production hosting deferred for local scaffold only

Date checked: 2026-06-17

## Scope

This document records local and target-hosting readiness before the real Laravel backend and Astro frontend scaffold is created.

It does not scaffold Laravel, scaffold Astro, install packages, create migrations, configure Filament, or deploy anything.

## Official References Checked

- Laravel 13 deployment requirements, rechecked 2026-06-17: https://laravel.com/docs/13.x/deployment
- Laravel 13 installation docs, rechecked 2026-06-17: https://laravel.com/docs/13.x/installation
- Astro installation requirements, rechecked 2026-06-17: https://docs.astro.build/en/install-and-setup/

## Readiness Status

Current status: **A1.4 readiness is complete for A1.5 local scaffold. A1.5 scaffold package commands can run only with approved escalation/outside the non-escalated Codex sandbox.**

The local machine has usable PHP, Composer, Git, Node, and npm binaries. PHP MySQL-related extensions and upload limits are fixed. Composer and npm registry connectivity work when the bad proxy variables are removed for the command. A normal PowerShell terminal can run `npm ping` and `npm view astro version` successfully, and an approved escalated Codex run of `npm view astro version` returned Astro `6.4.7`. However, npm package metadata/cache operations still fail on file unlink operations inside the non-escalated Codex sandbox, and a direct write/read/delete test confirmed that this session can create and read a workspace file but cannot delete it without escalation.

Global Composer remains `2.9.5` because Windows denies non-admin writes to `C:\ProgramData\ComposerSetup\bin\composer.phar`, but a workspace-local secure Composer PHAR now exists at `tools/composer/composer.phar`. `php tools\composer\composer.phar diagnose` reports Composer `2.10.1`, current version OK, vulnerability audit OK, Packagist OK, and GitHub rate-limit OK. A1.5 should use `php tools\composer\composer.phar` unless global Composer is later updated from an elevated/admin shell.

Local MySQL is ready for scaffold work. MySQL Server `8.0.46` is installed under `C:\Program Files\MySQL\MySQL Server 8.0`, service `MySQL80` is running, `mysqld` is listening on `127.0.0.1:3306`, and MySQL Workbench has an active local connection. Do not store the local password in docs; use `.env` locally and `.env.example` placeholders in the scaffold.

Production/shared-hosting readiness is explicitly deferred for A1.5 local scaffold only. This does not approve shared cPanel for deployment; the hosting checklist must still pass before deployment planning or production use.

## Local Checks

| Area | Result | Evidence | Status |
|---|---|---|---|
| Workspace app scaffold | `apps/backend` and `apps/frontend` do not exist | `Test-Path` returned false for `apps`, `apps/backend`, and `apps/frontend` | Expected |
| PHP version | PHP `8.5.5` | `php -v` | Pass for Laravel 13 minimum |
| PHP config file | `C:\php-8.5.5\php.ini` | `php -i` | Pass |
| Laravel required PHP extensions | Core Laravel extensions are loaded | `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `PDO`, `session`, `tokenizer`, `xml` present in `php -m` | Pass |
| PHP MySQL driver | Loaded | `pdo_mysql` and `mysqli` present in `php -m` | Pass |
| Useful PHP extensions | Loaded | `gd`, `intl`, `sodium`, and `zip` present in `php -m` | Pass |
| Upload size | `upload_max_filesize = 5M`; `post_max_size = 10M` | `php -i` and `php.ini` | Pass for V1 upload rule |
| PHP memory | `memory_limit = 256M` | `php -i` | Pass for local scaffold work |
| Composer for A1.5 | Workspace-local Composer `2.10.1` available | `php tools\composer\composer.phar --version` | Pass |
| Composer connectivity/security | Workspace-local Composer reaches Packagist/GitHub and has no reported Composer advisories | `php tools\composer\composer.phar diagnose` reports version OK, audit OK, Packagist OK, GitHub OK | Pass |
| Global Composer | Global Composer remains `2.9.5` | `composer --version`; `composer self-update` downloaded `2.10.1` but Windows denied writing `C:\ProgramData\ComposerSetup\bin\composer.phar` | Non-blocking if A1.5 uses workspace Composer PHAR |
| Git | Git `2.50.1` installed | `git --version` | Pass |
| Node | Node `v22.18.0` installed | `node -v` | Pass for Astro `v22.12.0+` requirement |
| npm | npm `11.10.1` installed | `npm -v` | Installed |
| npm connectivity | Registry ping works when bad proxy variables are removed and offline is overridden | `npm ping` returned PONG after command-level env fix | Partial pass |
| npm package metadata/cache | Works in normal PowerShell and approved escalated Codex; still fails inside non-escalated Codex because npm cannot delete cache temp files from this sandbox | User terminal screenshot shows `npm view astro version` returned `6.4.7`; approved escalated Codex run returned `6.4.7`; non-escalated Codex rerun failed with `EPERM` on `unlink` | Package work requires approved escalation/outside sandbox |
| npm proxy config | npm explicit proxy unset, but process proxy blocks traffic | `npm config get proxy` and `https-proxy` returned `null`; env proxy points to `127.0.0.1:9` | Blocker |
| Process proxy environment | Proxy points to closed local port and npm defaults to offline mode | `cmd /c set` shows `HTTP_PROXY`, `HTTPS_PROXY`, `ALL_PROXY`, `GIT_HTTP_PROXY`, and `GIT_HTTPS_PROXY` as `http://127.0.0.1:9`; `NPM_CONFIG_OFFLINE=true` | Blocker |
| MySQL client/server binaries | MySQL Server `8.0.46` binaries installed | `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe --version`; `mysqld.exe --version` | Pass |
| MySQL local service | Service `MySQL80` running | `Get-Service` shows `MySQL80` status `Running`; `mysqld` process active | Pass |
| MySQL local port | `127.0.0.1:3306` reachable | `Test-NetConnection 127.0.0.1 -Port 3306` returned `TcpTestSucceeded=True`; `netstat` shows `3306` listening | Pass |
| MySQL development connection | Local connection confirmed without storing secrets | User-provided Workbench screenshot shows `Local instance MySQL80` connected | Pass for A1.5 scaffold |
| Local filesystem write/read/delete | Create and read works; delete fails inside sandbox | `Set-Content` and `Get-Content` succeeded for a temp workspace file; `Remove-Item` returned access denied until escalated cleanup | Blocker for scaffold/package commands in current sandbox |
| Windows ACL for PHP/cache paths | User has Modify/FullControl where checked | `Get-Acl` shows `CSS-NANDA\user` access for `php.ini`, npm cache, and Composer cache | OS permission appears fixed, but sandbox delete is still blocked |

## Remaining Constraints For A1.5

1. **Package commands need controlled environment overrides or approved escalation.**
   Composer and npm can fail while traffic is routed to `127.0.0.1:9` or npm sees `NPM_CONFIG_OFFLINE=true`. Command-level overrides and approved escalation work; use them during A1.5 package/scaffold commands.

2. **File delete/unlink operations are blocked inside the current Codex sandbox.**
   A direct temp-file test can create and read a file in the workspace, but `Remove-Item` receives access denied. This matches npm's `EPERM` failure while deleting cache temp files.

3. **npm package metadata/cache operations are blocked inside the non-escalated Codex sandbox.**
   `npm ping` works with command-level environment overrides, and `npm view astro version` works with approved escalation/outside the sandbox. Non-escalated Codex still fails because npm cannot delete cache temp files, so A1.5 package/scaffold commands must use approved escalation unless the sandbox unlink/delete issue is fixed.

4. **Database credentials must stay local.**
   MySQL is running and reachable for A1.5, but real `DB_USERNAME` and `DB_PASSWORD` must not be stored in docs or committed files. Scaffold `.env.example` should use placeholders only.

5. **Production hosting is deferred, not approved.**
   Production/shared cPanel details are not available. It is acceptable to defer production hosting only for A1.5 local scaffold work; shared cPanel cannot be approved until PHP version, extensions, Composer/deploy workflow, MySQL/MariaDB version, cron, queue feasibility, private storage path, webhook access, SSL, backup, and rollback options are confirmed.

## Non-Blocking Risks

1. **This folder is not currently a Git repository.**
   The project plan recommends one Git repository. Initialize Git before or during A1.5 if this workspace is intended to become the real repo root.

## Repair Attempt Notes

Checked after the first readiness report:

- Composer can reach Packagist/GitHub when `HTTP_PROXY`, `HTTPS_PROXY`, `ALL_PROXY`, `GIT_HTTP_PROXY`, and `GIT_HTTPS_PROXY` are removed for the command.
- npm can ping the registry when the bad proxy variables are removed and `NPM_CONFIG_OFFLINE=false` is set for the command.
- PHP now loads `pdo_mysql`, `mysqli`, `intl`, and `sodium` from the default config.
- PHP upload settings now show `upload_max_filesize = 5M` and `post_max_size = 10M`.
- PHP memory now shows `memory_limit = 256M`.
- Windows ACLs now show `CSS-NANDA\user` has Modify access to `C:\php-8.5.5\php.ini` and FullControl/Modify access to npm and Composer cache paths.
- npm package metadata still fails because cache file create/delete operations are blocked inside this Codex sandbox, including with a fresh cache under the workspace.
- Composer package lookup reaches Packagist, but Composer also reports its cache directory as not writable from this sandbox.
- On 2026-06-17, a direct workspace temp-file check confirmed the delete problem: file creation and readback succeeded, but delete required escalated cleanup.
- On 2026-06-17, Composer connectivity still worked after proxy variables were removed for the command, but Composer itself reported security advisories.
- On 2026-06-17, the user's normal PowerShell terminal successfully ran `npm ping` and `npm view astro version`, returning Astro `6.4.7`; the same package metadata check still failed inside Codex with `EPERM` on `unlink`.
- On 2026-06-17, an approved escalated Codex run of `npm view astro version --registry=https://registry.npmjs.org/ --cache .npm-cache` also returned Astro `6.4.7`.
- On 2026-06-17, global `composer self-update` could download Composer `2.10.1` but could not write to `C:\ProgramData\ComposerSetup\bin\composer.phar` due to Windows permissions.
- On 2026-06-17, `tools/composer/composer.phar` was downloaded into the workspace. `php tools\composer\composer.phar diagnose` passed version, audit, Packagist, GitHub, platform, git, and disk checks.
- On 2026-06-17, `C:\laragon` was found with MySQL 8.4 data/config, but `mysql.exe` and `mysqld.exe` were missing and no local MySQL service/process/port was active.
- On 2026-06-17, MySQL Server `8.0.46` was confirmed installed at `C:\Program Files\MySQL\MySQL Server 8.0`, service `MySQL80` was running, `127.0.0.1:3306` was reachable, and Workbench showed an active `Local instance MySQL80` connection.

## Recommended Local Fixes Before A1.5

1. Fix shell/package network:
   - Remove or override `HTTP_PROXY`, `HTTPS_PROXY`, and `ALL_PROXY` values pointing to `http://127.0.0.1:9`.
   - Remove or override `GIT_HTTP_PROXY` and `GIT_HTTPS_PROXY` values pointing to `http://127.0.0.1:9`.
   - Set npm offline mode to false.
   - Re-run `composer diagnose`.
   - Re-run `npm ping`.
   - Re-run a real metadata/install check such as `npm view astro version`.

2. Fix filesystem delete/unlink behavior in the execution environment:
   - Confirm a normal shell can create, read, and delete files in this workspace without escalation.
   - Re-run `npm view astro version --cache .npm-cache`.
   - If Codex sandbox continues blocking delete/unlink operations, run scaffold/package commands outside this sandbox or adjust Codex workspace permissions.

3. Use the workspace-local Composer for A1.5:
   - Run Composer commands as `php tools\composer\composer.phar ...`.
   - Optional: update global Composer later from an elevated/admin shell if you want `composer` on PATH to be current.

4. Use local MySQL for A1.5:
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=3306`
   - Use a local development database name such as `okina_craft_local`.
   - Keep actual `DB_USERNAME` and `DB_PASSWORD` only in untracked `.env`; use placeholders in `.env.example`.

5. Decide Git root:
   - Initialize Git in this workspace if this is the final repository root.

## Target Hosting Checklist

Before approving shared cPanel for Phase 1, confirm these from the host:

| Requirement | Must Confirm |
|---|---|
| PHP version | PHP `8.3+` available for the backend domain/subdomain |
| PHP extensions | Laravel required extensions plus `pdo_mysql`, `fileinfo`, `gd`, `zip`, `intl`, and `sodium` where available |
| Composer | Composer can run on hosting, or deployment can upload a built vendor folder through a documented process |
| MySQL/MariaDB | Version and credentials are available; migrations can run safely |
| Web root | Laravel public root can point to `apps/backend/public` or equivalent without exposing project root |
| Private storage | Uploaded originals can live outside public web root |
| Permissions | `storage` and `bootstrap/cache` are writable by the web process |
| Cron | Laravel scheduler can run every minute or on an accepted interval |
| Queues | A queue worker or safe cron-driven queue fallback is available |
| Webhooks | Cashfree webhook URL can reach the backend over HTTPS |
| SSL | SSL available for `okinacraft.com` and admin/backend subdomain |
| Upload limits | Host permits at least 5 MB uploads, plus safe overhead |
| Backups | Database and private upload backup method is known |
| Rollback | Files, database, and environment rollback path is documented |

## Hosting Recommendation

Do not approve shared cPanel yet.

Shared cPanel remains possible only if it passes the checklist above, especially PHP 8.3+, Composer/deploy workflow, MySQL/MariaDB, private storage outside public root, cron, webhook HTTPS access, and a realistic queue strategy.

If cPanel cannot support queue workers, reliable cron, private file storage, and webhook handling, use VPS for the backend from the beginning. Astro can still be built as a static frontend and deployed separately.

## A1.5 Entry Gate

Move to A1.5 only after:

- Composer can reach Packagist/GitHub.
- Composer is updated to a secure current stable version, or A1.5 uses `php tools\composer\composer.phar`.
- npm can reach the npm registry, is no longer offline, and can complete package metadata/cache operations.
- The intended execution environment can create, read, and delete workspace files without escalation, or A1.5 package/scaffold commands are explicitly run with approved escalation/outside the non-escalated Codex sandbox.
- Default proxy/offline environment values no longer break package tools.
- PHP has `pdo_mysql` enabled by default.
- Upload limits are set for 5 MB uploads.
- MySQL/MariaDB local or remote development database path is confirmed.
- Production hosting path is approved, or explicitly deferred for A1.5 local scaffold only.

## Sequence 10 Result

Readiness status: **Complete for A1.5 local scaffold.**

Rechecked on 2026-06-17. Composer is fixed for project use through `tools/composer/composer.phar`, npm package metadata works with approved escalation, MySQL Server `8.0.46` is running on `127.0.0.1:3306`, and production hosting is deferred for A1.5 local scaffold only. A1.4 can be marked complete and `CURRENT-TASK.md` can move to A1.5.
