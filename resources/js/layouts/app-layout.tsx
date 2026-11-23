import FlashStatus from '@/components/flash-status';
import Wordmark from '@/components/wordmark';
import { cn } from '@/lib/utils';
import { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Inbox, LayoutList, LogOut, Send, Settings, UserRound } from 'lucide-react';
import { ReactNode } from 'react';

const NAV = [
    { label: 'Overview', route: 'dashboard', match: '/dashboard', icon: LayoutList },
    { label: 'Profile', route: 'profile.index', match: '/profile', icon: UserRound },
    { label: 'Sharing', route: 'sharing.index', match: '/sharing', icon: Send },
    { label: 'Inbox', route: 'inbox.index', match: '/inbox', icon: Inbox },
    { label: 'Settings', route: 'account.edit', match: '/settings', icon: Settings },
] as const;

/** Signed-in shell: a slim top bar on desktop, thumb-reach tabs on a phone. */
export default function AppLayout({ children, width = 'default' }: { children: ReactNode; width?: 'default' | 'wide' }) {
    const { url, props } = usePage<SharedData>();
    const unread = props.unread ?? 0;

    return (
        <div className="min-h-dvh pb-24 md:pb-0">
            <a
                href="#main"
                className="bg-primary text-primary-foreground sr-only z-50 rounded-md px-3 py-2 focus:not-sr-only focus:fixed focus:top-2 focus:left-2"
            >
                Skip to content
            </a>

            <header className="border-b">
                <div className="mx-auto flex h-14 max-w-5xl items-center justify-between gap-6 px-4 sm:px-6">
                    <Link href={route('dashboard')} aria-label="Overview">
                        <Wordmark />
                    </Link>

                    <nav aria-label="Main" className="hidden items-center gap-1 md:flex">
                        {NAV.map((item) => {
                            const active = url.startsWith(item.match);

                            return (
                                <Link
                                    key={item.route}
                                    href={route(item.route)}
                                    aria-current={active ? 'page' : undefined}
                                    className={cn(
                                        'rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                        active ? 'bg-secondary text-foreground' : 'text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    {item.label}
                                    {item.route === 'inbox.index' && unread > 0 && (
                                        <span className="bg-mark text-mark-foreground numeral ml-2 rounded-sm px-1.5 py-0.5 text-xs font-medium">
                                            {unread}
                                            <span className="sr-only"> unread</span>
                                        </span>
                                    )}
                                </Link>
                            );
                        })}
                    </nav>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-muted-foreground hover:text-foreground inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm font-medium"
                    >
                        <LogOut className="size-4" aria-hidden="true" />
                        Log out
                    </Link>
                </div>
            </header>

            <main id="main" className={cn('mx-auto px-4 py-8 sm:px-6 sm:py-12', width === 'wide' ? 'max-w-5xl' : 'max-w-3xl')}>
                {children}
            </main>

            <nav aria-label="Main" className="bg-background fixed inset-x-0 bottom-0 z-40 border-t pb-[env(safe-area-inset-bottom)] md:hidden">
                <ul className="mx-auto grid max-w-md grid-cols-5">
                    {NAV.map((item) => {
                        const active = url.startsWith(item.match);
                        const Icon = item.icon;

                        return (
                            <li key={item.route}>
                                <Link
                                    href={route(item.route)}
                                    aria-current={active ? 'page' : undefined}
                                    className={cn(
                                        'relative flex min-h-16 flex-col items-center justify-center gap-1 text-[0.7rem] font-medium',
                                        active ? 'text-foreground' : 'text-muted-foreground',
                                    )}
                                >
                                    {active && <span className="bg-primary absolute inset-x-4 top-0 h-0.5" aria-hidden="true" />}
                                    <span className="relative">
                                        <Icon className="size-5" aria-hidden="true" />
                                        {item.route === 'inbox.index' && unread > 0 && (
                                            <span className="bg-mark text-mark-foreground numeral absolute -top-1.5 -right-3 rounded-sm px-1 text-[0.65rem] leading-4">
                                                {unread}
                                                <span className="sr-only"> unread</span>
                                            </span>
                                        )}
                                    </span>
                                    {item.label}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            <FlashStatus />
        </div>
    );
}
