import * as React from 'react';

import { cn } from '@/lib/utils';

const Input = React.forwardRef<HTMLInputElement, React.ComponentProps<'input'>>(({ className, type, ...props }, ref) => {
    return <input type={type} className={cn('field-control', className)} ref={ref} {...props} />;
});
Input.displayName = 'Input';

const Textarea = React.forwardRef<HTMLTextAreaElement, React.ComponentProps<'textarea'>>(({ className, ...props }, ref) => {
    return <textarea className={cn('field-control min-h-32 leading-relaxed', className)} ref={ref} {...props} />;
});
Textarea.displayName = 'Textarea';

const Select = React.forwardRef<HTMLSelectElement, React.ComponentProps<'select'>>(({ className, children, ...props }, ref) => {
    return (
        <select className={cn('field-control', className)} ref={ref} {...props}>
            {children}
        </select>
    );
});
Select.displayName = 'Select';

export { Input, Select, Textarea };
