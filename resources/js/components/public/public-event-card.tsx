import { Link } from '@inertiajs/react';
import {
    ArrowUpRight,
    Circle,
    CircleCheck,
    CircleMinus,
    CircleX,
    Clock3,
    LockKeyhole,
    MapPin,
    Ticket,
    Users,
} from 'lucide-react';
import {
    eventRegistrationLabels,
    eventTypeLabels,
    formatEventDate,
    formatEventPrice,
    formatEventShortDate,
    formatEventTimeRange,
} from '@/lib/event-formatting';
import { cn } from '@/lib/utils';
import { show as eventShow } from '@/routes/events';
import type { PublicEventSummary } from '@/types';

type Props = {
    event: PublicEventSummary;
    variant?: 'card' | 'list';
};

type EventDate = ReturnType<typeof formatEventShortDate>;

export default function PublicEventCard({ event, variant = 'card' }: Props) {
    if (variant === 'list') {
        return <PublicEventListItem event={event} />;
    }

    return <PublicEventGridCard event={event} />;
}

function PublicEventGridCard({ event }: Pick<Props, 'event'>) {
    const date = formatEventShortDate(event.startsAt);

    return (
        <Link
            href={eventShow(event.slug)}
            prefetch
            className="group relative flex h-full flex-col overflow-hidden rounded-sm border border-paddock-rule bg-white transition-colors hover:border-dds-blue focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:outline-none dark:border-white/10 dark:bg-night-900 dark:hover:border-dds-cyan"
        >
            <EventImage event={event} className="aspect-[16/9]">
                <span
                    aria-hidden="true"
                    className="absolute top-0 right-0 z-10 h-1 w-1/3 bg-dds-orange"
                />
                <EventCardDate date={date} event={event} />
            </EventImage>

            <div className="flex flex-1 flex-col p-5 sm:p-6">
                <EventEyebrow date={date} event={event} />

                <h2 className="mt-3 line-clamp-2 font-public-display text-2xl leading-[1.08] font-semibold tracking-[-0.04em] text-deep-signal transition-colors group-hover:text-dds-blue sm:text-[1.7rem] dark:text-white dark:group-hover:text-dds-cyan">
                    {event.title}
                </h2>

                <EventLocation event={event} className="mt-3" />

                <div className="mt-auto pt-6">
                    <EventFacts event={event} variant="card" />

                    <div className="flex items-center justify-between gap-4 pt-5">
                        <PublicEventRegistrationStatus event={event} />
                        <span className="inline-flex items-center gap-2 text-sm font-semibold text-dds-blue dark:text-dds-cyan">
                            Bekijk event
                            <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none" />
                        </span>
                    </div>
                </div>
            </div>
        </Link>
    );
}

function PublicEventListItem({ event }: Pick<Props, 'event'>) {
    const date = formatEventShortDate(event.startsAt);

    return (
        <Link
            href={eventShow(event.slug)}
            prefetch
            className="group grid gap-5 px-1 py-6 transition-colors hover:bg-white/65 focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none focus-visible:ring-inset sm:px-4 lg:grid-cols-[5.25rem_11rem_minmax(0,1fr)_10.5rem_10rem] lg:items-center lg:gap-6 lg:py-5 xl:grid-cols-[5.5rem_14rem_minmax(0,1fr)_11rem_10.5rem] xl:gap-7 dark:hover:bg-white/4"
        >
            <EventDateRail date={date} event={event} />

            <EventImage
                event={event}
                className="aspect-[5/2] w-full rounded-sm sm:aspect-[2/1] lg:aspect-auto lg:h-40 xl:h-44"
            />

            <div className="min-w-0 lg:py-1">
                <EventEyebrow
                    date={date}
                    event={event}
                    showDate
                    hideDateOnDesktop
                />

                <h2 className="mt-2 line-clamp-2 font-public-display text-2xl leading-[1.08] font-semibold tracking-[-0.04em] text-deep-signal xl:text-[1.8rem] dark:text-white">
                    {event.title}
                </h2>

                <EventLocation event={event} className="mt-2" />

                {event.excerpt && (
                    <p className="mt-4 line-clamp-2 max-w-2xl text-sm leading-6 text-signal-muted dark:text-night-400">
                        {event.excerpt}
                    </p>
                )}
            </div>

            <EventFacts event={event} variant="list" />

            <div className="flex items-center justify-between gap-4 border-t border-paddock-rule pt-4 lg:min-h-28 lg:flex-col lg:items-stretch lg:justify-center lg:border-t-0 lg:border-l lg:px-4 lg:pt-0 lg:text-center dark:border-white/12">
                <PublicEventRegistrationStatus
                    event={event}
                    className="lg:w-full"
                />
                <span className="inline-flex items-center gap-2 text-sm font-semibold text-dds-blue lg:justify-center dark:text-dds-cyan">
                    Bekijk event
                    <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none" />
                </span>
            </div>
        </Link>
    );
}

function EventCardDate({
    date,
    event,
}: {
    date: EventDate;
    event: PublicEventSummary;
}) {
    return (
        <time
            dateTime={event.startsAt}
            aria-label={formatEventDate(event.startsAt)}
            className="absolute bottom-0 left-0 z-10 grid min-w-[9.25rem] grid-cols-[auto_1fr] items-center gap-3 border-t border-r border-paddock-rule bg-paper px-4 py-3 dark:border-white/12 dark:bg-night-900"
        >
            <span className="font-public-display text-[2.75rem] leading-none font-semibold tracking-[-0.06em] text-dds-orange">
                {date.day}
            </span>
            <span className="flex min-w-0 flex-col">
                <span className="font-mono text-[0.68rem] leading-4 font-semibold tracking-[0.09em] text-deep-signal uppercase dark:text-white">
                    {date.month}
                    {date.year !== null ? ` ${date.year}` : ''}
                </span>
                <span className="mt-0.5 text-xs leading-4 font-medium text-signal-muted capitalize dark:text-night-400">
                    {date.weekday}
                </span>
            </span>
        </time>
    );
}

function EventDateRail({
    date,
    event,
}: {
    date: EventDate;
    event: PublicEventSummary;
}) {
    return (
        <div className="relative hidden h-full min-h-36 items-center justify-start border-r border-paddock-rule lg:flex dark:border-white/12">
            <time
                dateTime={event.startsAt}
                className="flex flex-col items-center"
            >
                <span className="font-public-display text-4xl leading-none font-semibold tracking-[-0.055em] text-dds-orange">
                    {date.day}
                </span>
                <span className="mt-1 font-mono text-[0.72rem] font-semibold tracking-[0.1em] text-signal-muted uppercase dark:text-night-400">
                    {date.month}
                </span>
                <span className="mt-2 font-mono text-[0.58rem] tracking-[0.08em] text-signal-muted uppercase dark:text-night-400">
                    {date.weekday}
                </span>
                {date.year !== null && (
                    <span className="mt-1 font-mono text-[0.62rem] font-semibold text-signal-muted dark:text-night-400">
                        {date.year}
                    </span>
                )}
            </time>
            <Circle
                aria-hidden="true"
                className="absolute top-1/2 right-0 size-3.5 translate-x-1/2 -translate-y-1/2 fill-dds-orange text-dds-orange"
            />
        </div>
    );
}

function EventImage({
    children,
    className,
    event,
}: {
    children?: React.ReactNode;
    className?: string;
    event: PublicEventSummary;
}) {
    const isCancelled = event.status === 'cancelled';

    return (
        <div
            className={cn('relative overflow-hidden bg-deep-signal', className)}
        >
            <img
                src={event.image.src}
                alt={event.image.alt}
                loading="lazy"
                className={cn(
                    'h-full w-full object-cover transition duration-500 group-hover:scale-[1.025] motion-reduce:transform-none motion-reduce:transition-none',
                    isCancelled && 'grayscale',
                )}
            />
            {children}
        </div>
    );
}

function EventEyebrow({
    className,
    date,
    event,
    hideDateOnDesktop = false,
    showDate = false,
}: {
    className?: string;
    date: EventDate;
    event: PublicEventSummary;
    hideDateOnDesktop?: boolean;
    showDate?: boolean;
}) {
    return (
        <p
            className={cn(
                'flex flex-wrap items-center gap-2 font-mono text-[0.68rem] font-semibold tracking-[0.09em] text-dds-blue uppercase dark:text-dds-cyan',
                className,
            )}
        >
            <span>{eventTypeLabels[event.type]}</span>
            {showDate && (
                <span
                    className={cn('contents', hideDateOnDesktop && 'lg:hidden')}
                >
                    <span aria-hidden="true" className="text-dds-orange">
                        •
                    </span>
                    <time
                        dateTime={event.startsAt}
                        className="tracking-[0.04em] text-signal-muted dark:text-night-400"
                    >
                        {date.day} {date.month}
                        {date.year !== null ? ` ${date.year}` : ''}
                    </time>
                </span>
            )}
            {event.season !== null && (
                <>
                    <span aria-hidden="true" className="text-dds-orange">
                        •
                    </span>
                    <span className="tracking-[0.04em] text-signal-muted dark:text-night-400">
                        {event.season.name}
                    </span>
                </>
            )}
        </p>
    );
}

function EventLocation({
    className,
    event,
}: {
    className?: string;
    event: PublicEventSummary;
}) {
    return (
        <p
            className={cn(
                'flex items-start gap-2 text-sm leading-5 text-signal-muted dark:text-night-400',
                className,
            )}
        >
            <MapPin className="mt-0.5 size-4 shrink-0 text-dds-blue dark:text-dds-cyan" />
            <span>
                {event.location.name}, {event.location.city}
            </span>
        </p>
    );
}

function EventFacts({
    event,
    variant,
}: {
    event: PublicEventSummary;
    variant: 'card' | 'list';
}) {
    return (
        <dl
            className={cn(
                'text-xs font-medium text-signal-muted dark:text-night-400',
                variant === 'card' &&
                    '-mx-5 grid grid-cols-3 divide-x divide-paddock-rule border-y border-paddock-rule bg-deep-signal/[0.025] py-4 sm:-mx-6 dark:divide-white/12 dark:border-white/12 dark:bg-white/[0.025]',
                variant === 'list' &&
                    'grid grid-cols-3 gap-3 border-t border-paddock-rule pt-5 lg:min-h-28 lg:grid-cols-1 lg:content-center lg:gap-3 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-6 dark:border-white/12',
            )}
        >
            <EventFact label="Tijd" variant={variant}>
                <Clock3 className="size-4 shrink-0 text-dds-blue dark:text-dds-cyan" />
                {formatEventTimeRange(event.startsAt, event.endsAt)}
            </EventFact>
            <EventFact label="Prijs" variant={variant}>
                <Ticket className="size-4 shrink-0 text-dds-blue dark:text-dds-cyan" />
                {formatEventPrice(event.priceCents)}
            </EventFact>
            <EventFact label="Capaciteit" variant={variant}>
                <Users className="size-4 shrink-0 text-dds-blue dark:text-dds-cyan" />
                {event.capacity === null ? (
                    'Plekken volgen'
                ) : (
                    <span>
                        {event.capacity} plekken
                        {variant === 'list' && (
                            <span className="hidden lg:inline"> totaal</span>
                        )}
                    </span>
                )}
            </EventFact>
        </dl>
    );
}

function EventFact({
    children,
    label,
    variant,
}: {
    children: React.ReactNode;
    label: string;
    variant: 'card' | 'list';
}) {
    return (
        <div
            className={cn(
                'min-w-0',
                variant === 'card' && 'flex items-center justify-center px-2',
            )}
        >
            <dt className="sr-only">{label}</dt>
            <dd
                className={cn(
                    'flex items-start gap-2 leading-5',
                    variant === 'card' &&
                        'items-center justify-center gap-1.5 text-center text-[0.7rem] whitespace-nowrap',
                )}
            >
                {children}
            </dd>
        </div>
    );
}

export function PublicEventRegistrationStatus({
    className,
    event,
    label,
}: Pick<Props, 'event'> & { className?: string; label?: string }) {
    const isCancelled = event.status === 'cancelled';
    const StatusIcon = isCancelled
        ? CircleX
        : event.registrationStatus === 'open'
          ? CircleCheck
          : event.registrationStatus === 'waitlist'
            ? Clock3
            : event.registrationStatus === 'full'
              ? CircleMinus
              : LockKeyhole;

    return (
        <span
            className={cn(
                'inline-flex min-h-8 w-fit items-center justify-center gap-2 rounded-md border px-3 py-1.5 text-xs leading-4 font-semibold',
                isCancelled &&
                    'border-red-200 bg-red-50/70 text-red-700 dark:border-red-400/25 dark:bg-red-500/10 dark:text-red-300',
                !isCancelled &&
                    event.registrationStatus === 'open' &&
                    'border-emerald-200 bg-emerald-50/70 text-emerald-700 dark:border-emerald-400/25 dark:bg-emerald-500/10 dark:text-emerald-300',
                !isCancelled &&
                    event.registrationStatus === 'waitlist' &&
                    'border-amber-200 bg-amber-50/70 text-amber-800 dark:border-amber-400/25 dark:bg-amber-500/10 dark:text-amber-200',
                !isCancelled &&
                    event.registrationStatus === 'full' &&
                    'border-orange-200 bg-orange-50/70 text-orange-800 dark:border-orange-400/25 dark:bg-orange-500/10 dark:text-orange-200',
                !isCancelled &&
                    event.registrationStatus === 'closed' &&
                    'dark:text-night-300 border-slate-200 bg-slate-50/80 text-slate-700 dark:border-white/15 dark:bg-white/6',
                className,
            )}
        >
            <StatusIcon aria-hidden="true" className="size-3.5 shrink-0" />
            {label ??
                (isCancelled
                    ? 'Dit event gaat niet door'
                    : eventRegistrationLabels[event.registrationStatus])}
        </span>
    );
}
