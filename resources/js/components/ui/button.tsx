import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/lib/utils';

const buttonVariants = cva(
    'inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-md text-sm font-semibold whitespace-nowrap transition-colors disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                default: 'bg-primary text-primary-foreground hover:bg-primary/85',
                destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/85',
                outline: 'border-input hover:bg-secondary border bg-transparent',
                secondary: 'bg-secondary text-foreground hover:bg-secondary/70',
                ghost: 'hover:bg-secondary',
                link: 'decoration-primary min-h-0 underline decoration-2 underline-offset-4 hover:decoration-current',
            },
            size: {
                default: 'px-4 py-2',
                sm: 'min-h-9 px-3',
                lg: 'min-h-12 px-6 text-base',
                icon: 'size-11',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
);

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement>, VariantProps<typeof buttonVariants> {
    asChild?: boolean;
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(({ className, variant, size, asChild = false, type, ...props }, ref) => {
    const Comp = asChild ? Slot : 'button';

    return <Comp className={cn(buttonVariants({ variant, size, className }))} ref={ref} {...(asChild ? {} : { type: type ?? 'button' })} {...props} />;
});
Button.displayName = 'Button';

export { Button, buttonVariants };
