import Field from '@/components/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({ token, email, password: '', password_confirmation: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.store'), { onFinish: () => reset('password', 'password_confirmation') });
    };

    return (
        <AuthLayout title="Choose a new password">
            <Head title="New password" />

            <form className="grid gap-5" onSubmit={submit} noValidate>
                <Field label="Email" htmlFor="email" error={errors.email}>
                    <Input id="email" type="email" autoComplete="email" value={data.email} readOnly />
                </Field>
                <Field label="New password" htmlFor="password" hint="At least 10 characters." error={errors.password}>
                    <Input
                        id="password"
                        type="password"
                        autoFocus
                        autoComplete="new-password"
                        aria-describedby="password-hint"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                    />
                </Field>
                <Field label="New password again" htmlFor="password_confirmation" error={errors.password_confirmation}>
                    <Input
                        id="password_confirmation"
                        type="password"
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                    />
                </Field>
                <Button type="submit" size="lg" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                    Save new password
                </Button>
            </form>
        </AuthLayout>
    );
}
