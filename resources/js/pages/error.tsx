import { buttonVariants } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';

const MESSAGES: Record<number, [string, string]> = {
    403: ['Not allowed', 'You do not have access to this page.'],
    404: ['Page not found', 'There is nothing at this address. It may have been moved or removed.'],
    429: ['Slow down a little', 'Too many requests came from this device. Please wait a minute and try again.'],
    500: ['Something went wrong', 'That is our fault, not yours. Please try again in a moment.'],
    503: ['Back soon', 'The site is being updated. Please try again in a few minutes.'],
};

export default function ErrorPage({ status }: { status: number }) {
    const [title, text] = MESSAGES[status] ?? MESSAGES[500];

    return (
        <main className="mx-auto flex min-h-dvh max-w-xl flex-col justify-center px-4 py-16">
            <Head title={title} />
            <p className="kicker">Error {status}</p>
            <h1 className="mt-3 text-4xl font-semibold">{title}</h1>
            <p className="text-muted-foreground mt-4 text-lg leading-relaxed">{text}</p>
            <p className="mt-8">
                <Link href="/" className={buttonVariants({ variant: 'outline' })}>
                    Go to the home page
                </Link>
            </p>
        </main>
    );
}
