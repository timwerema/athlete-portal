import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

/** A quiet side-ruled note. Used for honest caveats, never for marketing. */
export default function Notice({ title, children, className }: { title?: string; children: ReactNode; className?: string }) {
    return (
        <div className={cn('border-primary text-muted-foreground border-l-2 py-1 pl-4 text-sm leading-relaxed', className)}>
            {title && <p className="text-foreground mb-1 font-semibold">{title}</p>}
            {children}
        </div>
    );
}
