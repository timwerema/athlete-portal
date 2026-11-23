import PageHeader from '@/components/page-header';
import { buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Completeness } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Check, ChevronRight, Eye } from 'lucide-react';

interface ProfileIndexProps {
    sections: Record<string, string>;
    completeness: Completeness;
    displayName: string | null;
}

export default function ProfileIndex({ sections, completeness }: ProfileIndexProps) {
    const keys = Object.keys(sections);

    const state = (section: string) => {
        const items = completeness.items.filter((item) => item.section === section);
        const done = items.filter((item) => item.done).length;
        const onlyOptionalLeft = items.filter((item) => !item.done).every((item) => item.optional);

        return { done, total: items.length, complete: items.length > 0 && done === items.length, onlyOptionalLeft };
    };

    return (
        <AppLayout>
            <Head title="Profile" />
            <PageHeader kicker="Profile" title="Build your profile">
                Fill in the parts in any order. Each one saves on its own, so you can stop and come back. Nothing is visible to anyone until you
                create a link.
            </PageHeader>

            <ol className="border-t">
                {keys.map((key, i) => {
                    const s = state(key);

                    return (
                        <li key={key} className="reveal border-b" style={{ ['--i' as string]: Math.min(i, 8) }}>
                            <Link
                                href={route('profile.section', key)}
                                className="hover:bg-surface group grid min-h-16 grid-cols-[2.5rem_1fr_auto] items-center gap-3 py-3"
                            >
                                <span className="numeral text-muted-foreground text-sm">{String(i + 1).padStart(2, '0')}</span>
                                <span>
                                    <span className="block text-lg font-semibold">{sections[key]}</span>
                                    <span className="text-muted-foreground block text-sm">
                                        {s.complete ? 'Done' : s.done > 0 ? 'Started' : s.onlyOptionalLeft ? 'Optional' : 'Not started'}
                                    </span>
                                </span>
                                <span className="flex items-center gap-3">
                                    {s.complete && (
                                        <span className="bg-mark text-mark-foreground inline-flex size-6 items-center justify-center rounded-full">
                                            <Check className="size-4" aria-hidden="true" />
                                        </span>
                                    )}
                                    <ChevronRight
                                        className="text-muted-foreground size-5 transition-transform group-hover:translate-x-1"
                                        aria-hidden="true"
                                    />
                                </span>
                            </Link>
                        </li>
                    );
                })}
            </ol>

            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                <Link href={route('profile.preview')} className={buttonVariants({ variant: 'outline' })}>
                    <Eye aria-hidden="true" />
                    See it as a coach would
                </Link>
                <Link href={route('sharing.index')} className={buttonVariants()}>
                    Sharing and privacy
                </Link>
            </div>
        </AppLayout>
    );
}
