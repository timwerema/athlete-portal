import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface FieldProps {
    label: string;
    htmlFor: string;
    hint?: ReactNode;
    error?: string;
    optional?: boolean;
    className?: string;
    children: ReactNode;
}

/** A labelled form row: label, optional hint, the control, and its error message. */
export default function Field({ label, htmlFor, hint, error, optional, className, children }: FieldProps) {
    return (
        <div className={cn('grid gap-1.5', className)}>
            <label htmlFor={htmlFor} className="text-sm font-medium">
                {label}
                {optional && <span className="text-muted-foreground font-normal"> (optional)</span>}
            </label>
            {hint && (
                <p id={`${htmlFor}-hint`} className="text-muted-foreground text-sm">
                    {hint}
                </p>
            )}
            {children}
            {error && (
                <p id={`${htmlFor}-error`} role="alert" className="text-danger text-sm font-medium">
                    {error}
                </p>
            )}
        </div>
    );
}
