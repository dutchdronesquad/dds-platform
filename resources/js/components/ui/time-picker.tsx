import { Clock3Icon } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

const hourOptions = Array.from({ length: 24 }, (_, hour) =>
    String(hour).padStart(2, '0'),
);
const minuteOptions = Array.from({ length: 60 }, (_, minute) =>
    String(minute).padStart(2, '0'),
);

type TimePickerProps = {
    'aria-describedby'?: string;
    'aria-invalid'?: boolean;
    className?: string;
    disabled?: boolean;
    id: string;
    label: string;
    onChange: (value: string) => void;
    value: string;
};

function TimePicker({
    'aria-describedby': ariaDescribedBy,
    'aria-invalid': ariaInvalid,
    className,
    disabled,
    id,
    label,
    onChange,
    value,
}: TimePickerProps) {
    const [open, setOpen] = useState(false);
    const hasTime = /^\d{2}:\d{2}$/.test(value);
    const [hours, minutes] = hasTime ? value.split(':') : ['00', '00'];
    const hourLabelId = `${id}_hour_label`;
    const minuteLabelId = `${id}_minute_label`;
    const displayedTime = hasTime ? `${hours}:${minutes}` : '--:--';

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    aria-label={
                        hasTime
                            ? `Tijd kiezen voor ${label}, ${hours}:${minutes}`
                            : `Tijd kiezen voor ${label}, nog niet ingesteld`
                    }
                    aria-describedby={ariaDescribedBy}
                    aria-invalid={ariaInvalid}
                    className={cn(
                        'w-full justify-start px-3 font-normal tabular-nums',
                        !hasTime && 'text-muted-foreground',
                        className,
                    )}
                >
                    <Clock3Icon />
                    <span>{displayedTime}</span>
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-64 p-3" align="end">
                <p className="mb-3 text-sm font-medium">Tijd kiezen</p>
                <div className="grid grid-cols-2 gap-3">
                    <div className="grid gap-1.5">
                        <span
                            id={hourLabelId}
                            className="text-xs font-medium text-muted-foreground"
                        >
                            Uur
                        </span>
                        <Select
                            value={hours}
                            onValueChange={(selectedHours) =>
                                onChange(`${selectedHours}:${minutes}`)
                            }
                        >
                            <SelectTrigger
                                aria-labelledby={hourLabelId}
                                className="w-full font-medium tabular-nums"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent
                                align="start"
                                className="max-h-64 min-w-[6.75rem]"
                            >
                                {hourOptions.map((hour) => (
                                    <SelectItem
                                        key={hour}
                                        value={hour}
                                        className="tabular-nums"
                                    >
                                        {hour}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="grid gap-1.5">
                        <span
                            id={minuteLabelId}
                            className="text-xs font-medium text-muted-foreground"
                        >
                            Minuut
                        </span>
                        <Select
                            value={minutes}
                            onValueChange={(selectedMinutes) =>
                                onChange(`${hours}:${selectedMinutes}`)
                            }
                        >
                            <SelectTrigger
                                aria-labelledby={minuteLabelId}
                                className="w-full font-medium tabular-nums"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent
                                align="end"
                                className="max-h-64 min-w-[6.75rem]"
                            >
                                {minuteOptions.map((minute) => (
                                    <SelectItem
                                        key={minute}
                                        value={minute}
                                        className="tabular-nums"
                                    >
                                        {minute}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <Button
                    type="button"
                    size="sm"
                    className="mt-3 w-full"
                    onClick={() => setOpen(false)}
                >
                    Gereed
                </Button>
            </PopoverContent>
        </Popover>
    );
}

export { TimePicker };
