import Field from '@/components/field';
import InputError from '@/components/input-error';
import { Button, buttonVariants } from '@/components/ui/button';
import { Input, Textarea } from '@/components/ui/input';
import Wordmark from '@/components/wordmark';
import { formatDate, formatHeight, formatStat, formatYears, initials } from '@/lib/format';
import { CoachProfile, SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, LoaderCircle, Printer, Send } from 'lucide-react';
import { FormEventHandler, ReactNode } from 'react';

interface ShowProps {
    profile: CoachProfile;
    mode: 'coach' | 'owner' | 'preview' | 'demo';
    contact: { action: string; started: string } | null;
}

const TEAM_KINDS: Record<string, string> = { college: 'College', club: 'Club', high_school: 'High school', national: 'National team' };

function Section({ number, title, children, id }: { number: string; title: string; children: ReactNode; id?: string }) {
    return (
        <section
            id={id}
            aria-labelledby={`${id ?? number}-title`}
            className="reveal grid gap-x-8 gap-y-4 border-t py-8 md:grid-cols-[11rem_1fr] print:py-5"
        >
            <h2 id={`${id ?? number}-title`} className="flex items-baseline gap-3 text-xl font-semibold md:flex-col md:gap-1">
                <span className="numeral text-primary text-sm font-normal">{number}</span>
                {title}
            </h2>
            <div className="min-w-0">{children}</div>
        </section>
    );
}

function ContactForm({ contact, name }: { contact: { action: string; started: string }; name: string }) {
    const { flash } = usePage<SharedData>().props;
    const form = useForm({ name: '', school: '', role: '', email: '', message: '', website: '', started: contact.started });
    const sent = flash?.status === 'contact-sent' && !form.isDirty;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(contact.action, { preserveScroll: true, onSuccess: () => form.reset() });
    };

    if (sent) {
        return (
            <div role="status" className="border-primary border-l-2 py-1 pl-4">
                <p className="text-lg font-semibold">Message sent.</p>
                <p className="text-muted-foreground mt-1 leading-relaxed">
                    {name} has it in their inbox and by email. If they want to follow up, they will reply to the address you gave.
                </p>
            </div>
        );
    }

    return (
        <form onSubmit={submit} noValidate className="grid max-w-xl gap-5">
            <p className="text-muted-foreground leading-relaxed">
                Your message goes straight to {name}. Their contact details are not shown here; they will reply to your email if they choose to.
            </p>
            <div className="grid gap-5 sm:grid-cols-2">
                <Field label="Your name" htmlFor="contact_name" error={form.errors.name}>
                    <Input id="contact_name" autoComplete="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                </Field>
                <Field label="Your role" htmlFor="contact_role" error={form.errors.role}>
                    <Input
                        id="contact_role"
                        autoComplete="organization-title"
                        placeholder="e.g. Assistant coach"
                        value={form.data.role}
                        onChange={(e) => form.setData('role', e.target.value)}
                    />
                </Field>
            </div>
            <Field label="School or programme" htmlFor="contact_school" error={form.errors.school}>
                <Input
                    id="contact_school"
                    autoComplete="organization"
                    value={form.data.school}
                    onChange={(e) => form.setData('school', e.target.value)}
                />
            </Field>
            <Field label="Your email" htmlFor="contact_email" error={form.errors.email}>
                <Input
                    id="contact_email"
                    type="email"
                    inputMode="email"
                    autoComplete="email"
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                />
            </Field>
            <Field label="Message" htmlFor="contact_message" error={form.errors.message}>
                <Textarea
                    id="contact_message"
                    rows={6}
                    maxLength={3000}
                    value={form.data.message}
                    onChange={(e) => form.setData('message', e.target.value)}
                />
            </Field>

            {/* Bot trap: hidden from people and from screen readers. Real visitors leave it empty. */}
            <div className="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                <label htmlFor="contact_website">Leave this field empty</label>
                <input
                    id="contact_website"
                    name="website"
                    tabIndex={-1}
                    autoComplete="off"
                    value={form.data.website}
                    onChange={(e) => form.setData('website', e.target.value)}
                />
            </div>
            <InputError message={(form.errors as Record<string, string>).website} />

            <p className="text-muted-foreground text-sm leading-relaxed">
                You are responsible for following the recruiting and contact rules of your own institution and governing body. We store what you enter
                here so the athlete can read it, as described in our{' '}
                <Link href={route('privacy')} className="underline underline-offset-4">
                    privacy notice
                </Link>
                .
            </p>
            <Button type="submit" size="lg" className="justify-self-start" disabled={form.processing}>
                {form.processing ? <LoaderCircle className="animate-spin" aria-hidden="true" /> : <Send aria-hidden="true" />}
                Send message
            </Button>
        </form>
    );
}

export default function ShareShow({ profile, mode, contact }: ShowProps) {
    const { name: appName } = usePage<SharedData>().props;
    const name = profile.display_name ?? 'Athlete';
    const firstName = name.split(' ')[0];

    const facts: [string, ReactNode][] = (
        [
            ['Position', profile.positions.map((p) => p.label).join(', ') || null],
            ['Preferred foot', profile.preferred_foot],
            ['Height', formatHeight(profile.height_cm)],
            ['Class', profile.class_year],
            ['School', profile.current_school],
            ['Division', profile.division],
            ['Conference', profile.conference],
            [
                'Eligibility left',
                profile.eligibility_years_left !== null ? (
                    <>
                        {profile.eligibility_years_left} {profile.eligibility_years_left === 1 ? 'season' : 'seasons'}{' '}
                        <span className="text-muted-foreground text-sm">(self-reported)</span>
                    </>
                ) : null,
            ],
            [
                'Official portal',
                profile.portal_entered_on ? (
                    <>
                        Entered {formatDate(profile.portal_entered_on)} <span className="text-muted-foreground text-sm">(self-reported)</span>
                    </>
                ) : null,
            ],
            ['Major', profile.major],
            [
                'GPA',
                profile.gpa ? (
                    <>
                        <span className="numeral">{profile.gpa.value}</span>
                        {profile.gpa.scale && <> / {profile.gpa.scale}</>} <span className="text-muted-foreground text-sm">(self-reported)</span>
                    </>
                ) : null,
            ],
        ] as [string, ReactNode][]
    ).filter(([, value]) => value !== null && value !== undefined && value !== '');

    const sections: { key: string; title: string; show: boolean; body: () => ReactNode }[] = [
        {
            key: 'statement',
            title: 'In their words',
            show: !!profile.statement,
            body: () => <p className="max-w-2xl text-lg leading-relaxed whitespace-pre-line print:text-base">{profile.statement}</p>,
        },
        {
            key: 'stats',
            title: 'Season stats',
            show: profile.season_stats.length > 0,
            body: () => (
                <>
                    <div className="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                        <table className="w-full min-w-max border-collapse text-left">
                            <caption className="sr-only">Statistics by season</caption>
                            <thead>
                                <tr className="border-b">
                                    <th scope="col" className="kicker py-2 pr-4 font-normal">
                                        Season
                                    </th>
                                    {profile.stat_fields.map((f) => (
                                        <th key={f.key} scope="col" className="kicker px-2 py-2 text-right font-normal sm:px-3">
                                            <abbr title={f.label} className="no-underline">
                                                {f.short}
                                            </abbr>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {profile.season_stats.map((s, i) => (
                                    <tr key={i} className="border-b align-top">
                                        <th scope="row" className="max-w-40 py-3 pr-4 font-normal sm:max-w-none">
                                            <span className="numeral font-medium">{s.season}</span>
                                            <span className="text-muted-foreground block text-sm">
                                                {[s.team, s.competition].filter(Boolean).join(' · ')}
                                            </span>
                                        </th>
                                        {profile.stat_fields.map((f) => (
                                            <td key={f.key} className="numeral px-2 py-3 text-right text-lg sm:px-3 print:text-base">
                                                {formatStat(s.stats[f.key], f.key)}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <p className="text-muted-foreground mt-3 text-sm">
                        {profile.stat_fields.map((f) => `${f.short} = ${f.label.toLowerCase()}`).join(', ')}. A dash means not entered. Numbers are
                        entered by the athlete.
                    </p>
                </>
            ),
        },
        {
            key: 'videos',
            title: 'Video',
            show: profile.videos.length > 0,
            body: () => (
                <ul className="grid gap-8">
                    {profile.videos.map((v, i) => (
                        <li key={i}>
                            <p className="font-semibold">
                                {v.title}{' '}
                                <span className="text-muted-foreground font-normal">· {v.kind === 'full_match' ? 'Full match' : 'Highlights'}</span>
                            </p>
                            {v.embed_url && (
                                <div className="no-print bg-secondary mt-3 aspect-video max-w-2xl overflow-hidden rounded-md border">
                                    <iframe
                                        src={v.embed_url}
                                        title={`${v.title} (${v.label} video)`}
                                        loading="lazy"
                                        className="size-full"
                                        referrerPolicy="no-referrer"
                                        sandbox="allow-scripts allow-same-origin allow-presentation"
                                        allow="fullscreen; picture-in-picture; encrypted-media"
                                        allowFullScreen
                                    />
                                </div>
                            )}
                            <a
                                href={v.url}
                                target="_blank"
                                rel="noopener noreferrer nofollow"
                                className="mt-2 inline-flex min-h-11 items-center gap-2 break-all underline underline-offset-4"
                            >
                                <ExternalLink className="no-print size-4 shrink-0" aria-hidden="true" />
                                <span className="no-print">{v.embed_url ? `Open on ${v.label}` : `Watch on ${v.label}`}</span>
                                <span className="print-only numeral text-sm">{v.url}</span>
                            </a>
                        </li>
                    ))}
                </ul>
            ),
        },
        {
            key: 'history',
            title: 'Playing history',
            show: profile.teams.length > 0,
            body: () => (
                <ul>
                    {profile.teams.map((t, i) => (
                        <li key={i} className="grid grid-cols-[6.5rem_1fr] gap-x-4 border-b py-3 first:pt-0 sm:grid-cols-[8rem_1fr]">
                            <span className="numeral text-muted-foreground pt-0.5 text-sm">{formatYears(t.start_year, t.end_year)}</span>
                            <span>
                                <span className="font-semibold">{t.name}</span>
                                <span className="text-muted-foreground block text-sm">
                                    {[TEAM_KINDS[t.kind], t.detail].filter(Boolean).join(' · ')}
                                </span>
                            </span>
                        </li>
                    ))}
                </ul>
            ),
        },
        {
            key: 'honours',
            title: 'Honours',
            show: profile.honours.length > 0,
            body: () => (
                <ul>
                    {profile.honours.map((h, i) => (
                        <li key={i} className="grid grid-cols-[6.5rem_1fr] gap-x-4 border-b py-3 first:pt-0 sm:grid-cols-[8rem_1fr]">
                            <span className="numeral text-muted-foreground pt-0.5 text-sm">{h.year ?? ''}</span>
                            <span>{h.title}</span>
                        </li>
                    ))}
                </ul>
            ),
        },
        {
            key: 'looking',
            title: 'Looking for',
            show: !!profile.looking_for,
            body: () => (
                <dl>
                    {(
                        [
                            ['Level', profile.looking_for?.levels.join(', ')],
                            ['Region', profile.looking_for?.regions.join(', ')],
                            ['Intended major', profile.looking_for?.intended_major],
                            ['Timeline', profile.looking_for?.timeline],
                        ] as [string, string | null | undefined][]
                    )
                        .filter(([, value]) => value)
                        .map(([label, value]) => (
                            <div key={label} className="grid grid-cols-[6.5rem_1fr] gap-x-4 border-b py-3 first:pt-0 sm:grid-cols-[8rem_1fr]">
                                <dt className="kicker pt-1">{label}</dt>
                                <dd>{value}</dd>
                            </div>
                        ))}
                </dl>
            ),
        },
        {
            key: 'references',
            title: 'References',
            show: profile.references.length > 0,
            body: () => (
                <ul>
                    {profile.references.map((r, i) => (
                        <li key={i} className="border-b py-3 first:pt-0">
                            <span className="font-semibold">{r.name}</span>
                            <span className="text-muted-foreground block text-sm">{[r.role, r.organization].filter(Boolean).join(' · ')}</span>
                            <span className="block text-sm break-words">{r.contact}</span>
                        </li>
                    ))}
                </ul>
            ),
        },
    ].filter((s) => s.show);

    const banner: Record<ShowProps['mode'], ReactNode> = {
        coach: null,
        owner: (
            <>
                This is your shared page. You are logged in, so this visit is not counted.{' '}
                <Link href={route('sharing.index')} className="underline underline-offset-4">
                    Back to Sharing
                </Link>
            </>
        ),
        preview: (
            <>
                Preview: this is what a coach sees with your current settings. Only you can open this address.{' '}
                <Link href={route('sharing.index')} className="underline underline-offset-4">
                    Change what is shown
                </Link>
            </>
        ),
        demo: (
            <>
                <strong>Sample profile.</strong> {name} is a fictional athlete. The schools, clubs, stats and honours are made up to show what a
                profile looks like.
            </>
        ),
    };

    return (
        <div className="min-h-dvh">
            <Head title={mode === 'demo' ? 'Sample profile' : `${name} · ${profile.sport} profile`}>
                <meta name="robots" content="noindex, nofollow, noarchive" />
                <meta name="referrer" content="no-referrer" />
            </Head>

            {banner[mode] && (
                <div className="bg-mark text-mark-foreground no-print">
                    <p className="mx-auto max-w-5xl px-4 py-3 text-sm leading-relaxed sm:px-6" role="note">
                        {banner[mode]}
                    </p>
                </div>
            )}

            <header className="border-b">
                <div className="mx-auto flex min-h-14 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6">
                    {mode === 'preview' || mode === 'owner' ? (
                        <Link href={route('profile.index')} className="no-print inline-flex min-h-11 items-center gap-2 text-sm font-medium">
                            <ArrowLeft className="size-4" aria-hidden="true" />
                            Back to editing
                        </Link>
                    ) : mode === 'demo' ? (
                        <Link href={route('home')} aria-label="Home">
                            <Wordmark />
                        </Link>
                    ) : (
                        <Wordmark />
                    )}
                    <p className="kicker print-only">{profile.sport} athlete profile</p>
                    <div className="no-print flex items-center gap-2">
                        <Button variant="ghost" size="sm" onClick={() => window.print()}>
                            <Printer aria-hidden="true" />
                            Print
                        </Button>
                        {contact && (
                            <a href="#contact" className={buttonVariants({ size: 'sm' })}>
                                Message
                            </a>
                        )}
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-5xl px-4 sm:px-6">
                <div className="reveal grid gap-6 py-8 sm:py-12 md:grid-cols-[1fr_15rem] md:items-end print:grid-cols-[1fr_8rem] print:py-4">
                    <div>
                        <p className="kicker">
                            {profile.sport} · Athlete profile
                            {profile.updated_at && <> · Updated {formatDate(profile.updated_at)}</>}
                        </p>
                        <h1 className="mt-4 text-5xl leading-[0.95] font-semibold break-words sm:text-7xl print:text-4xl">{name}</h1>
                        {profile.pronouns && <p className="text-muted-foreground mt-3">{profile.pronouns}</p>}
                        {profile.positions.length > 0 && (
                            <p className="mt-6 flex flex-wrap gap-2" aria-label={`Positions: ${profile.positions.map((p) => p.label).join(', ')}`}>
                                {profile.positions.map((p, i) => (
                                    <span
                                        key={p.code}
                                        title={p.label}
                                        className={
                                            i === 0
                                                ? 'bg-mark text-mark-foreground numeral rounded-sm px-2.5 py-1 text-lg font-medium print:border'
                                                : 'numeral rounded-sm border px-2.5 py-1 text-lg'
                                        }
                                    >
                                        {p.code}
                                    </span>
                                ))}
                            </p>
                        )}
                    </div>
                    {profile.photo_url ? (
                        <img
                            src={profile.photo_url}
                            alt={`Photo of ${name}`}
                            className="bg-secondary aspect-[4/5] w-40 rounded-md border object-cover md:w-full print:w-32"
                            width={240}
                            height={300}
                        />
                    ) : (
                        <div
                            className="bg-secondary font-display text-muted-foreground no-print hidden aspect-[4/5] w-full items-center justify-center rounded-md border text-6xl font-semibold md:flex"
                            aria-hidden="true"
                        >
                            {initials(name)}
                        </div>
                    )}
                </div>

                {facts.length > 0 && (
                    <dl className="reveal grid border-t [--i:1] sm:grid-cols-2 sm:gap-x-10 print:grid-cols-2 print:gap-x-8">
                        {facts.map(([label, value]) => (
                            <div key={label} className="grid grid-cols-[8rem_1fr] gap-4 border-b py-3 print:py-1.5">
                                <dt className="kicker pt-1">{label}</dt>
                                <dd className="font-medium">{value}</dd>
                            </div>
                        ))}
                    </dl>
                )}

                <div className="mt-6">
                    {sections.map((s, i) => (
                        <Section key={s.key} number={String(i + 1).padStart(2, '0')} title={s.title} id={s.key}>
                            {s.body()}
                        </Section>
                    ))}
                </div>

                {sections.length === 0 && facts.length === 0 && (
                    <p className="text-muted-foreground border-t py-10">This profile does not have any details yet.</p>
                )}

                {contact && (
                    <section
                        id="contact"
                        aria-labelledby="contact-title"
                        className="no-print grid gap-x-8 gap-y-4 border-t py-10 md:grid-cols-[11rem_1fr]"
                    >
                        <h2 id="contact-title" className="text-xl font-semibold">
                            Get in touch with {firstName}
                        </h2>
                        {mode === 'owner' ? (
                            <p className="text-muted-foreground max-w-xl leading-relaxed">
                                Coaches see a message form here. It is hidden for you because this is your own profile.
                            </p>
                        ) : (
                            <ContactForm contact={contact} name={firstName} />
                        )}
                    </section>
                )}

                {mode === 'demo' && (
                    <section className="no-print border-t py-10">
                        <p className="text-muted-foreground max-w-xl leading-relaxed">
                            On a real profile, coaches see a message form here. It is switched off on this sample because there is nobody to receive
                            it.
                        </p>
                        <p className="mt-5">
                            <Link href={route('register')} className={buttonVariants()}>
                                Start your own profile
                            </Link>
                        </p>
                    </section>
                )}
            </main>

            <footer className="border-t">
                <p className="text-muted-foreground mx-auto max-w-5xl px-4 py-8 text-sm leading-relaxed sm:px-6 print:py-3 print:text-xs">
                    This page was written by the athlete using {appName}, an independent service. Details are self-reported and have not been
                    verified. {appName} is not the NCAA Transfer Portal and is not affiliated with the NCAA, the NAIA, the NJCAA, any conference or
                    any school.
                </p>
            </footer>
        </div>
    );
}
