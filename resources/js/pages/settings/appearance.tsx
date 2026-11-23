import { Appearance, useAppearance } from '@/hooks/use-appearance';
import SettingsLayout from '@/layouts/settings-layout';
import { cn } from '@/lib/utils';
import { Head } from '@inertiajs/react';
import { Monitor, Moon, Sun } from 'lucide-react';

const OPTIONS: { value: Appearance; label: string; text: string; icon: typeof Sun }[] = [
    { value: 'dark', label: 'Dark', text: 'The default.', icon: Moon },
    { value: 'light', label: 'Light', text: 'Easier in bright daylight.', icon: Sun },
    { value: 'system', label: 'Match my device', text: 'Follows your phone or computer setting.', icon: Monitor },
];

export default function AppearanceSettings() {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <SettingsLayout title="Appearance" intro="This only changes how the site looks for you. Coaches see your page in their own setting.">
            <Head title="Appearance" />
            <fieldset>
                <legend className="sr-only">Colour mode</legend>
                <ul className="max-w-md border-t">
                    {OPTIONS.map(({ value, label, text, icon: Icon }) => (
                        <li key={value} className="border-b">
                            <label className={cn('flex min-h-16 cursor-pointer items-center gap-4 py-3', appearance === value && 'font-semibold')}>
                                <input
                                    type="radio"
                                    name="appearance"
                                    className="accent-primary size-5"
                                    checked={appearance === value}
                                    onChange={() => updateAppearance(value)}
                                />
                                <Icon className="size-5" aria-hidden="true" />
                                <span>
                                    {label}
                                    <span className="text-muted-foreground block text-sm font-normal">{text}</span>
                                </span>
                            </label>
                        </li>
                    ))}
                </ul>
            </fieldset>
        </SettingsLayout>
    );
}
