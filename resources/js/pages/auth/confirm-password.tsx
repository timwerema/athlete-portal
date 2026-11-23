import Field from '@/components/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({ password: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.confirm'), { onFinish: () => reset('password') });
    };

    return (
        <AuthLayout title="Confirm your password" description="Please enter your password again before continuing.">
            <Head title="Confirm password" />
            <form className="grid gap-5" onSubmit={submit} noValidate>
                <Field label="Password" htmlFor="password" error={errors.password}>
                    <Input
                        id="password"
                        type="password"
                        autoFocus
                        autoComplete="current-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                    />
                </Field>
                <Button type="submit" size="lg" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                    Confirm
                </Button>
            </form>
        </AuthLayout>
    );
}
