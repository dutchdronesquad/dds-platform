import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { MediaLocale } from '@/types/media';

export function MediaAltTextFields({
    altText = {},
    columns = 'responsive',
    disabled = false,
    errors,
    locales,
    onChange,
}: {
    altText?: Record<string, string>;
    columns?: 'responsive' | 'stacked';
    disabled?: boolean;
    errors: Record<string, string>;
    locales: MediaLocale[];
    onChange?: (locale: string, value: string) => void;
}) {
    return (
        <fieldset className="grid gap-4">
            <div>
                <legend className="text-sm font-semibold text-neutral-950 dark:text-white">
                    Beschrijving voor toegankelijkheid
                </legend>
                <p className="mt-1 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                    Beschrijf wat de afbeelding betekenisvol maakt. Is de
                    afbeelding puur decoratief, dan mag je dit bewust leeg
                    laten. De uiteindelijke pagina kan de tekst nog aanpassen.
                </p>
            </div>

            <div
                className={
                    columns === 'responsive'
                        ? 'grid gap-4 sm:grid-cols-2'
                        : 'grid gap-4'
                }
            >
                {locales.map((locale) => {
                    const field = `alt_text.${locale.code}`;
                    const inputId = `alt_text_${locale.code}`;

                    return (
                        <div key={locale.code} className="grid gap-2">
                            <Label htmlFor={inputId}>{locale.label}</Label>
                            <Input
                                id={inputId}
                                name={`alt_text[${locale.code}]`}
                                value={
                                    onChange
                                        ? (altText[locale.code] ?? '')
                                        : undefined
                                }
                                defaultValue={
                                    onChange
                                        ? undefined
                                        : (altText[locale.code] ?? '')
                                }
                                disabled={disabled}
                                maxLength={500}
                                aria-invalid={Boolean(errors[field])}
                                aria-describedby={
                                    errors[field]
                                        ? `${inputId}-error`
                                        : undefined
                                }
                                onChange={(event) =>
                                    onChange?.(locale.code, event.target.value)
                                }
                            />
                            <InputError
                                id={`${inputId}-error`}
                                message={errors[field]}
                            />
                        </div>
                    );
                })}
            </div>
        </fieldset>
    );
}
