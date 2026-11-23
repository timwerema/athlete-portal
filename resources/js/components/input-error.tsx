import { cn } from '@/lib/utils';
import { HTMLAttributes } from 'react';

export default function InputError({ message, className = '', ...props }: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p role="alert" {...props} className={cn('text-danger text-sm font-medium', className)}>
            {message}
        </p>
    ) : null;
}
