import { Link } from '@inertiajs/react';
import { CalendarX2, ChevronLeft, ChevronRight } from 'lucide-react';
import PublicEventCard from '@/components/public/public-event-card';
import { PublicHero } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { cn } from '@/lib/utils';
import { index as eventsIndex } from '@/routes/events';
import type {
    EventType,
    EventTypeFilter,
    PublicEventPaginator,
    SeoMetadata,
} from '@/types';

type Props = {
    activeType: EventType | null;
    events: PublicEventPaginator;
    seo: SeoMetadata;
    typeFilters: EventTypeFilter[];
};

export default function EventsIndex({
    activeType,
    events,
    seo,
    typeFilters,
}: Props) {
    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                title="De volgende heat begint hier."
                description="Bekijk aankomende trainingen, races, demo’s en workshops. Je ziet direct waar en wanneer we vliegen en of aanmelden mogelijk is."
                actions={[
                    { label: 'Bekijk alle events', href: eventsIndex.url() },
                    {
                        label: 'Alleen trainingen',
                        href: eventsIndex.url({
                            query: { type: 'training' },
                        }),
                    },
                ]}
                media={{
                    src: '/images/dds/racing/indoor-track.jpg',
                    alt: 'Indoor FPV-raceparcours van Dutch Drone Squad in Alkmaar',
                    position: '56% center',
                }}
            />

            <div className="bg-paper text-deep-signal dark:bg-night-950 dark:text-white">
                <section
                    aria-labelledby="events-heading"
                    className="mx-auto w-full max-w-[86rem] px-public-gutter py-16 sm:py-20"
                >
                    <div className="flex flex-col gap-8 border-b border-paddock-rule pb-8 lg:flex-row lg:items-end lg:justify-between dark:border-white/12">
                        <div>
                            <h2
                                id="events-heading"
                                className="font-public-display text-4xl font-semibold tracking-[-0.05em] sm:text-5xl"
                            >
                                Aankomende events
                            </h2>
                            <p
                                aria-live="polite"
                                className="mt-4 max-w-2xl text-base leading-7 text-signal-muted dark:text-night-400"
                            >
                                {events.total === 1
                                    ? '1 event staat klaar.'
                                    : `${events.total} events staan klaar.`}
                            </p>
                        </div>

                        <nav
                            aria-label="Filter events op type"
                            className="flex flex-wrap gap-2"
                        >
                            <FilterLink
                                label="Alles"
                                isActive={activeType === null}
                            />
                            {typeFilters.map((filter) => (
                                <FilterLink
                                    key={filter.value}
                                    label={filter.label}
                                    type={filter.value}
                                    isActive={activeType === filter.value}
                                />
                            ))}
                        </nav>
                    </div>

                    {events.data.length > 0 ? (
                        <>
                            <ul
                                aria-label="Aankomende events"
                                className="mt-6 divide-y divide-paddock-rule border-b border-paddock-rule dark:divide-white/12 dark:border-white/12"
                            >
                                {events.data.map((event) => (
                                    <li key={event.id}>
                                        <PublicEventCard
                                            event={event}
                                            variant="list"
                                        />
                                    </li>
                                ))}
                            </ul>

                            <Pagination
                                activeType={activeType}
                                currentPage={events.current_page}
                                lastPage={events.last_page}
                            />
                        </>
                    ) : (
                        <div className="mt-10 flex min-h-80 flex-col items-center justify-center rounded-sm border border-dashed border-paddock-rule bg-paddock px-6 py-14 text-center dark:border-white/15 dark:bg-night-900">
                            <span className="flex size-14 items-center justify-center rounded-full bg-white text-dds-blue shadow-sm dark:bg-white/8 dark:text-dds-cyan">
                                <CalendarX2 className="size-6" />
                            </span>
                            <h2 className="mt-6 font-public-display text-2xl font-semibold tracking-[-0.035em]">
                                Geen events gevonden
                            </h2>
                            <p className="mt-3 max-w-md text-sm leading-6 text-signal-muted dark:text-night-400">
                                {activeType === null
                                    ? 'Er staan nog geen aankomende events gepubliceerd. Kijk later opnieuw.'
                                    : 'Er zijn geen aankomende events van dit type. Bekijk de volledige agenda voor andere activiteiten.'}
                            </p>
                            {activeType !== null && (
                                <Link
                                    href={eventsIndex()}
                                    preserveScroll
                                    preserveState
                                    className="mt-6 inline-flex min-h-11 items-center rounded-sm bg-dds-orange px-5 py-3 text-sm font-semibold text-deep-signal transition-colors hover:bg-flight-400 focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:outline-none"
                                >
                                    Bekijk alle events
                                </Link>
                            )}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

type FilterLinkProps = {
    isActive: boolean;
    label: string;
    type?: EventType;
};

function FilterLink({ isActive, label, type }: FilterLinkProps) {
    return (
        <Link
            href={
                type === undefined
                    ? eventsIndex()
                    : eventsIndex({ query: { type } })
            }
            preserveScroll
            preserveState
            aria-current={isActive ? 'page' : undefined}
            className={cn(
                'inline-flex min-h-10 items-center rounded-sm border px-4 py-2 text-sm font-semibold transition-colors focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-2 focus-visible:outline-none dark:focus-visible:ring-offset-night-950',
                isActive
                    ? 'border-deep-signal bg-deep-signal text-white dark:border-dds-cyan dark:bg-dds-cyan dark:text-deep-signal'
                    : 'dark:text-night-300 border-paddock-rule bg-white text-signal-muted hover:border-dds-blue hover:text-deep-signal dark:border-white/15 dark:bg-night-900 dark:hover:border-dds-cyan dark:hover:text-white',
            )}
        >
            {label}
        </Link>
    );
}

type PaginationProps = {
    activeType: EventType | null;
    currentPage: number;
    lastPage: number;
};

function Pagination({ activeType, currentPage, lastPage }: PaginationProps) {
    if (lastPage <= 1) {
        return null;
    }

    const pageHref = (page: number) =>
        eventsIndex({
            query: {
                page,
                ...(activeType === null ? {} : { type: activeType }),
            },
        });

    return (
        <nav
            aria-label="Paginering events"
            className="mt-12 flex items-center justify-between border-t border-paddock-rule pt-6 dark:border-white/12"
        >
            {currentPage > 1 ? (
                <Link
                    href={pageHref(currentPage - 1)}
                    className="inline-flex min-h-10 items-center gap-2 rounded-sm px-3 text-sm font-semibold text-dds-blue hover:text-deep-signal focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none dark:text-dds-cyan dark:hover:text-white"
                >
                    <ChevronLeft className="size-4" />
                    Vorige
                </Link>
            ) : (
                <span />
            )}

            <span className="text-sm text-signal-muted dark:text-night-400">
                Pagina {currentPage} van {lastPage}
            </span>

            {currentPage < lastPage ? (
                <Link
                    href={pageHref(currentPage + 1)}
                    className="inline-flex min-h-10 items-center gap-2 rounded-sm px-3 text-sm font-semibold text-dds-blue hover:text-deep-signal focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none dark:text-dds-cyan dark:hover:text-white"
                >
                    Volgende
                    <ChevronRight className="size-4" />
                </Link>
            ) : (
                <span />
            )}
        </nav>
    );
}
