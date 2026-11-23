import Field from '@/components/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

export default function TwoFactorChallenge() {
    const [useRecovery, setUseRecovery] = useState(false);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({ code: '', recovery_code: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('two-factor.challenge'), { onError: () => reset() });
    };

    const toggle = () => {
        setUseRecovery((value) => !value);
        reset();
        clearErrors();
    };

    return (
        <AuthLayout
            title="One more step"
            description={useRecovery ? 'Enter one of the recovery codes you saved.' : 'Enter the 6-digit code from your authenticator app.'}
        >
            <Head title="Two-step login" />

            <form className="grid gap-5" onSubmit={submit} noValidate>
                {useRecovery ? (
                    <Field label="Recovery code" htmlFor="recovery_code" error={errors.recovery_code}>
                        <Input
                            id="recovery_code"
                            autoFocus
                            autoComplete="off"
                            autoCapitalize="none"
                            className="numeral"
                            value={data.recovery_code}
                            onChange={(e) => setData('recovery_code', e.target.value)}
                        />
                    </Field>
                ) : (
                    <Field label="6-digit code" htmlFor="code" error={errors.code}>
                        <Input
                            id="code"
                            autoFocus
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            maxLength={7}
                            className="numeral text-xl tracking-[0.3em]"
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value)}
                        />
                    </Field>
                )}

                <Button type="submit" size="lg" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                    Continue
                </Button>

                <Button variant="link" className="justify-self-start" onClick={toggle}>
                    {useRecovery ? 'Use my authenticator app instead' : 'Use a recovery code instead'}
                </Button>
            </form>
        </AuthLayout>
    );
}
