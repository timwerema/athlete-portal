import ConfirmAction from '@/components/confirm-action';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { initials } from '@/lib/format';
import { shrinkImage } from '@/lib/shrink-image';
import { router } from '@inertiajs/react';
import { LoaderCircle, Trash2, Upload } from 'lucide-react';
import { ChangeEvent, useRef, useState } from 'react';

interface PhotoUploaderProps {
    hasPhoto: boolean;
    version: string | null;
    name: string;
    maxKilobytes: number;
}

/** Pick a photo and it uploads straight away. The server re-saves it and strips hidden location data. */
export default function PhotoUploader({ hasPhoto, version, name, maxKilobytes }: PhotoUploaderProps) {
    const input = useRef<HTMLInputElement>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string>();

    const choose = async (e: ChangeEvent<HTMLInputElement>) => {
        const original = e.target.files?.[0];
        e.target.value = '';

        if (!original) {
            return;
        }

        setError(undefined);
        setBusy(true);

        const file = await shrinkImage(original);

        if (file.size > maxKilobytes * 1024) {
            setError(`That photo is too big. Please use one under ${Math.floor(maxKilobytes / 1024)} MB.`);
            setBusy(false);
            return;
        }

        router.post(
            route('profile.photo.store'),
            { photo: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onError: (errors) => setError(errors.photo ?? 'The photo could not be saved. Please try again.'),
                onFinish: () => setBusy(false),
            },
        );
    };

    const remove = (done: () => void) => {
        setBusy(true);
        router.delete(route('profile.photo.destroy'), { preserveScroll: true, onSuccess: done, onFinish: () => setBusy(false) });
    };

    return (
        <div className="grid gap-2">
            <p className="text-sm font-medium">
                Photo <span className="text-muted-foreground font-normal">(optional)</span>
            </p>
            <div className="flex items-center gap-5">
                {hasPhoto ? (
                    <img
                        src={`${route('profile.photo')}?v=${version}`}
                        alt="Your profile photo"
                        className="bg-secondary size-24 rounded-md border object-cover"
                        width={96}
                        height={96}
                    />
                ) : (
                    <div
                        className="bg-secondary font-display text-muted-foreground flex size-24 items-center justify-center rounded-md border text-2xl font-semibold"
                        aria-hidden="true"
                    >
                        {initials(name) || '?'}
                    </div>
                )}
                <div className="flex flex-col items-start gap-2">
                    <input
                        ref={input}
                        id="photo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        className="sr-only"
                        onChange={choose}
                        tabIndex={-1}
                        aria-label="Choose a photo"
                    />
                    <Button variant="outline" disabled={busy} onClick={() => input.current?.click()}>
                        {busy ? <LoaderCircle className="animate-spin" aria-hidden="true" /> : <Upload aria-hidden="true" />}
                        {hasPhoto ? 'Change photo' : 'Upload a photo'}
                    </Button>
                    {hasPhoto && (
                        <ConfirmAction
                            trigger={
                                <Button variant="link" className="text-sm">
                                    <Trash2 aria-hidden="true" />
                                    Remove photo
                                </Button>
                            }
                            title="Remove your photo?"
                            description="The photo is deleted from our storage."
                            confirmLabel="Remove"
                            destructive
                            busy={busy}
                            onConfirm={remove}
                        />
                    )}
                </div>
            </div>
            <p className="text-muted-foreground text-sm">
                JPEG, PNG or WebP. A clear head-and-shoulders or action shot works best. We remove hidden location data from the file.
            </p>
            <InputError message={error} />
        </div>
    );
}
