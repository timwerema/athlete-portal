import { Select } from '@/components/ui/input';
import { cmToFeetInches, feetInchesToCm } from '@/lib/format';

/** Height in feet and inches (how US coaches read it). Stored in centimetres. */
export default function HeightInput({ value, onChange }: { value: number | null; onChange: (cm: number | null) => void }) {
    const current = value ? cmToFeetInches(value) : null;

    const set = (feet: string, inches: string) => {
        if (feet === '') {
            onChange(null);
            return;
        }

        onChange(feetInchesToCm(Number(feet), Number(inches || 0)));
    };

    return (
        <div className="flex items-center gap-3">
            <div className="grid gap-1">
                <label htmlFor="height_feet" className="text-muted-foreground text-xs">
                    Feet
                </label>
                <Select
                    id="height_feet"
                    className="w-24"
                    value={current ? String(current.feet) : ''}
                    onChange={(e) => set(e.target.value, String(current?.inches ?? 0))}
                >
                    <option value="">–</option>
                    {[4, 5, 6, 7].map((feet) => (
                        <option key={feet} value={feet}>
                            {feet} ft
                        </option>
                    ))}
                </Select>
            </div>
            <div className="grid gap-1">
                <label htmlFor="height_inches" className="text-muted-foreground text-xs">
                    Inches
                </label>
                <Select
                    id="height_inches"
                    className="w-24"
                    disabled={!current}
                    value={current ? String(current.inches) : ''}
                    onChange={(e) => set(String(current?.feet ?? ''), e.target.value)}
                >
                    {!current && <option value="">–</option>}
                    {Array.from({ length: 12 }, (_, inches) => (
                        <option key={inches} value={inches}>
                            {inches} in
                        </option>
                    ))}
                </Select>
            </div>
            {value && <p className="text-muted-foreground numeral self-end pb-3 text-sm">{value} cm</p>}
        </div>
    );
}
