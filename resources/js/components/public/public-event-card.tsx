import { Link } from '@inertiajs/react';
import { ArrowUpRight, Clock3, MapPin } from 'lucide-react';
import {
    eventRegistrationLabels,
    eventTypeLabels,
    formatEventShortDate,
    formatEventTimeRange,
} from '@/lib/event-formatting';
import { cn } from '@/lib/utils';
import { show as eventShow } from '@/routes/events';
import type { PublicEventSummary } from '@/types';

type Props = {
    event: PublicEventSummary;
};

export default function PublicEventCard({ event }: Props) {
    const date = formatEventShortDate(event.startsAt);
    const isCancelled = event.status === 'cancelled';

    return (
        <Link
            href={eventShow(event.slug)}
            prefetch
            className="group flex h-full flex-col overflow-hidden rounded-xl border border-paddock-rule bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-dds-cyan hover:shadow-public-card focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:outline-none motion-reduce:transform-none motion-reduce:transition-none dark:border-white/10 dark:bg-night-900 dark:hover:border-dds-cyan"
        >
            <div className="relative aspect-[16/9] overflow-hidden bg-deep-signal">
                <img
                    src={event.image.src}
                    alt={event.image.alt}
                    loading="lazy"
                    className={cn(
                        'h-full w-full object-cover transition duration-500 group-hover:scale-[1.025] motion-reduce:transform-none motion-reduce:transition-none',
                        isCancelled && 'grayscale',
                    )}
                />
                <div className="absolute inset-0 bg-linear-to-t from-deep-signal/72 via-transparent to-transparent" />
                <div className="absolute top-4 left-4 flex gap-2">
                    <span className="rounded-full bg-paper/95 px-3 py-1 text-xs font-semibold text-deep-signal shadow-sm">
                        {eventTypeLabels[event.type]}
                    </span>
                    {isCancelled && (
                        <span className="rounded-full bg-red-600 px-3 py-1 text-xs font-semibold text-white shadow-sm">
                            Geannuleerd
                        </span>
                    )}
                </div>
            </div>

            <div className="flex flex-1 flex-col p-5 sm:p-6">
                <div className="flex items-start gap-5">
                    <div className="flex w-14 shrink-0 flex-col items-center border-r border-paddock-rule pr-5 text-center dark:border-white/12">
                        <span className="font-public-display text-3xl leading-none font-semibold tracking-[-0.05em] text-dds-orange">
                            {date.day}
                        </span>
                        <span className="mt-1 font-mono text-[0.7rem] tracking-[0.09em] text-signal-muted uppercase dark:text-night-400">
                            {date.month}
                        </span>
                    </div>
                    <div className="min-w-0">
                        <h2 className="font-public-display text-2xl leading-tight font-semibold tracking-[-0.035em] text-deep-signal dark:text-white">
                            {event.title}
                        </h2>
                        <p className="mt-3 inline-flex items-center gap-2 text-sm text-signal-muted dark:text-night-400">
                            <MapPin className="size-4 shrink-0 text-dds-blue dark:text-dds-cyan" />
                            {event.location.name}, {event.location.city}
                        </p>
                    </div>
                </div>

                {event.excerpt && (
                    <p className="mt-5 line-clamp-3 text-sm leading-6 text-signal-muted dark:text-night-400">
                        {event.excerpt}
                    </p>
                )}

                <div className="mt-auto flex items-end justify-between gap-4 border-t border-paddock-rule pt-5 dark:border-white/12">
                    <div className="flex flex-col gap-2 text-xs font-medium text-signal-muted dark:text-night-400">
                        <span className="inline-flex items-center gap-2">
                            <Clock3 className="size-4 text-dds-blue dark:text-dds-cyan" />
                            {formatEventTimeRange(event.startsAt, event.endsAt)}
                        </span>
                        <span
                            className={cn(
                                'font-semibold',
                                !isCancelled &&
                                    event.registrationStatus === 'open' &&
                                    'text-emerald-700 dark:text-emerald-400',
                                isCancelled && 'text-red-600 dark:text-red-400',
                            )}
                        >
                            {isCancelled
                                ? 'Dit event gaat niet door'
                                : eventRegistrationLabels[
                                      event.registrationStatus
                                  ]}
                        </span>
                    </div>
                    <ArrowUpRight className="size-5 shrink-0 text-dds-blue transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none dark:text-dds-cyan" />
                </div>
            </div>
        </Link>
    );
}
