import Field from '@/components/field';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function Register({ minimumAge }: { minimumAge: number }) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        name: string;
        email: string;
        date_of_birth: string;
        password: string;
        password_confirmation: string;
        terms: boolean;
    }>({
        name: '',
        email: '',
        date_of_birth: '',
        password: '',
        password_confirmation: '',
        terms: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), { onFinish: () => reset('password', 'password_confirmation') });
    };

    return (
        <AuthLayout
            title="Create your account"
            description={`For current college athletes aged ${minimumAge} or over. Your profile is private until you choose to share it.`}
        >
            <Head title="Sign up" />

            <form className="grid gap-5" onSubmit={submit} noValidate>
                <Field label="Your name" htmlFor="name" error={errors.name}>
                    <Input id="name" required autoFocus autoComplete="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                </Field>

                <Field
                    label="Email"
                    htmlFor="email"
                    hint="Use a personal email, not a team or school one that others can read. Coaches never see it."
                    error={errors.email}
                >
                    <Input
                        id="email"
                        type="email"
                        required
                        autoComplete="email"
                        inputMode="email"
                        aria-describedby="email-hint"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                </Field>

                <Field
                    label="Date of birth"
                    htmlFor="date_of_birth"
                    hint={`Only used to check you are ${minimumAge} or over. We do not save it.`}
                    error={errors.date_of_birth}
                >
                    <Input
                        id="date_of_birth"
                        type="date"
                        required
                        autoComplete="bday"
                        aria-describedby="date_of_birth-hint"
                        max={new Date().toISOString().slice(0, 10)}
                        value={data.date_of_birth}
                        onChange={(e) => setData('date_of_birth', e.target.value)}
                    />
                </Field>

                <Field label="Password" htmlFor="password" hint="At least 10 characters." error={errors.password}>
                    <Input
                        id="password"
                        type="password"
                        required
                        autoComplete="new-password"
                        aria-describedby="password-hint"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                    />
                </Field>

                <Field label="Password again" htmlFor="password_confirmation" error={errors.password_confirmation}>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                    />
                </Field>

                <div>
                    <label className="flex items-start gap-3 text-sm leading-relaxed">
                        <input
                            type="checkbox"
                            className="accent-primary mt-0.5 size-5 shrink-0"
                            checked={data.terms}
                            onChange={(e) => setData('terms', e.target.checked)}
                        />
                        <span>
                            I have read the <TextLink href={route('terms')}>terms</TextLink> and the{' '}
                            <TextLink href={route('privacy')}>privacy notice</TextLink>.
                        </span>
                    </label>
                    <InputError message={errors.terms} className="mt-2" />
                </div>

                <Button type="submit" size="lg" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                    Create account
                </Button>

                <p className="text-muted-foreground text-sm">
                    Already have an account? <TextLink href={route('login')}>Log in</TextLink>
                </p>
            </form>
        </AuthLayout>
    );
}
