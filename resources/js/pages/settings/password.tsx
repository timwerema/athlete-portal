import Field from '@/components/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import SettingsLayout from '@/layouts/settings-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function Password() {
    const { data, setData, put, errors, processing, reset } = useForm({ current_password: '', password: '', password_confirmation: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <SettingsLayout title="Password" intro="Use a long password you do not use anywhere else.">
            <Head title="Password" />
            <form onSubmit={submit} noValidate className="grid max-w-md gap-5">
                <Field label="Current password" htmlFor="current_password" error={errors.current_password}>
                    <Input
                        id="current_password"
                        type="password"
                        autoComplete="current-password"
                        value={data.current_password}
                        onChange={(e) => setData('current_password', e.target.value)}
                    />
                </Field>
                <Field label="New password" htmlFor="password" hint="At least 10 characters." error={errors.password}>
                    <Input
                        id="password"
                        type="password"
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
                <Button type="submit" className="justify-self-start" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                    Change password
                </Button>
            </form>
        </SettingsLayout>
    );
}
