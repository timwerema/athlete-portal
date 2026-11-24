import ConfirmAction from '@/components/confirm-action';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { InertiaFormProps, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, LoaderCircle, Pencil, Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, ReactNode, useState } from 'react';

type FormData = Record<string, any>; // eslint-disable-line @typescript-eslint/no-explicit-any

interface ItemSectionProps<T extends { id: number }, F extends FormData> {
    /** teams | stats | honours | videos | references */
    type: string;
    items: T[];
    blank: F;
    toForm: (item: T) => F;
    noun: string;
    /** Show move up / move down buttons so the athlete sets the order. */
    orderable?: boolean;
    emptyText: string;
    summary: (item: T) => ReactNode;
    fields: (form: InertiaFormProps<F>, editing: boolean) => ReactNode;
}

function Editor<F extends FormData>({
    type,
    id,
    initial,
    noun,
    fields,
    onClose,
}: {
    type: string;
    id: number | null;
    initial: F;
    noun: string;
    fields: (form: InertiaFormProps<F>, editing: boolean) => ReactNode;
    onClose: () => void;
}) {
    const form = useForm<F>(initial);
    const limitError = (form.errors as Record<string, string>).limit;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (id === null) {
            form.post(route('profile.items.store', type), options);
        } else {
            form.put(route('profile.items.update', [type, id]), options);
        }
    };

    return (
        <form
            onSubmit={submit}
            noValidate
            className="bg-surface reveal my-4 grid gap-5 rounded-md border p-4 sm:p-6"
            aria-label={id === null ? `Add ${noun}` : `Edit ${noun}`}
        >
            <h3 className="text-lg font-semibold">{id === null ? `Add ${noun}` : `Edit ${noun}`}</h3>
            {fields(form, id !== null)}
            <InputError message={limitError} />
            <div className="flex flex-col gap-3 sm:flex-row">
                <Button type="submit" disabled={form.processing}>
                    {form.processing && <LoaderCircle className="animate-spin" aria-hidden="true" />}
                    {id === null ? `Add ${noun}` : 'Save changes'}
                </Button>
                <Button variant="outline" onClick={onClose}>
                    Cancel
                </Button>
            </div>
        </form>
    );
}

/** A list of entries (history, stats, honours, video, references) with add, edit and remove. */
export default function ItemSection<T extends { id: number }, F extends FormData>({
    type,
    items,
    blank,
    toForm,
    noun,
    orderable,
    emptyText,
    summary,
    fields,
}: ItemSectionProps<T, F>) {
    const [editing, setEditing] = useState<number | 'new' | null>(null);
    const [removing, setRemoving] = useState(false);
    const [moving, setMoving] = useState(false);

    const move = (id: number, direction: 'up' | 'down') => {
        setMoving(true);
        router.post(
            route('profile.items.move', [type, id]),
            { direction },
            { preserveScroll: true, preserveState: true, onFinish: () => setMoving(false) },
        );
    };

    const remove = (id: number, done: () => void) => {
        setRemoving(true);
        router.delete(route('profile.items.destroy', [type, id]), {
            preserveScroll: true,
            onSuccess: done,
            onFinish: () => setRemoving(false),
        });
    };

    return (
        <div>
            {items.length === 0 && editing !== 'new' && <p className="text-muted-foreground border-y py-6">{emptyText}</p>}

            {items.length > 0 && (
                <ul className="border-t">
                    {items.map((item, index) =>
                        editing === item.id ? (
                            <li key={item.id}>
                                <Editor
                                    type={type}
                                    id={item.id}
                                    initial={toForm(item)}
                                    noun={noun}
                                    fields={fields}
                                    onClose={() => setEditing(null)}
                                />
                            </li>
                        ) : (
                            <li key={item.id} className="flex items-start justify-between gap-3 border-b py-4">
                                <div className="min-w-0">{summary(item)}</div>
                                <div className="flex shrink-0 flex-wrap justify-end gap-1">
                                    {orderable && items.length > 1 && (
                                        <>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Move this ${noun} up`}
                                                disabled={moving || index === 0}
                                                onClick={() => move(item.id, 'up')}
                                            >
                                                <ArrowUp aria-hidden="true" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Move this ${noun} down`}
                                                disabled={moving || index === items.length - 1}
                                                onClick={() => move(item.id, 'down')}
                                            >
                                                <ArrowDown aria-hidden="true" />
                                            </Button>
                                        </>
                                    )}
                                    <Button variant="ghost" size="icon" aria-label={`Edit this ${noun}`} onClick={() => setEditing(item.id)}>
                                        <Pencil aria-hidden="true" />
                                    </Button>
                                    <ConfirmAction
                                        trigger={
                                            <Button variant="ghost" size="icon" aria-label={`Remove this ${noun}`}>
                                                <Trash2 aria-hidden="true" />
                                            </Button>
                                        }
                                        title={`Remove this ${noun}?`}
                                        description="It will be taken off your profile. You can add it again later."
                                        confirmLabel="Remove"
                                        destructive
                                        busy={removing}
                                        onConfirm={(done) => remove(item.id, done)}
                                    />
                                </div>
                            </li>
                        ),
                    )}
                </ul>
            )}

            {editing === 'new' ? (
                <Editor type={type} id={null} initial={blank} noun={noun} fields={fields} onClose={() => setEditing(null)} />
            ) : (
                <Button variant="outline" className="mt-5" onClick={() => setEditing('new')}>
                    <Plus aria-hidden="true" />
                    Add {noun}
                </Button>
            )}
        </div>
    );
}
