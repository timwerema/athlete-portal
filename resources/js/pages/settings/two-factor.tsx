import Field from '@/components/field';
import Notice from '@/components/notice';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import SettingsLayout from '@/layouts/settings-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

interface TwoFactorProps {
    enabled: boolean;
    setup: { qr: string; secret: string } | null;
    recoveryCodes: string[] | null;
    recoveryCodesLeft: number | null;
}

function PasswordAction({
    routeName,
    method,
    label,
    variant,
}: {
    routeName: string;
    method: 'post' | 'delete';
    label: string;
    variant?: 'default' | 'outline' | 'destructive';
}) {
    const form = useForm({ password: '' });
    const id = `password-${routeName}-${method}`;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.submit(method, route(routeName), { preserveScroll: true, onFinish: () => form.reset() });
    };

    return (
        <form onSubmit={submit} noValidate className="grid max-w-md gap-4">
            <Field label="Your password" htmlFor={id} error={form.errors.password}>
                <Input
                    id={id}
                    type="password"
                    autoComplete="current-password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                />
            </Field>
            <Button type="submit" variant={variant} className="justify-self-start" disabled={form.processing}>
                {form.processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                {label}
            </Button>
        </form>
    );
}

function Confirm({ setup }: { setup: { qr: string; secret: string } }) {
    const form = useForm({ code: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('two-factor.confirm'), { preserveScroll: true, onError: () => form.reset() });
    };

    return (
        <div className="grid gap-6">
            <ol className="grid gap-6">
                <li>
                    <p className="font-semibold">1. Scan this with an authenticator app</p>
                    <p className="text-muted-foreground mt-1 text-sm">
                        For example Google Authenticator, Microsoft Authenticator, 1Password or Authy.
                    </p>
                    <img
                        src={setup.qr}
                        alt="QR code to scan with your authenticator app"
                        width={220}
                        height={220}
                        className="mt-4 rounded-md border bg-white p-2"
                    />
                    <p className="text-muted-foreground mt-3 text-sm">
                        Cannot scan? Type this key into the app instead:
                        <span className="numeral text-foreground mt-1 block text-sm break-all select-all">{setup.secret}</span>
                    </p>
                </li>
                <li>
                    <p className="font-semibold">2. Enter the 6-digit code the app shows</p>
                    <form onSubmit={submit} noValidate className="mt-3 grid max-w-xs gap-4">
                        <Field label="Code" htmlFor="code" error={form.errors.code}>
                            <Input
                                id="code"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                maxLength={7}
                                className="numeral text-xl tracking-[0.3em]"
                                value={form.data.code}
                                onChange={(e) => form.setData('code', e.target.value)}
                            />
                        </Field>
                        <Button type="submit" className="justify-self-start" disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                            Turn on two-step login
                        </Button>
                    </form>
                </li>
            </ol>
        </div>
    );
}

export default function TwoFactor({ enabled, setup, recoveryCodes, recoveryCodesLeft }: TwoFactorProps) {
    return (
        <SettingsLayout
            title="Two-step login"
            intro="After your password, you also enter a code from an app on your phone. It stops someone who learns your password from getting in."
        >
            <Head title="Two-step login" />

            {recoveryCodes && (
                <section aria-labelledby="codes" className="bg-surface mb-10 rounded-md border p-5">
                    <h2 id="codes" className="text-xl font-semibold">
                        Save your recovery codes now
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                        If you lose your phone, one of these gets you in. Each works once. We will not show them again, so write them down or keep
                        them in a password manager.
                    </p>
                    <ul className="numeral mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm select-all sm:max-w-sm">
                        {recoveryCodes.map((code) => (
                            <li key={code}>{code}</li>
                        ))}
                    </ul>
                </section>
            )}

            {enabled ? (
                <div className="grid gap-10">
                    <p className="text-lg">
                        <span className="bg-mark text-mark-foreground kicker mr-3 rounded-sm px-2 py-1">On</span>
                        Two-step login is protecting your account.
                        {recoveryCodesLeft !== null && (
                            <span className="text-muted-foreground block pt-2 text-sm">
                                Recovery codes left: <span className="numeral">{recoveryCodesLeft}</span>
                            </span>
                        )}
                    </p>
                    <section aria-labelledby="new-codes" className="border-t pt-8">
                        <h2 id="new-codes" className="mb-1 text-xl font-semibold">
                            Make new recovery codes
                        </h2>
                        <p className="text-muted-foreground mb-4 text-sm">The old codes stop working.</p>
                        <PasswordAction routeName="two-factor.recovery-codes" method="post" label="Make new codes" variant="outline" />
                    </section>
                    <section aria-labelledby="turn-off" className="border-t pt-8">
                        <h2 id="turn-off" className="mb-4 text-xl font-semibold">
                            Turn it off
                        </h2>
                        <PasswordAction routeName="two-factor.destroy" method="delete" label="Turn off two-step login" variant="destructive" />
                    </section>
                </div>
            ) : setup ? (
                <Confirm setup={setup} />
            ) : (
                <div className="grid gap-6">
                    <Notice>You will need an authenticator app on your phone. Enter your password to start.</Notice>
                    <PasswordAction routeName="two-factor.store" method="post" label="Set up two-step login" />
                </div>
            )}
        </SettingsLayout>
    );
}
