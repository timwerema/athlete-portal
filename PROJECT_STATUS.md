# Project status

Last updated: 25 November 2026. Product name: Second Half (chosen by Timasi; trademark and domain not yet checked).

## Implemented and tested

Automated: 187 back-end tests (run on SQLite and on PostgreSQL 16) and 14 front-end unit tests.
CI on GitHub passes: tests, audits, secret scan, static analysis (Larastan level 5), and migrations on MariaDB 11 and PostgreSQL 16.
Browser: a scripted walk through the real app in Chromium at 390 and 1440 wide, dark and light.

| Feature | Automated tests | Checked in browser |
| --- | --- | --- |
| Sign-up with 18+ check (birth date not stored), under-18 refusal | yes | yes |
| Email verification, log-in, log-out, password reset, rate limits | yes | verification and log-in |
| Two-step login (authenticator app, recovery codes) | yes | set-up and log-in |
| Profile builder: name, pronouns, positions, foot, height, school, division, conference, class year, eligibility, history, season stats (with goalkeeper stats), honours, academics, video links, references, looking for, statement | yes | yes |
| Photo upload (re-encoded, metadata stripped, private) | yes | yes |
| Private by default; secret link create, replace, switch off | yes | yes |
| Per-section visibility; GPA, references and portal date hidden by default | yes | yes |
| Coach page (phone, desktop, print to 2 pages) | yes | yes |
| Coach contact form, athlete inbox, email to athlete | yes | yes (log mailer) |
| Honest view counts (own visits, bots, previews excluded) | yes | yes |
| Completeness checklist | yes | yes |
| Download my data, delete my account | yes | yes |
| Security headers and nonce-based CSP | yes | no violations seen |
| One labelled sample profile at /demo (seeder) | yes | yes |
| Reorder teams, honours, videos and references by hand | yes | yes |
| Email change confirmed by a link to the new address, password required, old address notified | yes | yes (log mailer) |
| Official sources list (NCAA transfer rules page, NAIA Eligibility Center) on Overview and Sharing. Links opened and confirmed by Timasi on 25 Nov 2026; the build environment could not reach them | host check only | yes |
| Accessibility: automated axe audit, 144 page checks (every page, dark and light, 390 and 1440 wide), no violations | n/a | yes |
| Draft privacy notice and terms | n/a | yes |

## Not verified

- Embedded YouTube/Vimeo players actually playing (this build environment cannot reach those sites; the frame is created with the right address).
- Hudl embeds: not built. Hudl links show as plain links because no embed address has been verified.
- Real email delivery (only the log mailer has been used).
- Real phones and Safari; real screen readers (the automated audit cannot judge everything).

## Planned

- Official link for the NJCAA, and the NCAA "Want to Transfer?" page: both failed to load when Timasi checked on 25 Nov 2026, so they are not linked
- Testing with a real screen reader and on real phones
- Later versions: other sports, high-school recruits with parent consent, coach accounts

## Decided by Timasi

- Name: Second Half
- Palette: purple, grey, black and white
- Coach contact form stays in version one as built (only people the athlete sent the link to can use it; coaches are told they are responsible for their own contact rules)

## Blocked on Timasi

- Review of privacy notice and terms wording; a contact email address
- Hosting and mail provider (nothing is deployed)
