# Security

This file describes what protects athletes' data, how each control was checked, and what is still
a risk. Nothing here is a claim that the app is "secure" or ready for production: it has not had
an independent review and has never been deployed.

To report a problem, contact Timasi Werema (a dedicated address will be added before launch).

## What we are protecting, and from whom

The main harm: **an athlete's current coach or school finds out they are considering a transfer
before the athlete has told them.** Other harms: personal data leaking, someone taking over an
account, an athlete being spammed or harassed through the contact form, a reference's contact
details spreading, and minors signing up.

| Threat | What stands in the way | How it was checked |
| --- | --- | --- |
| Someone finds a profile without being given the link | No public listing or search. Profiles have no guessable address: the link holds a 43-character random token (about 256 bits). Only its SHA-256 hash is used for lookup. `noindex` header and meta, `robots.txt` disallows everything. | `SharingTest` |
| A link is forwarded or leaks | The athlete can switch it off or replace it; the old one returns 404 immediately. `Referrer-Policy: same-origin` and `rel="noreferrer"` keep the address away from other sites. Pages are sent `no-store`. | `SharingTest`, `SecurityHeadersTest`, browser run |
| Someone checks whether an athlete has an account | Sign-up gives the same answer for new and existing emails. Log-in and password reset give generic answers. | `RegistrationTest` |
| The coach page shows more than intended | One presenter builds the coach data field by field; hidden sections are left out on the server. Account email is never included. GPA, references and portal date are off by default. | `SharingTest` (every section, plus a search of the whole payload for the email) |
| One athlete reads or changes another's data | Every query starts from the signed-in athlete's own profile; no profile ID is ever taken from the request. Another athlete's item is "not found". | `AccessControlTest` |
| Account takeover | Hashed passwords (min 10 characters; checked against known breaches in production), email verification, optional two-step login (TOTP, no code reuse, single-use recovery codes), rate limits and lockout, session ID renewed on log-in. | `AuthenticationTest`, `TwoFactorTest`, browser run |
| Script injection (XSS) | React escapes all output; no raw HTML is rendered. Content-Security-Policy with a per-request nonce, no `unsafe-inline` or `unsafe-eval` for scripts. Video players only from YouTube (no-cookie) and Vimeo, built from a validated ID, in a sandboxed frame. | `SecurityHeadersTest`, `VideoEmbedTest`; browser run with zero CSP violations |
| Malicious upload | Images only (JPEG, PNG, WebP), checked by content, size and pixel limits, then decoded and re-encoded to a new JPEG (drops EXIF/GPS and any payload). Random file name on a private disk, served only through the app with `nosniff`. | `PhotoTest` |
| Spam or abuse through the contact form | 5 messages per hour per IP, 30 per day per link, hidden bot trap, minimum fill time with an encrypted timestamp. | `ContactTest` |
| Cross-site request forgery, clickjacking | Laravel CSRF tokens on every form; `frame-ancestors 'none'` and `X-Frame-Options: DENY`. | Framework default; headers tested |
| SQL injection | Eloquent and the query builder only; no user input in raw SQL. | Code review |
| Data at rest | Encrypted in the database: two-step secrets and recovery codes, share tokens, reference contact details, coach emails and message text. | `TwoFactorTest`, `ContactTest`, `SharingTest` |
| Under-18 sign-ups | Date of birth checked at sign-up and not stored; only the confirmation time is kept. | `RegistrationTest`, browser run |

Rate limits are defined in `app/Providers/AppServiceProvider.php`.

## Remaining risks (known, not fixed)

1. **Anyone with the link can open it.** That is how link sharing works. The page tells athletes so.
2. **Age is self-declared.** A person can lie about their birth date.
3. **Coaches are not verified.** Anyone with the link can send a message and claim to be a coach. The email and inbox both warn the athlete.
4. **Encryption depends on `APP_KEY`.** Someone with both the database and the key can read everything. Athlete profile fields themselves (school, stats, statement) are not encrypted.
5. **Email change links work only while logged in** and for 60 minutes. A new address takes effect only after the link sent to it is opened; the screen gives the same answer whether or not the address is in use, and the old address is told afterwards (`AccountUpdateTest`).
6. **`style-src-attr 'unsafe-inline'`** is allowed so components can set inline style attributes. Scripts remain strict.
7. **View counting is approximate.** Bots that look like browsers are counted; two people behind one network with the same browser count once.
8. **YouTube and Vimeo players** load from those companies and can set their own cookies. No consent prompt exists yet; decide before launch.
9. **Mail is sent during the request.** If the mail provider is slow, the coach waits. The message is saved first, so it is never lost.
10. **Developer-only tooling** (ESLint chain) has open `npm audit` advisories. Nothing vulnerable ships to users (`npm audit --omit=dev` is clean as of 23 Nov 2026).
11. **Not yet done:** testing with real assistive technology (an automated axe audit passes), load testing, an independent security review.

## Before production (checklist)

- [ ] HTTPS only; `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`
- [ ] Web server serves only `public/`; `.env` and `storage/` are not reachable
- [ ] Database user limited to this database; nightly encrypted backups; a tested restore
- [ ] Real mail provider with SPF, DKIM and DMARC
- [ ] PHP `upload_max_filesize` and `post_max_size` at least 8M
- [ ] A CDN or WAF in front for DDoS and bot filtering; trusted proxies configured so rate limits see real IPs
- [ ] Error monitoring and uptime alerts
- [ ] Privacy notice and terms reviewed; contact address added
- [ ] Decide on a consent prompt for embedded video

## Keeping it patched

CI runs `composer audit`, `npm audit`, a secret scan and Larastan static analysis (level 5) on every push. Check for framework and
dependency updates monthly, and straight away for security advisories affecting Laravel, Inertia or
React.
