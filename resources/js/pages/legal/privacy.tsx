import LegalPage, { LegalSection } from '@/components/legal-page';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export default function Privacy() {
    const { name } = usePage<SharedData>().props;

    return (
        <LegalPage title="Privacy notice" updated="23 November 2026">
            <LegalSection title="Who runs this">
                <p>
                    {name} is run by Timasi Werema, an independent developer. It is not affiliated with the NCAA, the NAIA, the NJCAA, any conference
                    or any school. A contact address for privacy questions will be added here before launch.
                </p>
            </LegalSection>

            <LegalSection title="What we collect">
                <ul>
                    <li>
                        <strong>Account:</strong> your name, email address and a scrambled (hashed) password. If you turn on two-step login we store
                        the secret your authenticator app uses, encrypted.
                    </li>
                    <li>
                        <strong>Age:</strong> we ask your date of birth once to check you are 18 or over. We do not save it. We save only the date you
                        confirmed it.
                    </li>
                    <li>
                        <strong>Profile:</strong> whatever you choose to enter: positions, height, school, stats, history, honours, video links,
                        academics, references, what you are looking for, your statement and a photo. Everything except your name is optional.
                    </li>
                    <li>
                        <strong>Messages:</strong> when a coach writes to you through your link we store their name, school, role, email and message
                        so you can read it.
                    </li>
                    <li>
                        <strong>Visits to your link:</strong> a count of unique visits per day. We do not store visitors&apos; IP addresses. We store
                        a scrambled value that changes every day and cannot be turned back into an address.
                    </li>
                </ul>
            </LegalSection>

            <LegalSection title="Who can see your profile">
                <p>
                    Nobody, until you create a share link. After that, anyone who has the link can open the page, so send it only to people you trust.
                    You can switch the link off or replace it at any time, and you choose which parts of the page are shown. Your email address and
                    phone number are never shown on the page.
                </p>
                <p>There is no public directory and no search. We ask search engines not to index any page on this site.</p>
            </LegalSection>

            <LegalSection title="Photos">
                <p>
                    Photos are re-saved by our server, which removes hidden information such as the place the photo was taken. They are stored
                    privately and shown only to you and to people who have your active link.
                </p>
            </LegalSection>

            <LegalSection title="Video">
                <p>
                    We do not host video. If you add a YouTube or Vimeo link, the coach page loads the player from that company, and that company may
                    set its own cookies when the video is shown. Other links are shown as plain links.
                </p>
            </LegalSection>

            <LegalSection title="Cookies">
                <p>
                    We use only the cookies needed to keep you logged in and protect forms, plus one that remembers light or dark mode. We do not use
                    advertising or analytics trackers.
                </p>
            </LegalSection>

            <LegalSection title="What we do not do">
                <ul>
                    <li>We do not sell or rent your information.</li>
                    <li>We do not show your profile to your current school or coach. Only people with your link can see it.</li>
                    <li>We do not verify what athletes or coaches write.</li>
                </ul>
            </LegalSection>

            <LegalSection title="Your choices">
                <p>
                    In Settings, under &quot;Your data&quot;, you can download everything we hold about you, or delete your account. Deleting removes
                    your profile, photo, links, visit counts and messages straight away. Copies in routine backups, once hosting is set up, will be
                    removed on the backup schedule described here before launch.
                </p>
            </LegalSection>

            <LegalSection title="Age">
                <p>This service is for people aged 18 or over. We do not knowingly keep accounts for anyone younger.</p>
            </LegalSection>

            <LegalSection title="Still to be decided">
                <p>
                    Where the service is hosted, which company sends our email, how long backups are kept, and the contact address are not settled
                    yet. This notice will be updated with those details before the service opens.
                </p>
            </LegalSection>
        </LegalPage>
    );
}
