import Field from '@/components/field';
import InputError from '@/components/input-error';
import Notice from '@/components/notice';
import HeightInput from '@/components/profile/height-input';
import ItemSection from '@/components/profile/item-section';
import PhotoUploader from '@/components/profile/photo-uploader';
import { SaveBar, useSectionForm } from '@/components/profile/save-bar';
import { buttonVariants } from '@/components/ui/button';
import { Input, Select, Textarea } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { formatStat, formatYears } from '@/lib/format';
import { cn } from '@/lib/utils';
import { Honour, OwnerProfile, ProfileOptions, Reference, SeasonStat, Team, VideoItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { ReactNode } from 'react';

interface EditProps {
    section: string;
    sectionTitle: string;
    step: number;
    stepCount: number;
    previous: string | null;
    next: string | null;
    profile: OwnerProfile;
    options: ProfileOptions;
}

type SectionProps = Pick<EditProps, 'profile' | 'options' | 'next'>;

const toggle = <T,>(list: T[], value: T) => (list.includes(value) ? list.filter((v) => v !== value) : [...list, value]);
const numberOrNull = (value: string) => (value === '' ? null : Number(value));
const errorsOf = (errors: unknown) => errors as Record<string, string | undefined>;

function Basics({ profile, options, next }: SectionProps) {
    const { form, submit, advance } = useSectionForm('basics', { display_name: profile.display_name ?? '', pronouns: profile.pronouns ?? '' }, next);

    return (
        <div className="grid gap-10">
            <form onSubmit={submit} noValidate className="grid gap-5">
                <Field label="Name as coaches should see it" htmlFor="display_name" error={form.errors.display_name}>
                    <Input
                        id="display_name"
                        required
                        autoComplete="name"
                        value={form.data.display_name}
                        onChange={(e) => form.setData('display_name', e.target.value)}
                    />
                </Field>
                <Field label="Pronouns" htmlFor="pronouns" optional error={form.errors.pronouns}>
                    <Input
                        id="pronouns"
                        placeholder="she/her, he/him, they/them"
                        value={form.data.pronouns}
                        onChange={(e) => form.setData('pronouns', e.target.value)}
                    />
                </Field>
                <PhotoUploader
                    hasPhoto={profile.has_photo}
                    version={profile.photo_version}
                    name={form.data.display_name}
                    maxKilobytes={options.photoMaxKilobytes}
                />
                <SaveBar processing={form.processing} advance={advance} next={next} />
            </form>
        </div>
    );
}

function Playing({ profile, options, next }: SectionProps) {
    const { form, submit, advance } = useSectionForm(
        'playing',
        {
            positions: profile.sport_data.positions,
            preferred_foot: profile.sport_data.preferred_foot ?? '',
            height_cm: profile.height_cm,
        },
        next,
    );
    const errors = errorsOf(form.errors);
    const chosen = form.data.positions;
    const full = chosen.length >= 3;

    return (
        <form onSubmit={submit} noValidate className="grid gap-8">
            <fieldset>
                <legend className="text-sm font-medium">Positions</legend>
                <p className="text-muted-foreground mt-1 text-sm">Pick up to three. Tap your main position first.</p>
                <div className="mt-3 flex flex-wrap gap-2">
                    {Object.entries(options.positions).map(([code, label]) => {
                        const index = chosen.indexOf(code);
                        const on = index !== -1;

                        return (
                            <button
                                key={code}
                                type="button"
                                aria-pressed={on}
                                disabled={!on && full}
                                onClick={() => form.setData('positions', toggle(chosen, code))}
                                className={cn(
                                    'inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-md border px-3 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-45',
                                    on ? 'border-primary bg-primary text-primary-foreground' : 'border-input hover:bg-secondary',
                                )}
                            >
                                <span className="numeral">{code}</span>
                                <span className={on ? '' : 'text-muted-foreground'}>{label}</span>
                                {on && index === 0 && chosen.length > 1 && <span className="text-xs">(main)</span>}
                            </button>
                        );
                    })}
                </div>
                <InputError message={errors.positions ?? errors['positions.0']} className="mt-2" />
            </fieldset>

            <fieldset>
                <legend className="text-sm font-medium">Preferred foot</legend>
                <div className="mt-3 flex flex-wrap gap-x-6 gap-y-2">
                    {Object.entries(options.feet).map(([value, label]) => (
                        <label key={value} className="flex min-h-11 items-center gap-2">
                            <input
                                type="radio"
                                name="preferred_foot"
                                className="accent-primary size-5"
                                value={value}
                                checked={form.data.preferred_foot === value}
                                onChange={() => form.setData('preferred_foot', value)}
                            />
                            {label}
                        </label>
                    ))}
                </div>
                <InputError message={errors.preferred_foot} className="mt-2" />
            </fieldset>

            <fieldset>
                <legend className="mb-2 text-sm font-medium">
                    Height <span className="text-muted-foreground font-normal">(optional)</span>
                </legend>
                <HeightInput value={form.data.height_cm} onChange={(cm) => form.setData('height_cm', cm)} />
                <InputError message={errors.height_cm} className="mt-2" />
            </fieldset>

            <SaveBar processing={form.processing} advance={advance} next={next} />
        </form>
    );
}

function School({ profile, options, next }: SectionProps) {
    const { form, submit, advance } = useSectionForm(
        'school',
        {
            current_school: profile.current_school ?? '',
            division: profile.division ?? '',
            conference: profile.conference ?? '',
            class_year: profile.class_year ?? '',
            eligibility_years_left: profile.eligibility_years_left,
            portal_entered_on: profile.portal_entered_on ?? '',
        },
        next,
    );

    return (
        <form onSubmit={submit} noValidate className="grid gap-5">
            <Field label="Current school" htmlFor="current_school" error={form.errors.current_school}>
                <Input
                    id="current_school"
                    autoComplete="organization"
                    value={form.data.current_school}
                    onChange={(e) => form.setData('current_school', e.target.value)}
                />
            </Field>
            <div className="grid gap-5 sm:grid-cols-2">
                <Field label="Division" htmlFor="division" error={form.errors.division}>
                    <Select id="division" value={form.data.division} onChange={(e) => form.setData('division', e.target.value)}>
                        <option value="">Choose…</option>
                        {options.divisions.map((division) => (
                            <option key={division}>{division}</option>
                        ))}
                    </Select>
                </Field>
                <Field label="Conference" htmlFor="conference" optional error={form.errors.conference}>
                    <Input id="conference" value={form.data.conference} onChange={(e) => form.setData('conference', e.target.value)} />
                </Field>
            </div>
            <div className="grid gap-5 sm:grid-cols-2">
                <Field label="Class year" htmlFor="class_year" error={form.errors.class_year}>
                    <Select id="class_year" value={form.data.class_year} onChange={(e) => form.setData('class_year', e.target.value)}>
                        <option value="">Choose…</option>
                        {options.classYears.map((year) => (
                            <option key={year}>{year}</option>
                        ))}
                    </Select>
                </Field>
                <Field
                    label="Seasons of eligibility left"
                    htmlFor="eligibility_years_left"
                    optional
                    hint="Your own best understanding. Shown to coaches as self-reported."
                    error={form.errors.eligibility_years_left}
                >
                    <Select
                        id="eligibility_years_left"
                        aria-describedby="eligibility_years_left-hint"
                        value={form.data.eligibility_years_left ?? ''}
                        onChange={(e) => form.setData('eligibility_years_left', numberOrNull(e.target.value))}
                    >
                        <option value="">Prefer not to say</option>
                        {[0, 1, 2, 3, 4, 5].map((years) => (
                            <option key={years} value={years}>
                                {years}
                            </option>
                        ))}
                    </Select>
                </Field>
            </div>

            <Field
                label="Date you entered the official transfer portal"
                htmlFor="portal_entered_on"
                optional
                hint="Only fill this in if your school has already entered you. It is hidden from coaches unless you switch it on under Sharing, and it is shown as self-reported."
                error={form.errors.portal_entered_on}
            >
                <Input
                    id="portal_entered_on"
                    type="date"
                    className="sm:max-w-56"
                    aria-describedby="portal_entered_on-hint"
                    max={new Date().toISOString().slice(0, 10)}
                    value={form.data.portal_entered_on}
                    onChange={(e) => form.setData('portal_entered_on', e.target.value)}
                />
            </Field>

            <Notice>
                We cannot tell you how much eligibility you have or what the transfer rules are for you. Your school&apos;s compliance office can.
                This site is not the official portal and filling this in does not enter you into it.
            </Notice>

            <SaveBar processing={form.processing} advance={advance} next={next} />
        </form>
    );
}

function Academics({ profile, next }: SectionProps) {
    const { form, submit, advance } = useSectionForm(
        'academics',
        { major: profile.major ?? '', gpa: profile.gpa ?? '', gpa_scale: profile.gpa_scale ?? '4.0' },
        next,
    );

    return (
        <form onSubmit={submit} noValidate className="grid gap-5">
            <Field label="Major" htmlFor="major" optional error={form.errors.major}>
                <Input id="major" value={form.data.major} onChange={(e) => form.setData('major', e.target.value)} />
            </Field>
            <div className="grid grid-cols-2 gap-5 sm:max-w-sm">
                <Field label="GPA" htmlFor="gpa" optional error={form.errors.gpa}>
                    <Input
                        id="gpa"
                        inputMode="decimal"
                        className="numeral"
                        placeholder="3.50"
                        value={form.data.gpa}
                        onChange={(e) => form.setData('gpa', e.target.value)}
                    />
                </Field>
                <Field label="Out of" htmlFor="gpa_scale" error={form.errors.gpa_scale}>
                    <Select id="gpa_scale" value={form.data.gpa_scale} onChange={(e) => form.setData('gpa_scale', e.target.value)}>
                        <option value="4.0">4.0</option>
                        <option value="5.0">5.0</option>
                    </Select>
                </Field>
            </div>
            <Notice>
                Your GPA is hidden from coaches unless you switch it on under <strong>Sharing</strong>. You can leave it blank.
            </Notice>
            <SaveBar processing={form.processing} advance={advance} next={next} />
        </form>
    );
}

function CheckList({
    legend,
    hint,
    values,
    chosen,
    onChange,
    error,
}: {
    legend: string;
    hint?: string;
    values: string[];
    chosen: string[];
    onChange: (v: string[]) => void;
    error?: string;
}) {
    return (
        <fieldset>
            <legend className="text-sm font-medium">{legend}</legend>
            {hint && <p className="text-muted-foreground mt-1 text-sm">{hint}</p>}
            <div className="mt-2 grid gap-x-6 sm:grid-cols-2">
                {values.map((value) => (
                    <label key={value} className="flex min-h-11 items-center gap-3">
                        <input
                            type="checkbox"
                            className="accent-primary size-5"
                            checked={chosen.includes(value)}
                            onChange={() => onChange(toggle(chosen, value))}
                        />
                        {value}
                    </label>
                ))}
            </div>
            <InputError message={error} className="mt-2" />
        </fieldset>
    );
}

function LookingFor({ profile, options, next }: SectionProps) {
    const { form, submit, advance } = useSectionForm(
        'looking-for',
        {
            levels: profile.looking_for.levels,
            regions: profile.looking_for.regions,
            intended_major: profile.looking_for.intended_major ?? '',
            timeline: profile.looking_for.timeline ?? '',
        },
        next,
    );
    const errors = errorsOf(form.errors);

    return (
        <form onSubmit={submit} noValidate className="grid gap-7">
            <CheckList
                legend="Level"
                hint="Tick every level you would consider."
                values={options.divisions}
                chosen={form.data.levels}
                onChange={(levels) => form.setData('levels', levels)}
                error={errors.levels}
            />
            <CheckList
                legend="Region"
                values={options.regions}
                chosen={form.data.regions}
                onChange={(regions) => form.setData('regions', regions)}
                error={errors.regions}
            />
            <Field label="Intended major" htmlFor="intended_major" optional error={errors.intended_major}>
                <Input id="intended_major" value={form.data.intended_major} onChange={(e) => form.setData('intended_major', e.target.value)} />
            </Field>
            <Field label="Timeline" htmlFor="timeline" optional hint="For example: ready for the fall term." error={errors.timeline}>
                <Input
                    id="timeline"
                    maxLength={80}
                    aria-describedby="timeline-hint"
                    value={form.data.timeline}
                    onChange={(e) => form.setData('timeline', e.target.value)}
                />
            </Field>
            <SaveBar processing={form.processing} advance={advance} next={next} />
        </form>
    );
}

function Statement({ profile, next }: SectionProps) {
    const { form, submit, advance } = useSectionForm('statement', { statement: profile.statement ?? '' }, next);
    const limit = 1500;

    return (
        <form onSubmit={submit} noValidate className="grid gap-5">
            <Field
                label="In your own words"
                htmlFor="statement"
                hint="A few sentences on how you play, what you bring to a team and what you want next. Keep anything about your current team respectful: coaches talk to each other."
                error={form.errors.statement}
            >
                <Textarea
                    id="statement"
                    rows={9}
                    maxLength={limit}
                    aria-describedby="statement-hint statement-count"
                    value={form.data.statement}
                    onChange={(e) => form.setData('statement', e.target.value)}
                />
            </Field>
            <p id="statement-count" className="text-muted-foreground numeral -mt-3 text-right text-xs">
                {form.data.statement.length} / {limit}
            </p>
            <SaveBar processing={form.processing} advance={advance} next={next} />
        </form>
    );
}

function History({ profile, options }: SectionProps) {
    const thisYear = new Date().getFullYear();

    return (
        <ItemSection<Team, { kind: string; name: string; detail: string; start_year: number | null; end_year: number | null }>
            type="teams"
            noun="team"
            items={profile.teams}
            emptyText="No teams yet. Add your college, club and high school teams, newest first."
            blank={{ kind: 'college', name: '', detail: '', start_year: null, end_year: null }}
            toForm={(t) => ({ kind: t.kind, name: t.name, detail: t.detail ?? '', start_year: t.start_year, end_year: t.end_year })}
            summary={(t) => (
                <>
                    <p className="font-semibold">{t.name}</p>
                    <p className="text-muted-foreground text-sm">
                        {[options.teamKinds[t.kind], formatYears(t.start_year, t.end_year), t.detail].filter(Boolean).join(' · ')}
                    </p>
                </>
            )}
            fields={(form) => (
                <>
                    <div className="grid gap-5 sm:grid-cols-[10rem_1fr]">
                        <Field label="Type" htmlFor="team_kind" error={form.errors.kind}>
                            <Select id="team_kind" value={form.data.kind} onChange={(e) => form.setData('kind', e.target.value)}>
                                {Object.entries(options.teamKinds).map(([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Team or school name" htmlFor="team_name" error={form.errors.name}>
                            <Input id="team_name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                        </Field>
                    </div>
                    <Field label="League, level or role" htmlFor="team_detail" optional error={form.errors.detail}>
                        <Input
                            id="team_detail"
                            placeholder="e.g. ECNL, varsity captain"
                            value={form.data.detail}
                            onChange={(e) => form.setData('detail', e.target.value)}
                        />
                    </Field>
                    <div className="grid grid-cols-2 gap-5 sm:max-w-sm">
                        <Field label="From (year)" htmlFor="team_start" error={form.errors.start_year}>
                            <Input
                                id="team_start"
                                inputMode="numeric"
                                className="numeral"
                                placeholder={String(thisYear - 2)}
                                value={form.data.start_year ?? ''}
                                onChange={(e) => form.setData('start_year', numberOrNull(e.target.value.replace(/\D/g, '')))}
                            />
                        </Field>
                        <Field label="To (year)" htmlFor="team_end" hint="Blank if you are still there." error={form.errors.end_year}>
                            <Input
                                id="team_end"
                                inputMode="numeric"
                                className="numeral"
                                aria-describedby="team_end-hint"
                                value={form.data.end_year ?? ''}
                                onChange={(e) => form.setData('end_year', numberOrNull(e.target.value.replace(/\D/g, '')))}
                            />
                        </Field>
                    </div>
                </>
            )}
        />
    );
}

function StatsSection({ profile, options }: SectionProps) {
    const keeper = profile.sport_data.positions.includes('GK');
    const fields = options.statFields.filter((f) => f.group === 'all' || keeper);

    return (
        <>
            <ItemSection<SeasonStat, { season: string; team: string; competition: string; stats: Record<string, string> }>
                type="stats"
                noun="season"
                items={profile.season_stats}
                emptyText="No seasons yet. Add one row per season and team."
                blank={{ season: '', team: profile.current_school ?? '', competition: '', stats: {} }}
                toForm={(s) => ({
                    season: s.season,
                    team: s.team,
                    competition: s.competition ?? '',
                    stats: Object.fromEntries(Object.entries(s.stats).map(([k, v]) => [k, v === null || v === undefined ? '' : String(v)])),
                })}
                summary={(s) => (
                    <>
                        <p className="font-semibold">
                            <span className="numeral">{s.season}</span> · {s.team}
                        </p>
                        <p className="text-muted-foreground numeral text-sm">
                            {fields
                                .filter((f) => s.stats[f.key] !== undefined && s.stats[f.key] !== null)
                                .map((f) => `${formatStat(s.stats[f.key], f.key)} ${f.short}`)
                                .join(' · ') || 'No numbers entered'}
                        </p>
                    </>
                )}
                fields={(form) => {
                    const errors = errorsOf(form.errors);

                    return (
                        <>
                            <div className="grid gap-5 sm:grid-cols-[9rem_1fr]">
                                <Field label="Season" htmlFor="stat_season" hint="2025 or 2025-26" error={errors.season}>
                                    <Input
                                        id="stat_season"
                                        className="numeral"
                                        aria-describedby="stat_season-hint"
                                        value={form.data.season}
                                        onChange={(e) => form.setData('season', e.target.value)}
                                    />
                                </Field>
                                <Field label="Team" htmlFor="stat_team" error={errors.team}>
                                    <Input id="stat_team" value={form.data.team} onChange={(e) => form.setData('team', e.target.value)} />
                                </Field>
                            </div>
                            <Field label="Competition" htmlFor="stat_competition" optional error={errors.competition}>
                                <Input
                                    id="stat_competition"
                                    placeholder="e.g. conference play"
                                    value={form.data.competition}
                                    onChange={(e) => form.setData('competition', e.target.value)}
                                />
                            </Field>
                            <fieldset>
                                <legend className="text-sm font-medium">Numbers</legend>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    Leave a box empty if you do not have the number. Empty is shown as a dash, not as zero.
                                </p>
                                <div className="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-4">
                                    {fields.map((f) => (
                                        <Field key={f.key} label={f.label} htmlFor={`stat_${f.key}`} error={errors[`stats.${f.key}`]}>
                                            <Input
                                                id={`stat_${f.key}`}
                                                inputMode={f.type === 'decimal' ? 'decimal' : 'numeric'}
                                                className="numeral"
                                                value={form.data.stats[f.key] ?? ''}
                                                onChange={(e) => form.setData('stats', { ...form.data.stats, [f.key]: e.target.value })}
                                            />
                                        </Field>
                                    ))}
                                </div>
                            </fieldset>
                        </>
                    );
                }}
            />
            {!keeper && (
                <p className="text-muted-foreground mt-6 text-sm">
                    Goalkeeper? Add GK under{' '}
                    <Link href={route('profile.section', 'playing')} className="underline underline-offset-4">
                        Position
                    </Link>{' '}
                    to get saves, clean sheets and goals-against average.
                </p>
            )}
        </>
    );
}

function Honours({ profile }: SectionProps) {
    return (
        <ItemSection<Honour, { title: string; year: number | null }>
            type="honours"
            noun="honour"
            items={profile.honours}
            emptyText="No honours yet. All-conference teams, academic awards, captaincies and similar."
            blank={{ title: '', year: null }}
            toForm={(h) => ({ title: h.title, year: h.year })}
            summary={(h) => (
                <p>
                    <span className="font-semibold">{h.title}</span>
                    {h.year && <span className="text-muted-foreground numeral"> · {h.year}</span>}
                </p>
            )}
            fields={(form) => (
                <div className="grid gap-5 sm:grid-cols-[1fr_8rem]">
                    <Field label="Honour" htmlFor="honour_title" error={form.errors.title}>
                        <Input id="honour_title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                    </Field>
                    <Field label="Year" htmlFor="honour_year" optional error={form.errors.year}>
                        <Input
                            id="honour_year"
                            inputMode="numeric"
                            className="numeral"
                            value={form.data.year ?? ''}
                            onChange={(e) => form.setData('year', numberOrNull(e.target.value.replace(/\D/g, '')))}
                        />
                    </Field>
                </div>
            )}
        />
    );
}

function Videos({ profile, options }: SectionProps) {
    return (
        <>
            <ItemSection<VideoItem, { kind: string; title: string; url: string }>
                type="videos"
                noun="video link"
                items={profile.videos}
                emptyText="No video yet. A two to four minute highlight reel is the first thing most coaches open."
                blank={{ kind: 'highlight', title: '', url: '' }}
                toForm={(v) => ({ kind: v.kind, title: v.title, url: v.url })}
                summary={(v) => (
                    <>
                        <p className="font-semibold">{v.title}</p>
                        <p className="text-muted-foreground text-sm break-all">
                            {options.videoKinds[v.kind]} · {v.label} · {v.embed_url ? 'plays on your page' : 'shown as a link'}
                        </p>
                    </>
                )}
                fields={(form) => (
                    <>
                        <div className="grid gap-5 sm:grid-cols-[10rem_1fr]">
                            <Field label="Type" htmlFor="video_kind" error={form.errors.kind}>
                                <Select id="video_kind" value={form.data.kind} onChange={(e) => form.setData('kind', e.target.value)}>
                                    {Object.entries(options.videoKinds).map(([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                            <Field label="Title" htmlFor="video_title" error={form.errors.title}>
                                <Input
                                    id="video_title"
                                    placeholder="e.g. 2026 season highlights"
                                    value={form.data.title}
                                    onChange={(e) => form.setData('title', e.target.value)}
                                />
                            </Field>
                        </div>
                        <Field label="Link" htmlFor="video_url" hint="Paste the full address, starting with https://" error={form.errors.url}>
                            <Input
                                id="video_url"
                                type="url"
                                inputMode="url"
                                autoCapitalize="none"
                                aria-describedby="video_url-hint"
                                value={form.data.url}
                                onChange={(e) => form.setData('url', e.target.value)}
                            />
                        </Field>
                    </>
                )}
            />
            <Notice className="mt-6">
                We do not store video, only links. YouTube and Vimeo links play on your page. Hudl and any other link is shown as a plain link the
                coach can tap. Make sure the video is set so that people with the link can watch it.
            </Notice>
        </>
    );
}

function References({ profile }: SectionProps) {
    return (
        <>
            <ItemSection<Reference, { name: string; role: string; organization: string; contact: string; consent: boolean }>
                type="references"
                noun="reference"
                items={profile.references}
                emptyText="No references yet. A club coach, trainer or teacher who knows you well."
                blank={{ name: '', role: '', organization: '', contact: '', consent: false }}
                toForm={(r) => ({ name: r.name, role: r.role, organization: r.organization ?? '', contact: r.contact, consent: false })}
                summary={(r) => (
                    <>
                        <p className="font-semibold">{r.name}</p>
                        <p className="text-muted-foreground text-sm break-words">{[r.role, r.organization, r.contact].filter(Boolean).join(' · ')}</p>
                    </>
                )}
                fields={(form) => (
                    <>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field label="Their name" htmlFor="ref_name" error={form.errors.name}>
                                <Input id="ref_name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                            </Field>
                            <Field label="How they know you" htmlFor="ref_role" error={form.errors.role}>
                                <Input
                                    id="ref_role"
                                    placeholder="e.g. Club head coach"
                                    value={form.data.role}
                                    onChange={(e) => form.setData('role', e.target.value)}
                                />
                            </Field>
                        </div>
                        <Field label="Club, school or organisation" htmlFor="ref_org" optional error={form.errors.organization}>
                            <Input id="ref_org" value={form.data.organization} onChange={(e) => form.setData('organization', e.target.value)} />
                        </Field>
                        <Field
                            label="How a coach can reach them"
                            htmlFor="ref_contact"
                            hint="Their email or phone number, as they want it shared."
                            error={form.errors.contact}
                        >
                            <Input
                                id="ref_contact"
                                aria-describedby="ref_contact-hint"
                                value={form.data.contact}
                                onChange={(e) => form.setData('contact', e.target.value)}
                            />
                        </Field>
                        <div>
                            <label className="flex items-start gap-3 text-sm leading-relaxed">
                                <input
                                    type="checkbox"
                                    className="accent-primary mt-0.5 size-5 shrink-0"
                                    checked={form.data.consent}
                                    onChange={(e) => form.setData('consent', e.target.checked)}
                                />
                                <span>
                                    This person has agreed to be my reference and to have these contact details shown to coaches I share my profile
                                    with.
                                </span>
                            </label>
                            <InputError message={errorsOf(form.errors).consent} className="mt-2" />
                        </div>
                    </>
                )}
            />
            <Notice className="mt-6">
                References are hidden from coaches until you switch them on under <strong>Sharing</strong>. Think about who you list: a reference at
                your current school may be asked about you before you have told them your plans.
            </Notice>
        </>
    );
}

const SECTIONS: Record<string, { intro: ReactNode; component: (props: SectionProps) => ReactNode; list?: boolean }> = {
    basics: { intro: 'Start with how your name should appear.', component: Basics },
    playing: { intro: 'Where you play and how you are built.', component: Playing },
    school: { intro: 'Where you are now.', component: School },
    history: { intro: 'The teams you have played for.', component: History, list: true },
    stats: {
        intro: "One row per season. Numbers are self-reported, so use the ones on your team's official stats page.",
        component: StatsSection,
        list: true,
    },
    honours: { intro: 'Awards and recognition, on the field or in class.', component: Honours, list: true },
    academics: { intro: 'Coaches need to know you can get in and stay eligible.', component: Academics },
    videos: { intro: 'Links to your highlights and full matches.', component: Videos, list: true },
    references: { intro: 'People who will speak for you.', component: References, list: true },
    'looking-for': { intro: 'Help coaches see quickly whether you are a fit.', component: LookingFor },
    statement: { intro: 'The part only you can write.', component: Statement },
};

export default function ProfileEdit({ section, sectionTitle, step, stepCount, previous, next, profile, options }: EditProps) {
    const config = SECTIONS[section];
    const Section = config.component;

    return (
        <AppLayout>
            <Head title={sectionTitle} />

            <Link
                href={route('profile.index')}
                className="text-muted-foreground hover:text-foreground mb-6 inline-flex min-h-11 items-center gap-2 text-sm font-medium"
            >
                <ArrowLeft className="size-4" aria-hidden="true" />
                All parts
            </Link>

            <header className="reveal mb-8">
                <p className="kicker">
                    Part <span className="numeral">{step}</span> of <span className="numeral">{stepCount}</span>
                </p>
                <h1 className="mt-3 text-3xl leading-tight font-semibold sm:text-4xl">{sectionTitle}</h1>
                <p className="text-muted-foreground mt-3 max-w-2xl leading-relaxed">{config.intro}</p>
            </header>

            <div className="reveal [--i:1]" key={section}>
                <Section profile={profile} options={options} next={next} />
            </div>

            {config.list && (
                <nav aria-label="Parts" className="mt-10 flex flex-col gap-3 border-t pt-6 sm:flex-row sm:justify-between">
                    {previous ? (
                        <Link href={route('profile.section', previous)} className={buttonVariants({ variant: 'outline' })}>
                            <ArrowLeft aria-hidden="true" />
                            Back
                        </Link>
                    ) : (
                        <span />
                    )}
                    <Link href={next ? route('profile.section', next) : route('profile.index')} className={buttonVariants()}>
                        {next ? 'Continue' : 'Finish'}
                        <ArrowRight aria-hidden="true" />
                    </Link>
                </nav>
            )}
        </AppLayout>
    );
}
