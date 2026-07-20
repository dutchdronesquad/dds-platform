import { Clock3, UserRound } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

type AdminActivityActor = {
    id: number;
    name: string;
};

export type AdminActivity = {
    createdAt: string;
    createdBy: AdminActivityActor | null;
    updatedAt: string;
    updatedBy: AdminActivityActor | null;
};

const dateTimeFormatter = new Intl.DateTimeFormat('nl-NL', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'Europe/Amsterdam',
});

export function AdminActivityMetadata({
    activity,
    className,
}: {
    activity: AdminActivity;
    className?: string;
}) {
    const isSystemOrigin = activity.createdBy === null;

    return (
        <section
            className={cn(
                'border-t border-neutral-200 p-5 @min-[84rem]/admin-page:p-6 dark:border-neutral-800',
                className,
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-semibold tracking-[0.14em] text-neutral-500 uppercase">
                        Activiteit
                    </p>
                    <p className="mt-1 text-sm font-semibold text-neutral-950 dark:text-white">
                        Maker en laatste wijziging
                    </p>
                </div>
                <Badge
                    variant="outline"
                    className={
                        isSystemOrigin
                            ? 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-300'
                            : 'border-neutral-200 bg-neutral-50 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300'
                    }
                >
                    {isSystemOrigin ? 'Systeem / import' : 'Handmatig'}
                </Badge>
            </div>

            <dl className="mt-4 grid gap-3 text-xs leading-5">
                <ActivityRow
                    label="Aangemaakt"
                    actor={activity.createdBy}
                    dateTime={activity.createdAt}
                />
                <ActivityRow
                    label="Laatst bijgewerkt"
                    actor={activity.updatedBy}
                    dateTime={activity.updatedAt}
                />
            </dl>
        </section>
    );
}

export function AdminActivityByline({
    activity,
}: {
    activity: Pick<AdminActivity, 'updatedAt' | 'updatedBy'>;
}) {
    return (
        <div className="min-w-36 whitespace-nowrap">
            <time
                dateTime={activity.updatedAt}
                className="text-neutral-700 dark:text-neutral-300"
            >
                {dateTimeFormatter.format(new Date(activity.updatedAt))}
            </time>
            <p className="mt-0.5 max-w-48 truncate text-xs text-neutral-500">
                {activity.updatedBy?.name ?? 'Systeem / import'}
            </p>
        </div>
    );
}

function ActivityRow({
    actor,
    dateTime,
    label,
}: {
    actor: AdminActivityActor | null;
    dateTime: string;
    label: string;
}) {
    return (
        <div className="grid grid-cols-[1rem_minmax(0,1fr)] gap-x-2">
            {actor ? (
                <UserRound className="mt-0.5 size-4 text-neutral-400" />
            ) : (
                <Clock3 className="mt-0.5 size-4 text-neutral-400" />
            )}
            <div>
                <dt className="font-medium text-neutral-700 dark:text-neutral-300">
                    {label}
                </dt>
                <dd className="text-neutral-500 dark:text-neutral-400">
                    {actor?.name ?? 'Systeem / import'} ·{' '}
                    <time dateTime={dateTime}>
                        {dateTimeFormatter.format(new Date(dateTime))}
                    </time>
                </dd>
            </div>
        </div>
    );
}
