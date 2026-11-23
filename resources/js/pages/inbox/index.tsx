import PageHeader from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { Head, Link } from '@inertiajs/react';

interface MessageRow {
    id: number;
    sender_name: string;
    sender_school: string;
    sender_role: string;
    excerpt: string;
    received_at: string;
    read: boolean;
}

export default function InboxIndex({ messages }: { messages: MessageRow[] }) {
    return (
        <AppLayout>
            <Head title="Inbox" />
            <PageHeader kicker="Inbox" title="Messages from coaches">
                Messages sent through your shared link. Each one is also emailed to you.
            </PageHeader>

            {messages.length === 0 ? (
                <p className="text-muted-foreground reveal border-y py-8 [--i:1]">
                    Nothing here yet. When a coach writes to you from your profile page, it will show up here.
                </p>
            ) : (
                <ul className="border-t">
                    {messages.map((m, i) => (
                        <li key={m.id} className="reveal border-b" style={{ ['--i' as string]: Math.min(i + 1, 8) }}>
                            <Link href={route('inbox.show', m.id)} className="hover:bg-surface grid gap-1 py-4 sm:grid-cols-[1fr_auto] sm:gap-x-6">
                                <p className={cn('flex items-center gap-2', !m.read && 'font-semibold')}>
                                    {!m.read && (
                                        <span className="bg-mark text-mark-foreground kicker rounded-sm px-1.5 py-0.5 !text-[0.65rem]">New</span>
                                    )}
                                    {m.sender_name}
                                    <span className="text-muted-foreground font-normal">· {m.sender_school}</span>
                                </p>
                                <time dateTime={m.received_at} className="text-muted-foreground numeral text-sm sm:text-right">
                                    {formatDate(m.received_at)}
                                </time>
                                <p className="text-muted-foreground text-sm sm:col-span-2">{m.excerpt}</p>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </AppLayout>
    );
}
