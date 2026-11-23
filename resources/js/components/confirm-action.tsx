import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { ReactNode, useState } from 'react';

interface ConfirmActionProps {
    trigger: ReactNode;
    title: string;
    description: ReactNode;
    confirmLabel: string;
    destructive?: boolean;
    /** Runs the action. Call done() to close the dialog once it has worked. */
    onConfirm: (done: () => void) => void;
    busy?: boolean;
    children?: ReactNode;
}

/** Asks "are you sure?" before anything that cannot be undone. */
export default function ConfirmAction({ trigger, title, description, confirmLabel, destructive, onConfirm, busy, children }: ConfirmActionProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription asChild>
                        <div className="text-muted-foreground text-sm leading-relaxed">{description}</div>
                    </DialogDescription>
                </DialogHeader>
                {children}
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button variant={destructive ? 'destructive' : 'default'} disabled={busy} onClick={() => onConfirm(() => setOpen(false))}>
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
