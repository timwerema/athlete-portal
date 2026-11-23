import { buttonVariants } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { cn } from '@/lib/utils';
import { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { CSSProperties } from 'react';

const STEPS = [
    ['Build it on your phone', 'Positions, season stats, club and college history, video links, academics, and what you want next. Save as you go.'],
    [
        'Create your secret link',
        'Nobody can see your profile until you make a link. It is a long, unguessable address that only works for people you send it to.',
    ],
    [
        'Send it to the coaches you choose',
        'They read one clean page, print it if they like, and write to you through a form. Your email and phone are never shown.',
    ],
    ['Change your mind any time', 'Switch the link off or replace it with a new one in one tap. The old address stops working straight away.'],
];

const ON_A_PROFILE = [
    'Positions, preferred foot and height',
    'Per-season stats, including goalkeeper stats',
    'Club, school and college history',
    'Highlight and full-match video links',
    'Honours',
    'Major, and GPA only if you switch it on',
    'What you are looking for: level, region, timeline',
    'A short statement in your own words',
];

const PROMISES = [
    ['Private until you share', 'A new profile is visible to nobody. There is no public directory and no search.'],
    ['You pick what shows', 'Every part of the page has its own switch. GPA and references start switched off.'],
    ['Honest numbers', 'You see how many people opened your link. Your own visits and automated previews are not counted.'],
    ['Your data is yours', 'Download everything we hold, or delete your account and all of it, whenever you want.'],
];

const delay = (i: number) => ({ '--i': i }) as CSSProperties;

export default function Welcome({ hasDemo }: { hasDemo: boolean }) {
    const { name, auth } = usePage<SharedData>().props;

    return (
        <PublicLayout>
            <Head title="">
                <meta
                    name="description"
                    content="A private recruiting profile for college soccer players. Build it once, share it with the coaches you choose."
                />
            </Head>

            <section className="mx-auto max-w-5xl px-4 pt-14 pb-16 sm:px-6 sm:pt-24 sm:pb-24">
                <p className="kicker reveal">For college soccer players thinking about their next move</p>
                <h1 className="reveal mt-5 max-w-4xl text-[2.6rem] leading-[0.98] font-semibold [--i:1] sm:text-7xl">
                    One link that tells a coach <span className="bg-mark text-mark-foreground box-decoration-clone px-2">who you are</span> as a
                    player.
                </h1>
                <p className="text-muted-foreground reveal mt-7 max-w-2xl text-lg leading-relaxed [--i:2] sm:text-xl">
                    Put your stats, video, history and goals on one clean page. It stays private until you send the link, and you can switch it off
                    whenever you like.
                </p>
                <div className="reveal mt-9 flex flex-col gap-3 [--i:3] sm:flex-row">
                    <Link href={auth?.user ? route('dashboard') : route('register')} className={buttonVariants({ size: 'lg' })}>
                        {auth?.user ? 'Go to my profile' : 'Start your profile'}
                        <ArrowRight aria-hidden="true" />
                    </Link>
                    {hasDemo && (
                        <Link href={route('demo')} className={buttonVariants({ size: 'lg', variant: 'outline' })}>
                            See a sample profile
                        </Link>
                    )}
                </div>
                <p className="text-muted-foreground reveal mt-5 text-sm [--i:4]">For current college athletes aged 18 or over.</p>
            </section>

            <section aria-labelledby="how" className="border-t">
                <div className="mx-auto grid max-w-5xl gap-8 px-4 py-14 sm:px-6 sm:py-20 md:grid-cols-[14rem_1fr]">
                    <h2 id="how" className="text-2xl font-semibold sm:text-3xl">
                        How it works
                    </h2>
                    <ol className="border-t">
                        {STEPS.map(([title, text], i) => (
                            <li
                                key={title}
                                className="reveal grid grid-cols-[3rem_1fr] gap-x-4 border-b py-6 sm:grid-cols-[4rem_14rem_1fr]"
                                style={delay(i)}
                            >
                                <span className="numeral text-primary text-sm">{String(i + 1).padStart(2, '0')}</span>
                                <h3 className="text-lg leading-snug font-semibold">{title}</h3>
                                <p className="text-muted-foreground col-start-2 mt-2 leading-relaxed sm:col-start-3 sm:mt-0">{text}</p>
                            </li>
                        ))}
                    </ol>
                </div>
            </section>

            <section aria-labelledby="whats-on" className="border-t">
                <div className="mx-auto grid max-w-5xl gap-8 px-4 py-14 sm:px-6 sm:py-20 md:grid-cols-[14rem_1fr]">
                    <div>
                        <h2 id="whats-on" className="text-2xl font-semibold sm:text-3xl">
                            What goes on a profile
                        </h2>
                        <p className="text-muted-foreground mt-3 text-sm leading-relaxed">Soccer first. More sports later.</p>
                    </div>
                    <ul className="grid gap-x-10 sm:grid-cols-2">
                        {ON_A_PROFILE.map((item, i) => (
                            <li
                                key={item}
                                className={cn('reveal flex gap-4 border-b py-4', i < 2 && 'sm:border-t', i === 0 && 'border-t')}
                                style={delay(i % 4)}
                            >
                                <span className="bg-primary mt-2.5 block size-1.5 shrink-0" aria-hidden="true" />
                                {item}
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            <section aria-labelledby="privacy" className="border-t">
                <div className="mx-auto grid max-w-5xl gap-8 px-4 py-14 sm:px-6 sm:py-20 md:grid-cols-[14rem_1fr]">
                    <h2 id="privacy" className="text-2xl font-semibold sm:text-3xl">
                        Built for athletes who have not told anyone yet
                    </h2>
                    <dl className="border-t">
                        {PROMISES.map(([title, text], i) => (
                            <div key={title} className="reveal grid gap-1 border-b py-5 sm:grid-cols-[14rem_1fr] sm:gap-6" style={delay(i)}>
                                <dt className="font-semibold">{title}</dt>
                                <dd className="text-muted-foreground leading-relaxed">{text}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </section>

            <section aria-labelledby="not" className="bg-surface border-t">
                <div className="mx-auto grid max-w-5xl gap-8 px-4 py-14 sm:px-6 sm:py-20 md:grid-cols-[14rem_1fr]">
                    <h2 id="not" className="text-2xl font-semibold sm:text-3xl">
                        What this is not
                    </h2>
                    <div className="max-w-2xl space-y-4 leading-relaxed">
                        <p>
                            {name} is an independent tool for presenting yourself. It is <strong>not the NCAA Transfer Portal</strong>, and it is not
                            connected to the NCAA, the NAIA, the NJCAA, any conference or any school. Making a profile here does not enter you into
                            any official system and does not change your status with your team.
                        </p>
                        <p className="text-muted-foreground">
                            Transfer and contact rules are different for each division and they change. We do not give rules advice. Before you
                            contact coaches or reply to them, check with your school&apos;s compliance office.
                        </p>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
