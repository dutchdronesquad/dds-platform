import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type AdminStatus =
    'active' | 'archived' | 'draft' | 'inactive' | 'published';

const statusStyles: Record<AdminStatus, string> = {
    active: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
    archived:
        'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300',
    draft: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
    inactive:
        'border-neutral-200 bg-neutral-100 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400',
    published:
        'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300',
};

const statusLabels: Record<AdminStatus, string> = {
    active: 'Actief',
    archived: 'Gearchiveerd',
    draft: 'Concept',
    inactive: 'Inactief',
    published: 'Gepubliceerd',
};

type AdminStatusBadgeProps = {
    className?: string;
    label?: string;
    status: AdminStatus;
};

export function AdminStatusBadge({
    className,
    label,
    status,
}: AdminStatusBadgeProps) {
    return (
        <Badge
            variant="outline"
            className={cn(statusStyles[status], className)}
        >
            {label ?? statusLabels[status]}
        </Badge>
    );
}
