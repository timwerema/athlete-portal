import { Head } from '@inertiajs/react';

export default function Unavailable() {
    return (
        <main className="mx-auto flex min-h-dvh max-w-xl flex-col justify-center px-4 py-16">
            <Head title="Link not active" />
            <p className="kicker">Athlete profile</p>
            <h1 className="mt-3 text-4xl font-semibold">This link is not active</h1>
            <p className="text-muted-foreground mt-4 text-lg leading-relaxed">
                The athlete may have switched it off or replaced it with a new one. If you were expecting to see a profile, ask them to send you their
                current link.
            </p>
        </main>
    );
}
