import TextLink from '@/components/text-link';
import AuthLayout from '@/layouts/auth-layout';
import { Head } from '@inertiajs/react';

export default function CheckEmail() {
    return (
        <AuthLayout
            title="Check your email"
            description="We have sent you an email. Open the link inside to confirm your address, then log in to start your profile."
        >
            <Head title="Check your email" />
            <div className="text-muted-foreground grid gap-4 text-sm leading-relaxed">
                <p>It can take a minute to arrive. If you cannot see it, look in your spam folder.</p>
                <p>
                    Already confirmed? <TextLink href={route('login')}>Log in</TextLink>
                </p>
            </div>
        </AuthLayout>
    );
}
