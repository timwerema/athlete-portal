import Field from '@/components/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import SettingsLayout from '@/layouts/settings-layout';
import { SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function Account() {
    const { auth } = usePage<SharedData>().props;
    const { data, setData, patch, errors, processing } = useForm({ name: auth.user?.name ?? '', email: auth.user?.email ?? '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('account.update'), { preserveScroll: true });
    };

    return (
        <SettingsLayout title="Account" intro="Your log-in details. Coaches never see your email address.">
            <Head title="Account" />
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
                <Field label="Email" htmlFor="email" hint="If you change it, we will ask you to confirm the new address." error={errors.email}>
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
                <Button type="submit" className="justify-self-start" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                    Save
                </Button>
            </form>
        </SettingsLayout>
    );
}
