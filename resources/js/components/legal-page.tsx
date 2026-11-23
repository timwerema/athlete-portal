import PublicLayout from '@/layouts/public-layout';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';

export function LegalSection({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="grid gap-3 border-t py-8 md:grid-cols-[14rem_1fr] md:gap-8">
            <h2 className="text-lg font-semibold">{title}</h2>
            <div className="text-muted-foreground [&_strong]:text-foreground max-w-2xl space-y-3 leading-relaxed [&_ul]:list-disc [&_ul]:space-y-2 [&_ul]:pl-5">
                {children}
            </div>
        </section>
    );
}

export default function LegalPage({ title, updated, children }: { title: string; updated: string; children: ReactNode }) {
    return (
        <PublicLayout>
            <Head title={title} />
            <article className="mx-auto max-w-5xl px-4 py-12 sm:px-6 sm:py-16">
                <p className="kicker">Draft · last edited {updated}</p>
                <h1 className="mt-3 text-4xl font-semibold sm:text-5xl">{title}</h1>
                <p className="border-primary mt-6 max-w-2xl border-l-2 py-1 pl-4 leading-relaxed">
                    <strong>This is a draft.</strong> It describes how the service works today in plain words. It has not been reviewed by a lawyer
                    and will be replaced by a reviewed version before the service opens to the public.
                </p>
                <div className="mt-10">{children}</div>
            </article>
        </PublicLayout>
    );
}
