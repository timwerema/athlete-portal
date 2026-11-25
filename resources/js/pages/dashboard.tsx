import Notice from '@/components/notice';
import OfficialSources from '@/components/official-sources';
import PageHeader from '@/components/page-header';
import { buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/format';
import { Completeness, ViewSummary } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { ReactNode } from 'react';

interface DashboardProps {
    displayName: string | null;
    completeness: Completeness;
    link: { created_at: string } | null;
    views: ViewSummary;
    unreadMessages: number;
    totalMessages: number;
}

function Row({ index, label, children, action }: { index: number; label: string; children: ReactNode; action: ReactNode }) {
    return (
        <section
            className="reveal grid gap-x-6 gap-y-3 border-b py-7 sm:grid-cols-[10rem_1fr_auto] sm:items-start"
            style={{ ['--i' as string]: index }}
            aria-label={label}
        >
            <h2 className="kicker pt-1">{label}</h2>
            <div>{children}</div>
            <div className="sm:justify-self-end">{action}</div>
        </section>
    );
}

export default function Dashboard({ displayName, completeness, link, views, unreadMessages, totalMessages }: DashboardProps) {
    const next = completeness.items.filter((item) => !item.done && !item.optional).slice(0, 3);
    const optionalLeft = completeness.items.filter((item) => !item.done && item.optional).length;
    const firstName = displayName?.split(' ')[0];

    return (
        <AppLayout>
            <Head title="Overview" />
            <PageHeader kicker="Overview" title={firstName ? `Hello, ${firstName}.` : 'Hello.'}>
                {link
                    ? 'Your profile link is on. Here is where things stand.'
                    : 'Your profile is private. Nobody can see it until you create a link.'}
            </PageHeader>

            <div className="border-t">
                <Row
                    index={1}
                    label="Profile"
                    action={
                        <Link href={route('profile.index')} className={buttonVariants({ variant: 'outline' })}>
                            {completeness.done === 0 ? 'Start' : 'Edit profile'}
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    }
                >
                    <p className="text-2xl font-semibold">
                        <span className="numeral">{completeness.done}</span> of <span className="numeral">{completeness.total}</span> parts filled in
                    </p>
                    <div
                        className="bg-secondary mt-3 h-1.5 max-w-sm"
                        role="progressbar"
                        aria-label="Profile parts filled in"
                        aria-valuemin={0}
                        aria-valuemax={completeness.total}
                        aria-valuenow={completeness.done}
                    >
                        <div className="bg-primary h-full" style={{ width: `${(completeness.done / completeness.total) * 100}%` }} />
                    </div>
                    {next.length > 0 ? (
                        <ul className="mt-4 space-y-1">
                            {next.map((item) => (
                                <li key={item.key}>
                                    <Link
                                        href={route('profile.section', item.section)}
                                        className="inline-flex min-h-9 items-center gap-2 underline underline-offset-4"
                                    >
                                        {item.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-muted-foreground mt-4">
                            The main parts are done.
                            {optionalLeft > 0 &&
                                ` ${optionalLeft} optional ${optionalLeft === 1 ? 'part is' : 'parts are'} still empty, which is fine.`}
                        </p>
                    )}
                </Row>

                <Row
                    index={2}
                    label="Sharing"
                    action={
                        <Link href={route('sharing.index')} className={buttonVariants({ variant: 'outline' })}>
                            {link ? 'Manage link' : 'Sharing'}
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    }
                >
                    {link ? (
                        <>
                            <p className="text-2xl font-semibold">
                                <span className="numeral">{views.total}</span> {views.total === 1 ? 'visit' : 'visits'} to your link
                            </p>
                            <p className="text-muted-foreground mt-2">
                                <span className="numeral">{views.last_7_days}</span> in the last 7 days
                                {views.last_viewed_on && <>, most recently on {formatDate(views.last_viewed_on)}</>}. Your own visits are not counted.
                            </p>
                        </>
                    ) : (
                        <>
                            <p className="text-2xl font-semibold">Private</p>
                            <p className="text-muted-foreground mt-2">
                                No link exists. When you are ready, create one and send it to coaches you choose.
                            </p>
                        </>
                    )}
                </Row>

                <Row
                    index={3}
                    label="Inbox"
                    action={
                        <Link href={route('inbox.index')} className={buttonVariants({ variant: 'outline' })}>
                            Open inbox
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    }
                >
                    <p className="text-2xl font-semibold">
                        {unreadMessages > 0 ? (
                            <>
                                <span className="numeral">{unreadMessages}</span> new {unreadMessages === 1 ? 'message' : 'messages'}
                            </>
                        ) : (
                            'No new messages'
                        )}
                    </p>
                    <p className="text-muted-foreground mt-2">
                        {totalMessages === 0
                            ? 'Messages coaches send through your link will arrive here and in your email.'
                            : `${totalMessages} in total.`}
                    </p>
                </Row>
            </div>

            <Notice title="Before you share" className="reveal mt-10 [--i:4]">
                This is not the official NCAA Transfer Portal and making a profile here does not enter you into it. Transfer and contact rules differ
                by division and change often. If you are unsure what you are allowed to do, ask your school&apos;s compliance office first.
            </Notice>
            <OfficialSources className="mt-12" />
        </AppLayout>
    );
}
