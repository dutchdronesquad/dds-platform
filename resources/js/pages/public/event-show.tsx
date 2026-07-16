import { Link } from '@inertiajs/react';
import {
    ArrowUpRight,
    CalendarDays,
    CalendarRange,
    ChevronRight,
    Clock3,
    MapPin,
    Ticket,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { PublicEventRegistrationStatus } from '@/components/public/public-event-card';
import { CtaBand, PublicHero } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import {
    eventTypeLabels,
    formatEventDate,
    formatEventDateTime,
    formatEventLocation,
    formatEventPrice,
    formatEventTimeRange,
    getEventRegistrationDetail,
    getEventRegistrationLabel,
} from '@/lib/event-formatting';
import { index as eventsIndex } from '@/routes/events';
import { show as seasonShow } from '@/routes/seasons';
import type { PublicEventDetail, SeoMetadata } from '@/types';

type Props = {
    event: PublicEventDetail;
    seo: SeoMetadata;
};

export default function EventShow({ event, seo }: Props) {
    const isCancelled = event.status === 'cancelled';
    const registrationLabel = getRegistrationLabel(event, isCancelled);
    const canRegister = canRegisterForEvent(event, isCancelled);

    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                kicker={eventTypeLabels[event.type]}
                title={event.title}
                actions={[
                    {
                        label: isCancelled
                            ? 'Bekijk eventinformatie'
                            : canRegister
                              ? event.registrationStatus === 'waitlist'
                                  ? 'Bekijk de wachtlijst'
                                  : 'Aanmelden'
                              : 'Bekijk praktische info',
                        href: canRegister ? '#tickets' : '#praktische-info',
                    },
                    {
                        label: 'Terug naar de agenda',
                        href: eventsIndex.url(),
                    },
                ]}
                media={event.image}
                separatorTone="paper"
                showSeparator={false}
                size="compact"
            />

            <div className="bg-paper text-deep-signal dark:bg-night-950 dark:text-white">
                <section
                    id="praktische-info"
                    aria-label="Event in het kort"
                    className="scroll-mt-24 border-y border-paddock-rule bg-paddock dark:border-white/12 dark:bg-night-900"
                >
                    <div className="mx-auto w-full max-w-7xl px-public-gutter">
                        <dl
                            data-testid="event-quick-facts"
                            className="grid gap-px bg-paddock-rule sm:grid-cols-2 lg:grid-cols-4 dark:bg-white/12"
                        >
                            <EventQuickFact
                                icon={CalendarDays}
                                label="Datum"
                                value={formatEventDate(event.startsAt)}
                            />
                            <EventQuickFact
                                icon={Clock3}
                                label="Tijd"
                                value={formatEventTimeRange(
                                    event.startsAt,
                                    event.endsAt,
                                )}
                            />
                            <EventQuickFact
                                icon={MapPin}
                                label="Locatie"
                                value={formatEventLocation(
                                    event.location.name,
                                    event.location.city,
                                )}
                            />
                            <EventQuickFact
                                icon={Ticket}
                                label={
                                    event.seasonContext?.ticket != null
                                        ? 'Los ticket'
                                        : 'Prijs'
                                }
                                value={formatEventPrice(event.priceCents)}
                            />
                        </dl>
                    </div>
                </section>

                <section
                    aria-labelledby="briefing-heading"
                    className="mx-auto grid w-full max-w-7xl gap-12 px-public-gutter py-16 sm:py-20 lg:grid-cols-[minmax(0,1.08fr)_minmax(20rem,0.62fr)] lg:items-start lg:gap-20 lg:py-28"
                >
                    <div className="lg:pt-4">
                        <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                            Briefing
                        </p>
                        <h2
                            id="briefing-heading"
                            className="mt-4 font-public-display text-4xl font-semibold tracking-[-0.05em] sm:text-5xl"
                        >
                            Dit staat je te wachten.
                        </h2>
                        <div className="dark:text-night-300 mt-7 max-w-3xl text-base leading-8 whitespace-pre-line text-signal-muted sm:text-lg">
                            {event.content ??
                                'De inhoudelijke briefing voor dit event volgt binnenkort. De praktische gegevens en aanmeldstatus vind je hiernaast.'}
                        </div>
                    </div>

                    <div className="min-w-0 lg:sticky lg:top-28">
                        <RegistrationPanel
                            canRegister={canRegister}
                            event={event}
                            isCancelled={isCancelled}
                            registrationLabel={registrationLabel}
                        />
                    </div>
                </section>

                <section className="border-t border-paddock-rule bg-paddock dark:border-white/12 dark:bg-night-900">
                    <div className="mx-auto grid w-full max-w-7xl gap-10 px-public-gutter py-14 lg:grid-cols-[minmax(16rem,0.62fr)_minmax(0,1.38fr)] lg:items-stretch lg:gap-14 lg:py-20">
                        <div className="flex flex-col justify-center">
                            <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                                Vlieglocatie
                            </p>
                            <h2 className="mt-3 font-public-display text-3xl font-semibold tracking-[-0.045em]">
                                {event.location.name}
                            </h2>
                            <address className="dark:text-night-300 mt-5 text-base leading-7 text-signal-muted not-italic">
                                {event.location.street}{' '}
                                {event.location.houseNumber}
                                <br />
                                {event.location.postalCode}{' '}
                                {event.location.city}
                            </address>

                            <a
                                href={event.location.mapUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="mt-7 inline-flex min-h-11 w-fit items-center gap-2 rounded-sm border border-deep-signal/20 px-4 py-2.5 text-sm font-semibold text-deep-signal transition-colors hover:border-dds-blue hover:text-dds-blue focus-visible:ring-2 focus-visible:ring-dds-blue focus-visible:ring-offset-3 focus-visible:outline-none dark:border-white/20 dark:text-white dark:hover:border-dds-cyan dark:hover:text-dds-cyan dark:focus-visible:ring-dds-cyan dark:focus-visible:ring-offset-night-900"
                            >
                                Open in Google Maps
                                <ArrowUpRight
                                    aria-hidden="true"
                                    className="size-4"
                                />
                                <span className="sr-only">
                                    (opent in een nieuw tabblad)
                                </span>
                            </a>
                        </div>

                        <div className="relative min-h-72 overflow-hidden rounded-sm border border-paddock-rule bg-paper shadow-sm sm:min-h-96 dark:border-white/12 dark:bg-night-950">
                            <iframe
                                src={event.location.mapEmbedUrl}
                                title={`Google Maps-kaart van ${event.location.name}`}
                                loading="lazy"
                                referrerPolicy="strict-origin-when-cross-origin"
                                allowFullScreen
                                className="absolute inset-0 size-full border-0"
                            />
                        </div>
                    </div>
                </section>
            </div>

            <CtaBand
                eyebrow="Volgende heat"
                title="Bekijk wat er nog meer op de planning staat."
                description="In de agenda vind je alle gepubliceerde trainingen, races, demo’s en workshops."
                action={{
                    label: 'Alle events',
                    href: eventsIndex.url(),
                }}
            />
        </>
    );
}

type EventQuickFactProps = {
    icon: LucideIcon;
    label: string;
    value: string;
};

function EventQuickFact({ icon: Icon, label, value }: EventQuickFactProps) {
    return (
        <div className="flex min-h-20 min-w-0 items-center gap-4 bg-paddock px-5 py-4 text-deep-signal sm:min-h-24 sm:px-6 sm:py-5 dark:bg-night-900 dark:text-white">
            <span className="flex size-10 shrink-0 items-center justify-center rounded-sm border border-deep-signal/10 bg-white/70 text-dds-blue dark:border-white/12 dark:bg-white/6 dark:text-dds-cyan">
                <Icon aria-hidden="true" className="size-5" />
            </span>
            <div className="min-w-0">
                <dt className="font-mono text-[0.66rem] font-semibold tracking-[0.12em] text-signal-muted uppercase dark:text-night-400">
                    {label}
                </dt>
                <dd className="mt-1.5 text-sm leading-5 font-semibold text-deep-signal sm:text-[0.94rem] dark:text-white">
                    {value}
                </dd>
            </div>
        </div>
    );
}

type RegistrationPanelProps = {
    canRegister: boolean;
    event: PublicEventDetail;
    isCancelled: boolean;
    registrationLabel: string;
};

function RegistrationPanel({
    canRegister,
    event,
    isCancelled,
    registrationLabel,
}: RegistrationPanelProps) {
    const registrationDetail = getEventRegistrationDetail(event);
    const hasSeasonTicket = event.seasonContext?.ticket != null;

    return (
        <section
            id="tickets"
            aria-labelledby="registration-heading"
            className="relative scroll-mt-24 overflow-hidden rounded-sm border border-paddock-rule bg-white shadow-sm dark:border-white/12 dark:bg-night-900"
        >
            <span
                aria-hidden="true"
                className="absolute top-0 right-0 h-1.5 w-1/3 bg-dds-orange"
            />
            <div className="p-6 sm:p-8">
                <div className="flex items-start justify-between gap-4">
                    <p
                        data-testid="registration-panel-kicker"
                        className="flex min-h-8 items-center font-mono text-[0.66rem] font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan"
                    >
                        Aanmelden
                    </p>
                    <div
                        data-testid="registration-panel-status"
                        className="shrink-0"
                    >
                        <PublicEventRegistrationStatus
                            event={event}
                            label={registrationLabel}
                        />
                    </div>
                </div>
                <h2
                    id="registration-heading"
                    className="mt-3 font-public-display text-3xl font-semibold tracking-[-0.045em] text-deep-signal dark:text-white"
                >
                    {isCancelled
                        ? 'Dit event is geannuleerd.'
                        : canRegister
                          ? 'Aanmelden voor dit event.'
                          : 'Inschrijving voor dit event.'}
                </h2>
                <p className="mt-4 text-sm leading-6 text-signal-muted dark:text-night-400">
                    {isCancelled
                        ? 'Aanmelden is niet meer mogelijk. De overige eventinformatie blijft beschikbaar.'
                        : canRegister
                          ? `Je meldt je hiermee aan voor ${event.title}.`
                          : registrationDetail.note}
                </p>
                {canRegister && (
                    <a
                        href={event.registrationUrl ?? undefined}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="mt-6 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-sm bg-dds-orange px-5 py-3 text-sm font-semibold text-deep-signal transition-colors hover:bg-flight-400 focus-visible:ring-2 focus-visible:ring-dds-blue focus-visible:ring-offset-3 focus-visible:outline-none dark:focus-visible:ring-dds-cyan dark:focus-visible:ring-offset-night-900"
                    >
                        {event.registrationStatus === 'waitlist'
                            ? 'Meld je aan voor de wachtlijst'
                            : 'Meld je aan'}
                        <ArrowUpRight aria-hidden="true" className="size-4" />
                        <span className="sr-only">
                            (opent in een nieuw tabblad)
                        </span>
                    </a>
                )}
            </div>
            {!isCancelled && (
                <dl className="divide-y divide-paddock-rule border-t border-paddock-rule bg-paddock px-6 dark:divide-white/12 dark:border-white/12 dark:bg-night-950">
                    <RegistrationDetail
                        label="Capaciteit"
                        value={
                            event.capacity === null
                                ? 'Wordt bekendgemaakt'
                                : `${event.capacity} plekken totaal`
                        }
                    />
                    {event.registrationOpensAt !== null &&
                        registrationDetail.label !== 'Aanmelden vanaf' && (
                            <RegistrationDetail
                                label="Aanmelden vanaf"
                                value={formatEventDateTime(
                                    event.registrationOpensAt,
                                )}
                            />
                        )}
                    <RegistrationDetail
                        label={registrationDetail.label}
                        value={registrationDetail.value}
                    />
                </dl>
            )}

            {event.season !== null && (
                <Link
                    href={seasonShow(event.season.slug)}
                    prefetch
                    aria-label={`Bekijk seizoen ${event.season.name}`}
                    data-testid="event-season-context"
                    className="group grid min-w-0 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 border-t border-paddock-rule px-5 py-4 transition-colors hover:bg-paddock focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none dark:border-white/12 dark:hover:bg-white/5"
                >
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-sm bg-dds-blue/8 text-dds-blue dark:bg-dds-cyan/10 dark:text-dds-cyan">
                        <CalendarRange aria-hidden="true" className="size-5" />
                    </span>
                    <span className="min-w-0 flex-1">
                        <span className="flex flex-wrap items-center gap-2">
                            <span className="text-xs font-semibold tracking-[0.08em] text-signal-muted uppercase dark:text-night-400">
                                {isCancelled ? 'Seizoenprogramma' : 'Seizoen'}
                            </span>
                            {hasSeasonTicket && (
                                <span className="rounded-sm bg-emerald-50 px-2 py-1 text-[0.68rem] leading-none font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                    Ook in seizoensticket
                                </span>
                            )}
                        </span>
                        <span className="mt-1.5 block text-sm leading-5 font-semibold break-words text-deep-signal transition-colors group-hover:text-dds-blue dark:text-white dark:group-hover:text-dds-cyan">
                            {event.season.name}
                        </span>
                    </span>
                    <span className="flex size-8 shrink-0 items-center justify-center rounded-sm text-dds-blue transition-colors group-hover:bg-dds-blue/8 dark:text-dds-cyan dark:group-hover:bg-dds-cyan/10">
                        <span className="sr-only">Bekijk seizoen</span>
                        <ChevronRight
                            aria-hidden="true"
                            className="size-5 transition-transform group-hover:translate-x-0.5 motion-reduce:transition-none"
                        />
                    </span>
                </Link>
            )}
        </section>
    );
}

function RegistrationDetail({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <div className="flex items-baseline justify-between gap-4 py-4 text-sm">
            <dt className="text-signal-muted dark:text-night-400">{label}</dt>
            <dd className="text-right font-semibold text-deep-signal dark:text-white">
                {value}
            </dd>
        </div>
    );
}

function getRegistrationLabel(
    event: PublicEventDetail,
    isCancelled: boolean,
): string {
    if (isCancelled) {
        return 'Event geannuleerd';
    }

    if (
        event.registrationUrl === null &&
        ['open', 'waitlist'].includes(event.registrationStatus)
    ) {
        return 'Aanmeldlink volgt';
    }

    return getEventRegistrationLabel(event);
}

function canRegisterForEvent(
    event: PublicEventDetail,
    isCancelled: boolean,
): boolean {
    return (
        !isCancelled &&
        event.registrationUrl !== null &&
        ['open', 'waitlist'].includes(event.registrationStatus)
    );
}
