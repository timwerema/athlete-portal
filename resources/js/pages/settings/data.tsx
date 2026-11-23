import ConfirmAction from '@/components/confirm-action';
import Field from '@/components/field';
import { Button, buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import SettingsLayout from '@/layouts/settings-layout';
import { Head, useForm } from '@inertiajs/react';
import { Download, Trash2 } from 'lucide-react';

export default function Data() {
    const form = useForm({ password: '' });

    const destroy = () => {
        form.delete(route('data.destroy'), { preserveScroll: true, onError: () => form.reset() });
    };

    return (
        <SettingsLayout title="Your data" intro="Everything here belongs to you. Take a copy or remove it whenever you want.">
            <Head title="Your data" />

            <section aria-labelledby="download" className="border-t py-8">
                <h2 id="download" className="text-xl font-semibold">
                    Download my data
                </h2>
                <p className="text-muted-foreground mt-2 max-w-xl leading-relaxed">
                    A zip file with everything we hold about you: your account details, your whole profile, your link history and visit counts, the
                    messages you received, and your photo.
                </p>
                {/* A plain link, so the browser handles the file download itself. */}
                <a href={route('data.export')} className={buttonVariants({ variant: 'outline', className: 'mt-5' })}>
                    <Download aria-hidden="true" />
                    Download zip
                </a>
            </section>

            <section aria-labelledby="delete" className="border-t py-8">
                <h2 id="delete" className="text-xl font-semibold">
                    Delete my account
                </h2>
                <p className="text-muted-foreground mt-2 max-w-xl leading-relaxed">
                    This removes your account, profile, photo, share links, visit counts and messages. Your link stops working immediately. It cannot
                    be undone.
                </p>
                <ConfirmAction
                    trigger={
                        <Button variant="destructive" className="mt-5">
                            <Trash2 aria-hidden="true" />
                            Delete my account
                        </Button>
                    }
                    title="Delete your account for good?"
                    description="Everything is removed and cannot be brought back. Enter your password to confirm."
                    confirmLabel="Delete everything"
                    destructive
                    busy={form.processing}
                    onConfirm={destroy}
                >
                    <Field label="Your password" htmlFor="delete-password" error={form.errors.password}>
                        <Input
                            id="delete-password"
                            type="password"
                            autoComplete="current-password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                        />
                    </Field>
                </ConfirmAction>
            </section>
        </SettingsLayout>
    );
}
