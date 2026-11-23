import LegalPage, { LegalSection } from '@/components/legal-page';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export default function Terms() {
    const { name } = usePage<SharedData>().props;

    return (
        <LegalPage title="Terms of use" updated="23 November 2026">
            <LegalSection title="What this service is">
                <p>
                    {name} lets a college athlete build a profile and share it with coaches of their choosing. It is an independent service. It is{' '}
                    <strong>not the NCAA Transfer Portal</strong> and is not affiliated with or endorsed by the NCAA, the NAIA, the NJCAA, any
                    conference or any school. Creating a profile does not enter you into any official system.
                </p>
            </LegalSection>

            <LegalSection title="Who can use it">
                <p>You must be 18 or older and you may only create a profile about yourself.</p>
            </LegalSection>

            <LegalSection title="Your content">
                <ul>
                    <li>What you put on your profile must be true to the best of your knowledge. Stats, eligibility and dates are self-reported.</li>
                    <li>Only add a reference if that person has agreed to be listed and to have their contact details shared.</li>
                    <li>Only add photos and video links you have the right to share.</li>
                    <li>You keep ownership of your content. You give us permission to store it and show it to people who have your link.</li>
                </ul>
            </LegalSection>

            <LegalSection title="Rules of your sport">
                <p>
                    Transfer, recruiting and contact rules differ by association and division, and they change. We do not give advice about those
                    rules and nothing on this site should be read as advice. You are responsible for following the rules that apply to you. If you are
                    unsure, ask your school&apos;s compliance office before you share your profile or reply to a coach.
                </p>
                <p>Coaches who use a shared link or the contact form are responsible for following the rules that apply to them.</p>
            </LegalSection>

            <LegalSection title="Messages from coaches">
                <p>
                    We do not check the identity of people who send messages through your link. Be careful before sharing personal details with
                    someone you have not confirmed is who they say they are.
                </p>
            </LegalSection>

            <LegalSection title="Acceptable use">
                <p>
                    Do not use the service to harass anyone, to pretend to be someone else, to send spam, or to try to break or overload it. We may
                    remove content or close accounts that do.
                </p>
            </LegalSection>

            <LegalSection title="No guarantees">
                <p>
                    We cannot promise that using the service will lead to contact from coaches, a roster place or a scholarship. The service is
                    provided as it is, and may change or stop. Wording on liability will be added after legal review.
                </p>
            </LegalSection>

            <LegalSection title="Leaving">
                <p>You can delete your account at any time from Settings. That removes your profile and everything attached to it.</p>
            </LegalSection>
        </LegalPage>
    );
}
