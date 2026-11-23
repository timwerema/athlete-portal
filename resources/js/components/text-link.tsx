import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ComponentProps } from 'react';

export default function TextLink({ className = '', children, ...props }: ComponentProps<typeof Link>) {
    return (
        <Link
            className={cn('decoration-primary font-medium underline decoration-2 underline-offset-4 hover:decoration-current', className)}
            {...props}
        >
            {children}
        </Link>
    );
}
