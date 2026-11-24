import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useEffect, useState } from 'react';

/** Shows the server's "Saved." style message for a few seconds and announces it to screen readers. */
export default function FlashStatus() {
    const page = usePage<SharedData>();
    const status = page.props.flash?.status;
    const [visible, setVisible] = useState<string | null>(null);

    useEffect(() => {
        if (!status || status === 'contact-sent' || status === 'verification-link-sent') {
            return;
        }

        setVisible(status);
        const timer = window.setTimeout(() => setVisible(null), Math.max(5000, status.length * 90));

        return () => window.clearTimeout(timer);
    }, [status, page]);

    return (
        <div
            aria-live="polite"
            role="status"
            className="no-print pointer-events-none fixed inset-x-0 bottom-20 z-50 flex justify-center px-4 md:bottom-6"
        >
            {visible && (
                <p className="bg-foreground text-background reveal pointer-events-auto flex max-w-md items-start gap-2 rounded-md px-4 py-3 text-sm font-medium shadow-lg">
                    <Check className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    {visible}
                </p>
            )}
        </div>
    );
}
