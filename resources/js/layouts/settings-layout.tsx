import PageHeader from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { ReactNode } from 'react';

const TABS = [
    { label: 'Account', route: 'account.edit', match: '/settings/account' },
    { label: 'Password', route: 'password.edit', match: '/settings/password' },
    { label: 'Two-step login', route: 'two-factor.edit', match: '/settings/two-factor' },
    { label: 'Appearance', route: 'appearance', match: '/settings/appearance' },
    { label: 'Your data', route: 'data.edit', match: '/settings/data' },
] as const;

export default function SettingsLayout({ title, intro, children }: { title: string; intro?: ReactNode; children: ReactNode }) {
    const { url } = usePage();

    return (
        <AppLayout>
            <nav aria-label="Settings" className="-mx-4 mb-8 overflow-x-auto border-b px-4 sm:mx-0 sm:px-0">
                <ul className="flex min-w-max gap-5">
                    {TABS.map((tab) => {
                        const active = url.startsWith(tab.match);

                        return (
                            <li key={tab.route}>
                                <Link
                                    href={route(tab.route)}
                                    aria-current={active ? 'page' : undefined}
                                    className={cn(
                                        '-mb-px inline-flex min-h-11 items-center border-b-2 text-sm font-medium',
                                        active ? 'border-primary text-foreground' : 'text-muted-foreground hover:text-foreground border-transparent',
                                    )}
                                >
                                    {tab.label}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>
            <PageHeader kicker="Settings" title={title}>
                {intro}
            </PageHeader>
            <div className="reveal [--i:1]">{children}</div>
        </AppLayout>
    );
}
