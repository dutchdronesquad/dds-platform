import { ListFilter, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

export type AdminDataTableFacetOption = {
    count?: number;
    label: string;
    value: string;
};

type AdminDataTableFacetFilterProps = {
    closeOnSelect?: boolean;
    onChange: (values: string[]) => void;
    options: AdminDataTableFacetOption[];
    selected: string[];
    title: string;
};

export function AdminDataTableFacetFilter({
    closeOnSelect = false,
    onChange,
    options,
    selected,
    title,
}: AdminDataTableFacetFilterProps) {
    const selectedValues = new Set(selected);
    const selectedOptions = options.filter((option) =>
        selectedValues.has(option.value),
    );
    const selectionLabel =
        selectedOptions.length === 1
            ? selectedOptions[0].label
            : `${selectedOptions.length} gekozen`;

    function changeSelection(value: string, checked: boolean): void {
        const nextSelectedValues = new Set(selectedValues);

        if (checked) {
            nextSelectedValues.add(value);
        } else {
            nextSelectedValues.delete(value);
        }

        onChange(
            options
                .filter((option) => nextSelectedValues.has(option.value))
                .map((option) => option.value),
        );
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    aria-label={
                        selectedOptions.length > 0
                            ? `${title}filter: ${selectedOptions.map((option) => option.label).join(', ')}`
                            : `Filter op ${title.toLowerCase()}`
                    }
                    className={cn(
                        'h-9 border-sidebar-border/80 bg-white dark:bg-neutral-950',
                        selectedOptions.length > 0 &&
                            'border-signal-300 bg-signal-50 text-signal-700 hover:bg-signal-100 hover:text-signal-700 dark:border-signal-500/30 dark:bg-signal-500/10 dark:text-signal-300 dark:hover:bg-signal-500/15',
                    )}
                >
                    <ListFilter className="size-4" />
                    {title}
                    {selectedOptions.length > 0 && (
                        <>
                            <Separator orientation="vertical" className="h-4" />
                            <span className="font-semibold">
                                {selectionLabel}
                            </span>
                        </>
                    )}
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="start" className="w-56">
                <DropdownMenuLabel>{title}</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {options.map((option) => (
                    <DropdownMenuCheckboxItem
                        key={option.value}
                        checked={selectedValues.has(option.value)}
                        onCheckedChange={(checked) =>
                            changeSelection(option.value, checked === true)
                        }
                        onSelect={
                            closeOnSelect
                                ? undefined
                                : (event) => event.preventDefault()
                        }
                        className="cursor-pointer"
                    >
                        <span>{option.label}</span>
                        {option.count !== undefined && (
                            <span className="ml-auto text-xs text-muted-foreground tabular-nums">
                                {option.count}
                            </span>
                        )}
                    </DropdownMenuCheckboxItem>
                ))}

                {selectedOptions.length > 0 && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            className="cursor-pointer justify-center"
                            onSelect={() => onChange([])}
                        >
                            <X className="size-3.5" />
                            Filter wissen
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
