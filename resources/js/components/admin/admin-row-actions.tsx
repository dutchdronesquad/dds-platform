import { MoreHorizontal } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export function AdminRowActions({
    children,
    label = 'Rijacties openen',
}: {
    children: ReactNode;
    label?: string;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    aria-label={label}
                    className="size-8 text-neutral-500 hover:text-neutral-950 focus-visible:ring-signal-500 dark:text-neutral-400 dark:hover:text-white dark:focus-visible:ring-signal-400"
                >
                    <MoreHorizontal className="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-48">
                {children}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
