export type Appearance = 'light' | 'dark' | 'system';

/** Dark is the default until the person picks something else. */
export function readAppearance(cookie: string): Appearance {
    const match = cookie.match(/(?:^|;\s*)appearance=(light|dark|system)(?:;|$)/);

    return (match?.[1] as Appearance | undefined) ?? 'dark';
}

export function resolvesToDark(appearance: Appearance, systemPrefersDark: boolean): boolean {
    return appearance === 'dark' || (appearance === 'system' && systemPrefersDark);
}
