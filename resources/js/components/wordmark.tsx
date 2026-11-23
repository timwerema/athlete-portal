import { cn } from '@/lib/utils';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

/** The product name with a small pitch mark. The name comes from APP_NAME, so renaming is one setting. */
export default function Wordmark({ className }: { className?: string }) {
    const { name } = usePage<SharedData>().props;

    return (
        <span className={cn('font-display inline-flex items-center gap-2 text-base font-semibold tracking-tight', className)}>
            <svg viewBox="0 0 20 20" className="size-5 shrink-0" aria-hidden="true">
                <rect x="1.25" y="1.25" width="17.5" height="17.5" fill="none" stroke="currentColor" strokeWidth="1.5" />
                <path d="M10 1.25v17.5" stroke="currentColor" strokeWidth="1.5" />
                <circle cx="10" cy="10" r="2.6" className="fill-primary" />
            </svg>
            {name ?? 'Athlete Profiles'}
        </span>
    );
}
