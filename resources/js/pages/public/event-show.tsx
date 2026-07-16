import {
    ArrowUpRight,
    CalendarDays,
    Clock3,
    MapPin,
    Ticket,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { PublicEventRegistrationStatus } from '@/components/public/public-event-card';
import { CtaBand, PublicHero } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import {
    eventRegistrationLabels,
    eventTypeLabels,
    formatEventDate,
    formatEventDateTime,
    formatEventPrice,
    formatEventTimeRange,
} from '@/lib/event-formatting';
import { cn } from '@/lib/utils';
import { index as eventsIndex } from '@/routes/events';
import type { PublicEventDetail, SeoMetadata } from '@/types';

type Props = {
    event: PublicEventDetail;
    seo: SeoMetadata;
};

export default function EventShow({ event, seo }: Props) {
    const isCancelled = event.status === 'cancelled';
    const registrationLabel = getRegistrationLabel(event, isCancelled);
    const canRegister = canRegisterForEvent(event, isCancelled);
    const heroKicker = [eventTypeLabels[event.type], event.season?.name]
        .filter(Boolean)
        .join(' · ');

    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                kicker={heroKicker}
                title={event.title}
                actions={[
                    {
                        label: isCancelled
                            ? 'Bekijk eventinformatie'
                            : canRegister
                              ? event.registrationStatus === 'waitlist'
                                  ? 'Bekijk de wachtlijst'
                                  : 'Tickets & aanmelden'
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
                size="compact"
            />

            <div className="bg-paper text-deep-signal dark:bg-night-950 dark:text-white">
                <section
                    id="praktische-info"
                    aria-label="Event in het kort"
                    className="scroll-mt-24"
                >
                    <div className="mx-auto w-full max-w-7xl px-public-gutter">
                        <div className="relative overflow-hidden border-x border-y border-deep-signal bg-deep-signal shadow-xl shadow-deep-signal/12 dark:border-white/12">
                            <span
                                aria-hidden="true"
                                className="absolute top-0 left-0 z-10 h-1 w-24 bg-dds-orange sm:w-36"
                            />
                            <span
                                aria-hidden="true"
                                className="absolute top-0 right-0 z-10 h-1 w-16 bg-dds-cyan sm:w-24"
                            />

                            <dl className="grid gap-px bg-white/12 sm:grid-cols-2 lg:grid-cols-[1.08fr_0.85fr_1.27fr_1.1fr]">
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
                                    value={`${event.location.name}, ${event.location.city}`}
                                />
                                <EventQuickFact
                                    icon={Ticket}
                                    label={
                                        isCancelled
                                            ? 'Eventstatus'
                                            : 'Aanmelding'
                                    }
                                >
                                    <PublicEventRegistrationStatus
                                        event={event}
                                        label={registrationLabel}
                                        className="rounded-sm"
                                    />
                                </EventQuickFact>
                            </dl>
                        </div>
                    </div>
                </section>

                <section
                    aria-labelledby="briefing-heading"
                    className={cn(
                        'mx-auto grid w-full max-w-7xl gap-12 px-public-gutter py-16 sm:py-20 lg:items-start lg:gap-20 lg:py-28',
                        !isCancelled &&
                            'lg:grid-cols-[minmax(0,1.08fr)_minmax(22rem,0.72fr)]',
                    )}
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

                    {!isCancelled && (
                        <RegistrationPanel
                            event={event}
                            canRegister={canRegister}
                        />
                    )}
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
    children?: ReactNode;
    icon: LucideIcon;
    label: string;
    value?: string;
};

function EventQuickFact({
    children,
    icon: Icon,
    label,
    value,
}: EventQuickFactProps) {
    return (
        <div className="flex min-h-24 min-w-0 items-center gap-4 bg-deep-signal px-5 py-5 text-white sm:min-h-28 sm:px-6 sm:py-6">
            <span className="flex size-10 shrink-0 items-center justify-center rounded-sm border border-white/12 bg-white/6 text-dds-cyan">
                <Icon aria-hidden="true" className="size-5" />
            </span>
            <div className="min-w-0">
                <dt className="font-mono text-[0.66rem] font-semibold tracking-[0.12em] text-white/45 uppercase">
                    {label}
                </dt>
                <dd className="mt-2 text-sm leading-5 font-semibold text-white sm:text-[0.94rem]">
                    {children ?? value}
                </dd>
            </div>
        </div>
    );
}

type RegistrationPanelProps = {
    canRegister: boolean;
    event: PublicEventDetail;
};

function RegistrationPanel({ canRegister, event }: RegistrationPanelProps) {
    return (
        <aside
            id="tickets"
            aria-label="Tickets en inschrijving"
            className="relative order-first scroll-mt-24 overflow-hidden rounded-sm bg-deep-signal text-white shadow-lg shadow-deep-signal/10 lg:sticky lg:top-28 lg:order-last"
        >
            <span
                aria-hidden="true"
                className="absolute top-0 right-0 h-1.5 w-1/3 bg-dds-orange"
            />

            <div className="p-6 sm:p-8">
                <p className="text-xs font-semibold tracking-[0.12em] text-dds-cyan uppercase">
                    Tickets & inschrijving
                </p>
                <h2 className="mt-4 font-public-display text-3xl font-semibold tracking-[-0.045em]">
                    {canRegister ? 'Klaar voor de start?' : 'Plan je bezoek.'}
                </h2>

                {canRegister && (
                    <a
                        href={event.registrationUrl ?? undefined}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="mt-6 inline-flex min-h-11 items-center gap-2 rounded-sm bg-dds-orange px-5 py-3 text-sm font-semibold text-deep-signal transition-colors hover:bg-flight-400 focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:ring-offset-deep-signal focus-visible:outline-none"
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

            <dl className="grid grid-cols-2 gap-px border-t border-white/12 bg-white/12">
                <RegistrationFact
                    icon={Ticket}
                    label="Ticket"
                    value={formatEventPrice(event.priceCents)}
                />
                <RegistrationFact
                    icon={Users}
                    label="Capaciteit"
                    value={
                        event.capacity === null
                            ? 'Wordt bekendgemaakt'
                            : `${event.capacity} plekken totaal`
                    }
                />
                <RegistrationFact
                    icon={CalendarDays}
                    label="Aanmelden vanaf"
                    value={
                        event.registrationOpensAt === null
                            ? 'Nog niet bekend'
                            : formatEventDate(event.registrationOpensAt)
                    }
                />
                <RegistrationFact
                    icon={Clock3}
                    label="Deadline"
                    value={
                        event.registrationDeadlineAt === null
                            ? 'Geen deadline vermeld'
                            : formatEventDateTime(event.registrationDeadlineAt)
                    }
                />
            </dl>
        </aside>
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

    return eventRegistrationLabels[event.registrationStatus];
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

type RegistrationFactProps = {
    icon: LucideIcon;
    label: string;
    value: string;
};

function RegistrationFact({ icon: Icon, label, value }: RegistrationFactProps) {
    return (
        <div className="bg-deep-signal p-5 sm:p-6">
            <Icon className="size-5 text-dds-cyan" />
            <dt className="mt-5 font-mono text-[0.68rem] tracking-[0.1em] text-white/52 uppercase">
                {label}
            </dt>
            <dd className="mt-2 font-public-display text-xl font-semibold tracking-[-0.025em]">
                {value}
            </dd>
        </div>
    );
}
