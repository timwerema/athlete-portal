import { useCallback, useEffect, useState } from 'react';

import { Appearance, readAppearance, resolvesToDark } from './use-appearance-logic';

export type { Appearance };

const COOKIE = 'appearance';
const mediaQuery = () => window.matchMedia('(prefers-color-scheme: dark)');

function apply(appearance: Appearance) {
    document.documentElement.classList.toggle('dark', resolvesToDark(appearance, mediaQuery().matches));
}

function onSystemChange() {
    apply(readAppearance(document.cookie));
}

export function initializeTheme() {
    apply(readAppearance(document.cookie));
    mediaQuery().addEventListener('change', onSystemChange);
}

export function useAppearance() {
    const [appearance, setAppearance] = useState<Appearance>('dark');

    useEffect(() => {
        setAppearance(readAppearance(document.cookie));
    }, []);

    const updateAppearance = useCallback((mode: Appearance) => {
        setAppearance(mode);
        // A plain preference cookie so the server can render the right theme first time. No personal data.
        const secure = window.location.protocol === 'https:' ? ';Secure' : '';
        document.cookie = `${COOKIE}=${mode};path=/;max-age=${60 * 60 * 24 * 365};SameSite=Lax${secure}`;
        apply(mode);
    }, []);

    return { appearance, updateAppearance };
}
