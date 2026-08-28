import { format } from 'date-fns';
import { nl } from 'date-fns/locale';
import { CalendarIcon, XIcon } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { TimePicker } from '@/components/ui/time-picker';
import { cn } from '@/lib/utils';

type DateTimePickerProps = {
    'aria-describedby'?: string;
    'aria-invalid'?: boolean;
    defaultValue?: string;
    id: string;
    label: string;
    name: string;
    showNowShortcut?: boolean;
    showTodayShortcut?: boolean;
};

function parseDateTime(value?: string): { date?: Date; time: string } {
    if (!value) {
        return { time: '' };
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return { time: '' };
    }

    return {
        date,
        time: format(date, 'HH:mm'),
    };
}

function formatDateTime(date: Date | undefined, time: string): string {
    const timeMatch = time.match(/^([01]\d|2[0-3]):([0-5]\d)$/);

    if (!date || !timeMatch) {
        return '';
    }

    const [, hours, minutes] = timeMatch;
    const dateTime = new Date(date);

    dateTime.setHours(Number(hours), Number(minutes), 0, 0);

    return format(dateTime, "yyyy-MM-dd'T'HH:mm:ssXXX");
}

function DateTimePicker({
    'aria-describedby': ariaDescribedBy,
    'aria-invalid': ariaInvalid,
    defaultValue,
    id,
    label,
    name,
    showNowShortcut = false,
    showTodayShortcut = false,
}: DateTimePickerProps) {
    const initialValue = parseDateTime(defaultValue);
    const [date, setDate] = useState<Date | undefined>(initialValue.date);
    const [time, setTime] = useState(initialValue.time);
    const [open, setOpen] = useState(false);

    return (
        <div
            data-slot="date-time-picker"
            data-invalid={ariaInvalid || undefined}
            className={cn(
                'grid h-9 grid-cols-[minmax(0,1fr)_5.5rem_2.25rem] overflow-hidden rounded-md border border-input bg-background shadow-xs transition-[border-color,box-shadow] focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50',
                ariaInvalid &&
                    'border-destructive ring-destructive/20 focus-within:border-destructive focus-within:ring-destructive/20 dark:ring-destructive/40 dark:focus-within:ring-destructive/40',
            )}
        >
            <input
                type="hidden"
                name={name}
                value={formatDateTime(date, time)}
            />
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="ghost"
                        aria-describedby={ariaDescribedBy}
                        aria-invalid={ariaInvalid}
                        className={cn(
                            'h-full min-w-0 justify-start overflow-hidden rounded-none px-3 text-left font-normal shadow-none hover:bg-accent/70 focus-visible:border-transparent focus-visible:bg-accent/70 focus-visible:ring-0',
                            !date && 'text-muted-foreground',
                        )}
                    >
                        <CalendarIcon />
                        <span className="truncate">
                            {date
                                ? format(date, 'd MMMM yyyy', { locale: nl })
                                : 'Kies datum'}
                        </span>
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="start">
                    <Calendar
                        mode="single"
                        locale={nl}
                        labels={{
                            labelDayButton: (day, modifiers) =>
                                `${modifiers.today ? 'Vandaag, ' : ''}${format(day, 'EEEE d MMMM yyyy', { locale: nl })}`,
                            labelNav: () => 'Kalendernavigatie',
                            labelNext: () => 'Volgende maand',
                            labelPrevious: () => 'Vorige maand',
                        }}
                        selected={date}
                        defaultMonth={date}
                        onSelect={(selectedDate) => {
                            setDate(selectedDate);
                            setTime((currentTime) => currentTime || '00:00');
                            setOpen(false);
                        }}
                        autoFocus
                    />
                    {(showNowShortcut || showTodayShortcut) && (
                        <div className="flex gap-1 border-t border-border p-2">
                            {showNowShortcut && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="flex-1"
                                    onClick={() => {
                                        const now = new Date();

                                        setDate(now);
                                        setTime(format(now, 'HH:mm'));
                                        setOpen(false);
                                    }}
                                >
                                    Nu
                                </Button>
                            )}
                            {showTodayShortcut && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="flex-1"
                                    onClick={() => {
                                        setDate(new Date());
                                        setTime((currentTime) =>
                                            currentTime || '00:00',
                                        );
                                        setOpen(false);
                                    }}
                                >
                                    Vandaag
                                </Button>
                            )}
                        </div>
                    )}
                </PopoverContent>
            </Popover>
            <TimePicker
                id={`${id}_time`}
                label={label}
                value={time}
                onChange={setTime}
                disabled={!date}
                aria-describedby={ariaDescribedBy}
                aria-invalid={ariaInvalid}
                className="h-full rounded-none border-0 border-l border-input bg-transparent shadow-none hover:bg-accent/70 focus-visible:z-10 focus-visible:border-l-input focus-visible:bg-accent/70 focus-visible:ring-0"
            />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                onClick={() => {
                    setDate(undefined);
                    setTime('');
                }}
                disabled={!date}
                aria-label={`${label} wissen`}
                className="size-auto h-full rounded-none border-l border-input text-muted-foreground shadow-none hover:bg-destructive/10 hover:text-destructive focus-visible:z-10 focus-visible:border-l-input focus-visible:bg-destructive/10 focus-visible:text-destructive focus-visible:ring-0"
            >
                <XIcon />
            </Button>
        </div>
    );
}

export { DateTimePicker };
