import { PlusCircle, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';

export type AdminDataTableFacetOption = {
    count?: number;
    label: string;
    value: string;
};

type AdminDataTableFacetFilterProps = {
    onChange: (value: string | null) => void;
    options: AdminDataTableFacetOption[];
    selected: string | null;
    title: string;
};

export function AdminDataTableFacetFilter({
    onChange,
    options,
    selected,
    title,
}: AdminDataTableFacetFilterProps) {
    const selectedOption = options.find((option) => option.value === selected);

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    aria-label={
                        selectedOption
                            ? `${title}filter: ${selectedOption.label}`
                            : `Filter op ${title.toLowerCase()}`
                    }
                    className="h-9 border-dashed"
                >
                    <PlusCircle className="size-4" />
                    {title}
                    {selectedOption && (
                        <>
                            <Separator orientation="vertical" className="h-4" />
                            <Badge
                                variant="secondary"
                                className="rounded-sm px-1.5 font-normal"
                            >
                                {selectedOption.label}
                            </Badge>
                        </>
                    )}
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="start" className="w-56">
                <DropdownMenuLabel>{title}</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuRadioGroup
                    value={selected ?? ''}
                    onValueChange={onChange}
                >
                    {options.map((option) => (
                        <DropdownMenuRadioItem
                            key={option.value}
                            value={option.value}
                            className="cursor-pointer"
                        >
                            <span>{option.label}</span>
                            {option.count !== undefined && (
                                <span className="ml-auto text-xs text-muted-foreground tabular-nums">
                                    {option.count}
                                </span>
                            )}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>

                {selectedOption && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            className="cursor-pointer justify-center"
                            onSelect={() => onChange(null)}
                        >
                            <X className="size-4" />
                            Filter wissen
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
