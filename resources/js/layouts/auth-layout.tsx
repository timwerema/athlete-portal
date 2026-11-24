import Wordmark from '@/components/wordmark';
import { Link } from '@inertiajs/react';
import { ReactNode } from 'react';

export default function AuthLayout({ title, description, children }: { title: string; description?: ReactNode; children: ReactNode }) {
    return (
        <div className="flex min-h-dvh flex-col">
            <header className="border-b">
                <div className="mx-auto flex h-14 max-w-5xl items-center px-4 sm:px-6">
                    <Link href={route('home')}>
                        <Wordmark />
                    </Link>
                </div>
            </header>
            <main className="mx-auto w-full max-w-md flex-1 px-4 py-10 sm:py-16">
                <div className="reveal">
                    <h1 className="text-3xl leading-tight font-semibold">{title}</h1>
                    {description && <p className="text-muted-foreground mt-3 leading-relaxed">{description}</p>}
                </div>
                <div className="reveal mt-8 [--i:1]">{children}</div>
            </main>
        </div>
    );
}
