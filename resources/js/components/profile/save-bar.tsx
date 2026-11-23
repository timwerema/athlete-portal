import { Button } from '@/components/ui/button';
import { router, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, MutableRefObject, useRef } from 'react';

type FormData = Record<string, any>; // eslint-disable-line @typescript-eslint/no-explicit-any

/** One section's form: "Save" stays on the page, "Save and continue" moves to the next part. */
export function useSectionForm<T extends FormData>(section: string, initial: T, next: string | null) {
    const form = useForm<T>(initial);
    const advance = useRef(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.put(route('profile.update', section), {
            preserveScroll: true,
            onSuccess: () => {
                if (advance.current) {
                    router.visit(next ? route('profile.section', next) : route('profile.index'));
                }
            },
            onFinish: () => {
                advance.current = false;
            },
        });
    };

    return { form, submit, advance };
}

export function SaveBar({ processing, advance, next }: { processing: boolean; advance: MutableRefObject<boolean>; next: string | null }) {
    return (
        <div className="mt-8 flex flex-col gap-3 border-t pt-6 sm:flex-row">
            <Button type="submit" size="lg" disabled={processing} onClick={() => (advance.current = true)}>
                {processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                {next ? 'Save and continue' : 'Save and finish'}
            </Button>
            <Button type="submit" size="lg" variant="outline" disabled={processing}>
                Save
            </Button>
        </div>
    );
}
