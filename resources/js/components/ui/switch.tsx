import { cn } from '@/lib/utils';

interface SwitchProps {
    id: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
    disabled?: boolean;
    'aria-describedby'?: string;
}

/** An on/off switch that works with the keyboard and is announced properly. */
export function Switch({ id, checked, onChange, disabled, ...rest }: SwitchProps) {
    return (
        <button
            type="button"
            role="switch"
            id={id}
            aria-checked={checked}
            disabled={disabled}
            onClick={() => onChange(!checked)}
            className={cn(
                'relative inline-flex h-7 w-12 shrink-0 items-center rounded-full border-2 transition-colors disabled:opacity-60',
                checked ? 'border-primary bg-primary' : 'border-input bg-transparent',
            )}
            {...rest}
        >
            <span
                aria-hidden="true"
                className={cn('block size-4 rounded-full transition-transform', checked ? 'bg-primary-foreground translate-x-6' : 'bg-input translate-x-1')}
            />
        </button>
    );
}
