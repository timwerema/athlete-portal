import Notice from '@/components/notice';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { auth } = usePage<SharedData>().props;
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <AuthLayout
            title="Check your email"
            description={
                <>
                    We sent a link to <strong className="text-foreground">{auth.user?.email}</strong>. Open it to confirm the address is yours, then
                    you can start your profile.
                </>
            }
        >
            <Head title="Confirm your email" />

            {status === 'verification-link-sent' && (
                <Notice className="mb-6">
                    <p role="status">A new link is on its way.</p>
                </Notice>
            )}

            <form onSubmit={submit} className="grid gap-4">
                <Button type="submit" size="lg" variant="outline" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                    Send the link again
                </Button>
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="text-muted-foreground min-h-11 cursor-pointer text-sm underline underline-offset-4"
                >
                    Log out
                </Link>
            </form>
        </AuthLayout>
    );
}
