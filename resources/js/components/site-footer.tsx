import { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

/** Says plainly what this service is not, on every public page. */
export default function SiteFooter() {
    const { name } = usePage<SharedData>().props;

    return (
        <footer className="no-print border-t">
            <div className="text-muted-foreground mx-auto grid max-w-5xl gap-6 px-4 py-10 text-sm sm:px-6 md:grid-cols-[1fr_auto]">
                <p className="max-w-xl leading-relaxed">
                    {name} is an independent service. It is not the NCAA Transfer Portal and is not affiliated with or endorsed by the NCAA, the NAIA,
                    the NJCAA, any conference or any school. Profiles are written by athletes and are not verified.
                </p>
                <ul className="flex flex-wrap gap-x-6 gap-y-2 md:justify-end">
                    <li>
                        <Link href={route('privacy')} className="hover:text-foreground underline underline-offset-4">
                            Privacy
                        </Link>
                    </li>
                    <li>
                        <Link href={route('terms')} className="hover:text-foreground underline underline-offset-4">
                            Terms
                        </Link>
                    </li>
                </ul>
                <p className="md:col-span-2">
                    © {new Date().getFullYear()} {name}. Built by Timasi Werema.
                </p>
            </div>
        </footer>
    );
}
