import ConfirmAction from '@/components/confirm-action';
import Notice from '@/components/notice';
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Mail, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Message {
    id: number;
    sender_name: string;
    sender_school: string;
    sender_role: string;
    sender_email: string;
    body: string;
    received_at: string;
}

export default function InboxShow({ message }: { message: Message }) {
    const [busy, setBusy] = useState(false);

    const remove = () => {
        setBusy(true);
        router.delete(route('inbox.destroy', message.id), { onFinish: () => setBusy(false) });
    };

    return (
        <AppLayout>
            <Head title={`Message from ${message.sender_name}`} />

            <Link
                href={route('inbox.index')}
                className="text-muted-foreground hover:text-foreground mb-6 inline-flex min-h-11 items-center gap-2 text-sm font-medium"
            >
                <ArrowLeft className="size-4" aria-hidden="true" />
                Inbox
            </Link>

            <article className="reveal">
                <p className="kicker">
                    Received <time dateTime={message.received_at}>{formatDate(message.received_at)}</time>
                </p>
                <h1 className="mt-3 text-3xl leading-tight font-semibold">{message.sender_name}</h1>
                <dl className="mt-5 border-t">
                    {[
                        ['Role', message.sender_role],
                        ['School', message.sender_school],
                        ['Email', message.sender_email],
                    ].map(([label, value]) => (
                        <div key={label} className="grid grid-cols-[6rem_1fr] gap-4 border-b py-3">
                            <dt className="kicker pt-1">{label}</dt>
                            <dd className="break-words">{value}</dd>
                        </div>
                    ))}
                </dl>
                <p className="mt-6 max-w-2xl text-lg leading-relaxed whitespace-pre-line">{message.body}</p>
            </article>

            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href={`mailto:${message.sender_email}`} className={buttonVariants()}>
                    <Mail aria-hidden="true" />
                    Reply by email
                </a>
                <ConfirmAction
                    trigger={
                        <Button variant="outline">
                            <Trash2 aria-hidden="true" />
                            Delete message
                        </Button>
                    }
                    title="Delete this message?"
                    description="It is removed from your inbox for good. The copy in your email is not affected."
                    confirmLabel="Delete"
                    destructive
                    busy={busy}
                    onConfirm={remove}
                />
            </div>

            <Notice title="Before you reply" className="mt-10">
                We do not check who sends messages, so make sure this person is who they say they are. A reply comes from your own email address, so
                they will see it. If you are not sure you are allowed to talk to this coach yet, ask your school&apos;s compliance office first.
            </Notice>
        </AppLayout>
    );
}
