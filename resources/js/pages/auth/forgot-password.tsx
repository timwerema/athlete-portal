import Field from '@/components/field';
import Notice from '@/components/notice';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <AuthLayout title="Reset your password" description="Enter your email and we will send a link to choose a new password.">
            <Head title="Reset password" />

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
                <Button type="submit" size="lg" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                    Send reset link
                </Button>
                <p className="text-sm">
                    <TextLink href={route('login')}>Back to log in</TextLink>
                </p>
            </form>
        </AuthLayout>
    );
}
