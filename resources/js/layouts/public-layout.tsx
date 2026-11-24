import FlashStatus from '@/components/flash-status';
import SiteFooter from '@/components/site-footer';
import { buttonVariants } from '@/components/ui/button';
import Wordmark from '@/components/wordmark';
import { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ReactNode } from 'react';

export default function PublicLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <div className="flex min-h-dvh flex-col">
            <a
                href="#main"
                className="bg-primary text-primary-foreground sr-only z-50 rounded-md px-3 py-2 focus:not-sr-only focus:fixed focus:top-2 focus:left-2"
            >
                Skip to content
            </a>
            <header className="no-print border-b">
                <div className="mx-auto flex h-14 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6">
                    <Link href={route('home')}>
                        <Wordmark />
                    </Link>
                    <nav aria-label="Account" className="flex items-center gap-1 sm:gap-2">
                        {auth?.user ? (
                            <Link href={route('dashboard')} className={buttonVariants({ size: 'sm' })}>
                                My profile
                            </Link>
                        ) : (
                            <>
                                <Link href={route('login')} className={buttonVariants({ variant: 'ghost', size: 'sm' })}>
                                    Log in
                                </Link>
                                <Link href={route('register')} className={buttonVariants({ size: 'sm' })}>
                                    Sign up
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>
            <main id="main" className="flex-1">
                {children}
            </main>
            <SiteFooter />
            <FlashStatus />
        </div>
    );
}
