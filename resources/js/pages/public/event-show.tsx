import {
    CalendarDays,
    CircleAlert,
    Clock3,
    MapPin,
    Ticket,
    Users,
} from 'lucide-react';
import { CtaBand, PageIntro } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import {
    eventRegistrationLabels,
    eventTypeLabels,
    formatEventDate,
    formatEventPrice,
    formatEventTimeRange,
} from '@/lib/event-formatting';
import { index as eventsIndex } from '@/routes/events';
import type { PublicEventDetail, SeoMetadata } from '@/types';

type Props = {
    event: PublicEventDetail;
    seo: SeoMetadata;
};

export default function EventShow({ event, seo }: Props) {
    const isCancelled = event.status === 'cancelled';
    const isTraining = event.type === 'training';
    const registrationLabel = isCancelled
        ? 'Aanmelden niet mogelijk'
        : eventRegistrationLabels[event.registrationStatus];

    return (
        <>
            <PublicSeoHead metadata={seo} />

            {isCancelled && (
                <div
                    role="status"
                    className="border-b border-red-800 bg-red-700 text-white"
                >
                    <div className="mx-auto flex w-full max-w-7xl items-start gap-3 px-public-gutter py-4 text-sm font-semibold">
                        <CircleAlert className="mt-0.5 size-5 shrink-0" />
                        <p>
                            Dit event is geannuleerd en gaat niet door. De
                            informatie hieronder blijft beschikbaar ter
                            referentie.
                        </p>
                    </div>
                </div>
            )}

            <PageIntro
                eyebrow={eventTypeLabels[event.type]}
                title={event.title}
                description={`${formatEventDate(event.startsAt)} · ${event.location.name}, ${event.location.city}`}
                action={{
                    label: 'Terug naar de agenda',
                    href: eventsIndex.url(),
                }}
                media={event.image}
            />

            <div className="bg-paper text-deep-signal dark:bg-night-950 dark:text-white">
                <section
                    aria-labelledby="practical-heading"
                    className="mx-auto grid w-full max-w-7xl gap-12 px-public-gutter py-16 sm:py-20 lg:grid-cols-[1.3fr_0.7fr] lg:gap-20 lg:py-28"
                >
                    <div>
                        <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                            Briefing
                        </p>
                        <h2
                            id="practical-heading"
                            className="mt-4 font-public-display text-4xl font-semibold tracking-[-0.05em] sm:text-5xl"
                        >
                            Dit staat je te wachten.
                        </h2>
                        <div className="dark:text-night-300 mt-7 max-w-3xl text-base leading-8 whitespace-pre-line text-signal-muted sm:text-lg">
                            {event.content ??
                                'Meer inhoudelijke informatie over dit event volgt binnenkort.'}
                        </div>
                    </div>

                    <aside aria-label="Praktische eventinformatie">
                        <dl className="overflow-hidden rounded-xl border border-paddock-rule bg-white shadow-sm dark:border-white/12 dark:bg-night-900">
                            <DetailRow
                                icon={CalendarDays}
                                label="Datum"
                                value={formatEventDate(event.startsAt)}
                            />
                            <DetailRow
                                icon={Clock3}
                                label="Tijd"
                                value={formatEventTimeRange(
                                    event.startsAt,
                                    event.endsAt,
                                )}
                            />
                            <DetailRow
                                icon={MapPin}
                                label="Locatie"
                                value={`${event.location.name}, ${event.location.city}`}
                            />
                            <DetailRow
                                icon={Ticket}
                                label="Aanmelding"
                                value={registrationLabel}
                                isLast
                            />
                        </dl>
                    </aside>
                </section>

                {isTraining && (
                    <TrainingDetails event={event} isCancelled={isCancelled} />
                )}

                <section className="border-t border-paddock-rule bg-paddock dark:border-white/12 dark:bg-night-900">
                    <div className="mx-auto grid w-full max-w-7xl gap-8 px-public-gutter py-14 md:grid-cols-[0.72fr_1.28fr] md:items-start lg:py-20">
                        <div>
                            <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                                Vlieglocatie
                            </p>
                            <h2 className="mt-3 font-public-display text-3xl font-semibold tracking-[-0.045em]">
                                {event.location.name}
                            </h2>
                        </div>
                        <address className="dark:text-night-300 text-base leading-7 text-signal-muted not-italic">
                            {event.location.street} {event.location.houseNumber}
                            <br />
                            {event.location.postalCode} {event.location.city}
                        </address>
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

type DetailRowProps = {
    icon: typeof CalendarDays;
    isLast?: boolean;
    label: string;
    value: string;
};

function DetailRow({
    icon: Icon,
    isLast = false,
    label,
    value,
}: DetailRowProps) {
    return (
        <div
            className={`flex gap-4 p-5 sm:p-6 ${isLast ? '' : 'border-b border-paddock-rule dark:border-white/12'}`}
        >
            <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-air text-dds-blue dark:bg-dds-cyan/10 dark:text-dds-cyan">
                <Icon className="size-5" />
            </span>
            <div>
                <dt className="font-mono text-[0.68rem] tracking-[0.1em] text-signal-muted uppercase dark:text-night-400">
                    {label}
                </dt>
                <dd className="mt-1 font-semibold text-deep-signal dark:text-white">
                    {value}
                </dd>
            </div>
        </div>
    );
}

type TrainingDetailsProps = {
    event: PublicEventDetail;
    isCancelled: boolean;
};

function TrainingDetails({ event, isCancelled }: TrainingDetailsProps) {
    const canRegister =
        !isCancelled &&
        event.registrationUrl !== null &&
        ['open', 'waitlist'].includes(event.registrationStatus);

    return (
        <section className="bg-deep-signal text-white">
            <div className="mx-auto w-full max-w-7xl px-public-gutter py-16 sm:py-20 lg:py-24">
                <div className="grid gap-10 lg:grid-cols-[0.72fr_1.28fr] lg:gap-16">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.12em] text-dds-cyan uppercase">
                            Training details
                        </p>
                        <h2 className="mt-4 font-public-display text-4xl font-semibold tracking-[-0.05em]">
                            Klaar voor de track?
                        </h2>
                        <p className="mt-5 max-w-lg text-base leading-7 text-white/65">
                            Controleer de ticketprijs, capaciteit en
                            aanmeldstatus voordat je je accu’s inpakt.
                        </p>
                    </div>

                    <div>
                        <dl className="grid gap-px overflow-hidden rounded-xl bg-white/12 sm:grid-cols-2">
                            <TrainingFact
                                icon={Ticket}
                                label="Ticket"
                                value={formatEventPrice(event.priceCents)}
                            />
                            <TrainingFact
                                icon={Users}
                                label="Capaciteit"
                                value={
                                    event.capacity === null
                                        ? 'Wordt bekendgemaakt'
                                        : `${event.capacity} piloten`
                                }
                            />
                            <TrainingFact
                                icon={CalendarDays}
                                label="Aanmelden vanaf"
                                value={
                                    event.registrationOpensAt === null
                                        ? 'Nog niet bekend'
                                        : formatEventDate(
                                              event.registrationOpensAt,
                                          )
                                }
                            />
                            <TrainingFact
                                icon={Clock3}
                                label="Deadline"
                                value={
                                    event.registrationDeadlineAt === null
                                        ? 'Geen deadline vermeld'
                                        : formatEventDate(
                                              event.registrationDeadlineAt,
                                          )
                                }
                            />
                        </dl>

                        <div className="mt-6 flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                            {canRegister && (
                                <a
                                    href={event.registrationUrl ?? undefined}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex min-h-11 items-center rounded-sm bg-dds-orange px-5 py-3 text-sm font-semibold text-deep-signal transition-colors hover:bg-flight-400 focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:ring-offset-deep-signal focus-visible:outline-none"
                                >
                                    {event.registrationStatus === 'waitlist'
                                        ? 'Meld je aan voor de wachtlijst'
                                        : 'Meld je aan'}
                                </a>
                            )}
                            <p className="text-sm font-semibold text-white/72">
                                {isCancelled
                                    ? 'Deze training is geannuleerd.'
                                    : eventRegistrationLabels[
                                          event.registrationStatus
                                      ]}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

type TrainingFactProps = {
    icon: typeof CalendarDays;
    label: string;
    value: string;
};

function TrainingFact({ icon: Icon, label, value }: TrainingFactProps) {
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
