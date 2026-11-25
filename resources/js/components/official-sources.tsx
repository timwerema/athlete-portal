import { cn } from '@/lib/utils';
import { ExternalLink } from 'lucide-react';

/**
 * Links to the governing bodies' own pages. We link, we do not summarise:
 * rules change and differ by division. Add a link here only after a person
 * has opened it and confirmed it is the right official page, and update the
 * date below when the list is re-checked.
 */
export const OFFICIAL_SOURCES_CHECKED = '25 November 2026';

export const OFFICIAL_SOURCES = [
    {
        body: 'NCAA',
        label: 'Transfer rules and eligibility',
        host: 'ncaa.org',
        url: 'https://www.ncaa.org/eligibility-center/transfer-rules-and-eligibility/',
    },
    {
        body: 'NAIA',
        label: 'Eligibility Center',
        host: 'play.mynaia.org',
        url: 'https://play.mynaia.org/',
    },
] as const;

export default function OfficialSources({ className }: { className?: string }) {
    return (
        <section aria-labelledby="official-sources" className={cn('reveal', className)}>
            <h2 id="official-sources" className="text-xl font-semibold">
                Official sources
            </h2>
            <p className="text-muted-foreground mt-2 max-w-xl text-sm leading-relaxed">
                For the actual rules, go to the organisations that set them, and ask your school&apos;s compliance office about your own situation. We
                are not connected to these organisations.
            </p>
            <ul className="mt-4 border-t">
                {OFFICIAL_SOURCES.map((source) => (
                    <li key={source.url} className="border-b">
                        <a
                            href={source.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="hover:bg-surface group grid min-h-14 grid-cols-[4rem_1fr_auto] items-center gap-3 py-3"
                        >
                            <span className="kicker">{source.body}</span>
                            <span>
                                <span className="font-medium underline underline-offset-4">{source.label}</span>
                                <span className="text-muted-foreground block text-sm">
                                    {source.host}
                                    <span className="sr-only"> (opens in a new tab)</span>
                                </span>
                            </span>
                            <ExternalLink className="text-muted-foreground size-4" aria-hidden="true" />
                        </a>
                    </li>
                ))}
            </ul>
            <p className="text-muted-foreground mt-3 text-xs">Links last checked {OFFICIAL_SOURCES_CHECKED}.</p>
        </section>
    );
}
