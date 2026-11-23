import Field from '@/components/field';
import Notice from '@/components/notice';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function Login({ status }: { status?: string }) {
    const { data, setData, post, processing, errors, reset } = useForm<{ email: string; password: string; remember: boolean }>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    return (
        <AuthLayout title="Log in" description="Welcome back. Your profile stays private until you share it.">
            <Head title="Log in" />

            {status && (
                <Notice className="mb-6">
                    <p role="status">{status}</p>
                </Notice>
            )}

            <form className="grid gap-5" onSubmit={submit} noValidate>
                <Field label="Email" htmlFor="email" error={errors.email}>
                    <Input
                        id="email"
                        type="email"
                        required
                        autoFocus
                        autoComplete="email"
                        inputMode="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                </Field>

                <Field label="Password" htmlFor="password" error={errors.password}>
                    <Input
                        id="password"
                        type="password"
                        required
                        autoComplete="current-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                    />
                </Field>

                <label className="flex min-h-11 items-center gap-3 text-sm">
                    <input
                        type="checkbox"
                        className="accent-primary size-5"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                    />
                    Keep me logged in on this device
                </label>

                <Button type="submit" size="lg" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                    Log in
                </Button>

                <p className="text-muted-foreground flex flex-wrap justify-between gap-3 text-sm">
                    <TextLink href={route('password.request')}>Forgot your password?</TextLink>
                    <TextLink href={route('register')}>Create an account</TextLink>
                </p>
            </form>
        </AuthLayout>
    );
}
