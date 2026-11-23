# Project status

Last updated: 23 November 2026. Product name: Second Half (chosen by Timasi; trademark and domain not yet checked).

## Implemented and tested

Automated: 174 back-end tests (run on SQLite and on PostgreSQL 16) and 14 front-end unit tests.
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
| Draft privacy notice and terms | n/a | yes |

## Not verified

- Embedded YouTube/Vimeo players actually playing (this build environment cannot reach those sites; the frame is created with the right address).
- Hudl embeds: not built. Hudl links show as plain links because no embed address has been verified.
- Real email delivery (only the log mailer has been used).
- Migrations on MariaDB (CI job written, not yet run).
- Real phones and Safari; screen readers. Colour pairs were chosen for AA contrast but no audit tool has been run.
- The CI workflow itself (it has never run, because the code is not on GitHub yet).

## Planned

- Verified links to official rules pages (none are linked yet, by design)
- Reorder teams, videos and honours by hand
- Email change confirmation that does not reveal other accounts
- Static analysis in CI, accessibility audit
- Later versions: other sports, high-school recruits with parent consent, coach accounts

## Blocked on Timasi

- Whether the coach contact form should stay in version one (possible rules concern for coaches)
- Review of privacy notice and terms wording; a contact email address
- Hosting and mail provider (nothing is deployed)
