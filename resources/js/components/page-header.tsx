import { ReactNode } from 'react';

export default function PageHeader({ kicker, title, children }: { kicker?: string; title: string; children?: ReactNode }) {
    return (
        <header className="reveal mb-8">
            {kicker && <p className="kicker mb-3">{kicker}</p>}
            <h1 className="text-3xl leading-[1.05] font-semibold sm:text-4xl">{title}</h1>
            {children && <div className="text-muted-foreground mt-3 max-w-2xl text-base leading-relaxed">{children}</div>}
        </header>
    );
}
