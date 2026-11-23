import ConfirmAction from '@/components/confirm-action';
import Notice from '@/components/notice';
import PageHeader from '@/components/page-header';
import { Button, buttonVariants } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/format';
import { ViewSummary } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Check, Copy, ExternalLink, Eye, Link2, RefreshCw, ShieldOff } from 'lucide-react';
import { useState } from 'react';

interface SharingProps {
    link: { url: string; created_at: string } | null;
    views: ViewSummary;
    hideable: Record<string, string>;
    hiddenSections: string[];
}

export default function Sharing({ link, views, hideable, hiddenSections }: SharingProps) {
    const [busy, setBusy] = useState(false);
    const [copied, setCopied] = useState<'yes' | 'failed' | null>(null);
    const [hidden, setHidden] = useState(hiddenSections);

    const act = (method: 'post' | 'delete', done?: () => void) => {
        setBusy(true);
        router.visit(route(method === 'post' ? 'sharing.link.store' : 'sharing.link.destroy'), {
            method,
            preserveScroll: true,
            onSuccess: () => done?.(),
            onFinish: () => setBusy(false),
        });
    };

    const copy = async () => {
        if (!link) {
            return;
        }

        try {
            await navigator.clipboard.writeText(link.url);
            setCopied('yes');
        } catch {
            // Some browsers block the clipboard; the address is selectable in the box as a fallback.
            setCopied('failed');
        }

        window.setTimeout(() => setCopied(null), 4000);
    };

    const setVisible = (key: string, visible: boolean) => {
        const previous = hidden;
        const next = visible ? hidden.filter((k) => k !== key) : [...hidden, key];

        setHidden(next);
        router.put(
            route('sharing.visibility'),
            { hidden_sections: next },
            { preserveScroll: true, preserveState: true, onError: () => setHidden(previous) },
        );
    };

    return (
        <AppLayout>
            <Head title="Sharing" />
            <PageHeader kicker="Sharing" title={link ? 'Your link is on' : 'Your profile is private'}>
                {link
                    ? 'Anyone who has this link can open your profile. Send it only to coaches you choose.'
                    : 'Nobody can see your profile. When you are ready, create a secret link and send it to the coaches you choose.'}
            </PageHeader>

            <section aria-label="Your link" className="reveal border-y py-7 [--i:1]">
                {link ? (
                    <div className="grid gap-5">
                        <div className="grid gap-2">
                            <label htmlFor="share-url" className="kicker">
                                Your secret link
                            </label>
                            <div className="flex flex-col gap-2 sm:flex-row">
                                <input
                                    id="share-url"
                                    readOnly
                                    value={link.url}
                                    onFocus={(e) => e.target.select()}
                                    className="field-control numeral min-w-0 flex-1 text-sm"
                                />
                                <Button onClick={copy}>
                                    {copied === 'yes' ? <Check aria-hidden="true" /> : <Copy aria-hidden="true" />}
                                    {copied === 'yes' ? 'Copied' : 'Copy link'}
                                </Button>
                            </div>
                            <p className="text-muted-foreground text-sm" role="status">
                                {copied === 'failed'
                                    ? 'Could not copy automatically. Tap the box, select the link and copy it.'
                                    : `Created ${formatDate(link.created_at)}.`}
                            </p>
                        </div>

                        <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            <a href={link.url} target="_blank" rel="noreferrer" className={buttonVariants({ variant: 'outline' })}>
                                <ExternalLink aria-hidden="true" />
                                Open the page
                            </a>
                            <ConfirmAction
                                trigger={
                                    <Button variant="outline">
                                        <RefreshCw aria-hidden="true" />
                                        Replace with a new link
                                    </Button>
                                }
                                title="Replace your link?"
                                description="The current link stops working straight away for everyone who has it. You will get a new one to send out. Your visit count starts again from zero."
                                confirmLabel="Replace link"
                                busy={busy}
                                onConfirm={(done) => act('post', done)}
                            />
                            <ConfirmAction
                                trigger={
                                    <Button variant="outline">
                                        <ShieldOff aria-hidden="true" />
                                        Switch link off
                                    </Button>
                                }
                                title="Switch your link off?"
                                description="Your profile goes back to private. Anyone opening the old link will see that it is no longer active. You can create a new link whenever you like."
                                confirmLabel="Switch off"
                                destructive
                                busy={busy}
                                onConfirm={(done) => act('delete', done)}
                            />
                        </div>

                        <dl className="grid grid-cols-2 gap-6 border-t pt-6 sm:max-w-md">
                            <div>
                                <dt className="kicker">Visits</dt>
                                <dd className="numeral mt-1 text-4xl font-medium">{views.total}</dd>
                            </div>
                            <div>
                                <dt className="kicker">Last 7 days</dt>
                                <dd className="numeral mt-1 text-4xl font-medium">{views.last_7_days}</dd>
                            </div>
                        </dl>
                        <p className="text-muted-foreground max-w-xl text-sm leading-relaxed">
                            {views.last_viewed_on ? `Most recent visit: ${formatDate(views.last_viewed_on)}. ` : ''}
                            How we count: one visit per visitor per day. Your own visits while logged in, link previews in messaging apps and known
                            bots are left out. We cannot tell you who visited.
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4">
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <Button size="lg" disabled={busy} onClick={() => act('post')}>
                                <Link2 aria-hidden="true" />
                                Create my secret link
                            </Button>
                            <Link href={route('profile.preview')} className={buttonVariants({ size: 'lg', variant: 'outline' })}>
                                <Eye aria-hidden="true" />
                                Preview first
                            </Link>
                        </div>
                        <p className="text-muted-foreground max-w-xl text-sm leading-relaxed">
                            The link is a long random address that cannot be guessed. You can switch it off or replace it at any time.
                        </p>
                    </div>
                )}
            </section>

            <section aria-labelledby="visibility" className="reveal mt-12 [--i:2]">
                <h2 id="visibility" className="text-2xl font-semibold">
                    What coaches can see
                </h2>
                <p className="text-muted-foreground mt-2 max-w-xl leading-relaxed">
                    Switch any part off and it disappears from the shared page. Your name and positions are always shown. Your email and phone number
                    are never shown.
                </p>
                <ul className="mt-5 border-t">
                    {Object.entries(hideable).map(([key, label]) => {
                        const visible = !hidden.includes(key);

                        return (
                            <li key={key} className="flex min-h-14 items-center justify-between gap-4 border-b py-2">
                                <label htmlFor={`show-${key}`} className="flex-1 cursor-pointer">
                                    {label}
                                    <span className="text-muted-foreground block text-sm">{visible ? 'Shown' : 'Hidden'}</span>
                                </label>
                                <Switch id={`show-${key}`} checked={visible} onChange={(checked) => setVisible(key, checked)} />
                            </li>
                        );
                    })}
                </ul>
                <p className="mt-6">
                    <Link href={route('profile.preview')} className={buttonVariants({ variant: 'outline' })}>
                        <Eye aria-hidden="true" />
                        See it as a coach would
                    </Link>
                </p>
            </section>

            <Notice title="Think before you send" className="reveal mt-12 [--i:3]">
                A link can be forwarded. If you have not told your current coach you may leave, only send it to people you trust to keep it to
                themselves. Rules about when coaches may talk to you differ by division and change; your school&apos;s compliance office can tell you
                what applies to you.
            </Notice>
        </AppLayout>
    );
}
