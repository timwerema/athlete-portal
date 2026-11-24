import Field from '@/components/field';
import Notice from '@/components/notice';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import SettingsLayout from '@/layouts/settings-layout';
import { SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

export default function Account({ pendingEmail }: { pendingEmail: string | null }) {
    const { auth } = usePage<SharedData>().props;
    const currentEmail = auth.user?.email ?? '';
    const { data, setData, patch, errors, processing, reset } = useForm({ name: auth.user?.name ?? '', email: currentEmail, current_password: '' });
    const [cancelling, setCancelling] = useState(false);
    const emailChanging = data.email.trim().toLowerCase() !== currentEmail;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('account.update'), {
            preserveScroll: true,
            onSuccess: () => {
                reset('current_password');
                setData('email', currentEmail);
            },
        });
    };

    const cancel = () => {
        setCancelling(true);
        router.delete(route('account.email.cancel'), { preserveScroll: true, onFinish: () => setCancelling(false) });
    };

    return (
        <SettingsLayout title="Account" intro="Your log-in details. Coaches never see your email address.">
            <Head title="Account" />

            {pendingEmail && (
                <Notice title="Waiting for you to confirm a new email" className="mb-8 max-w-md">
                    <p>
                        You asked to change your email to <strong className="text-foreground break-all">{pendingEmail}</strong>. If that address can
                        be used, a confirmation link was sent to it. Until you open the link, you still log in with{' '}
                        <span className="break-all">{currentEmail}</span>.
                    </p>
                    <Button variant="link" className="mt-2 text-sm" disabled={cancelling} onClick={cancel}>
                        Cancel this change
                    </Button>
                </Notice>
            )}

            <form onSubmit={submit} noValidate className="grid max-w-md gap-5">
                <Field label="Name" htmlFor="name" hint="The name on your profile is set separately, in Profile." error={errors.name}>
                    <Input
                        id="name"
                        autoComplete="name"
                        aria-describedby="name-hint"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                    />
                </Field>
                <Field
                    label="Email"
                    htmlFor="email"
                    hint="To change it, enter the new address. We send a link there, and the change happens when you open it."
                    error={errors.email}
                >
                    <Input
                        id="email"
                        type="email"
                        autoComplete="email"
                        inputMode="email"
                        aria-describedby="email-hint"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                </Field>
                {emailChanging && (
                    <Field label="Your password" htmlFor="current_password" hint="Needed to change your email." error={errors.current_password}>
                        <Input
                            id="current_password"
                            type="password"
                            autoComplete="current-password"
                            aria-describedby="current_password-hint"
                            value={data.current_password}
                            onChange={(e) => setData('current_password', e.target.value)}
                        />
                    </Field>
                )}
                <Button type="submit" className="justify-self-start" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                    Save
                </Button>
            </form>
        </SettingsLayout>
    );
}
