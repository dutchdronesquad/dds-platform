import type {
    EventRegistrationStatus,
    EventStatus,
    EventType,
    SeasonTicketSalesState,
} from '@/types';

const dateFormatter = new Intl.DateTimeFormat('nl-NL', {
    day: 'numeric',
    month: 'long',
    weekday: 'long',
    year: 'numeric',
});

const shortDateFormatter = new Intl.DateTimeFormat('nl-NL', {
    day: '2-digit',
    month: 'short',
    weekday: 'long',
    year: 'numeric',
});

const timeFormatter = new Intl.DateTimeFormat('nl-NL', {
    hour: '2-digit',
    minute: '2-digit',
});

const dateTimeFormatter = new Intl.DateTimeFormat('nl-NL', {
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    month: 'long',
    year: 'numeric',
});

const priceFormatter = new Intl.NumberFormat('nl-NL', {
    currency: 'EUR',
    style: 'currency',
});

const monthYearFormatter = new Intl.DateTimeFormat('nl-NL', {
    month: 'long',
    year: 'numeric',
});

export const eventTypeLabels: Record<EventType, string> = {
    demo: 'Demo',
    other: 'Event',
    race: 'Race',
    training: 'Training',
    workshop: 'Workshop',
};

export const eventRegistrationLabels: Record<EventRegistrationStatus, string> =
    {
        closed: 'Inschrijving gesloten',
        full: 'Vol',
        open: 'Aanmelden mogelijk',
        waitlist: 'Wachtlijst',
    };

export const eventStatusLabels: Record<EventStatus, string> = {
    cancelled: 'Geannuleerd',
    published: 'Gepland',
};

export const seasonTicketSalesLabels: Record<SeasonTicketSalesState, string> = {
    available: 'Verkoop open',
    closed: 'Verkoop gesloten',
    coming_soon: 'Binnenkort verkrijgbaar',
    sold_out: 'Uitverkocht',
};

export function formatEventDate(value: string): string {
    return dateFormatter.format(new Date(value));
}

export function formatEventDateTime(value: string): string {
    return dateTimeFormatter.format(new Date(value));
}

export function formatEventShortDate(value: string): {
    day: string;
    month: string;
    weekday: string;
    year: string;
} {
    const parts = shortDateFormatter.formatToParts(new Date(value));
    const year = parts.find((part) => part.type === 'year')?.value ?? '';

    return {
        day: parts.find((part) => part.type === 'day')?.value ?? '',
        month: (
            parts.find((part) => part.type === 'month')?.value ?? ''
        ).replace('.', ''),
        weekday: parts.find((part) => part.type === 'weekday')?.value ?? '',
        year,
    };
}

export function formatSeasonDateRange(
    startsAt: string | null,
    endsAt: string | null,
): string {
    if (startsAt === null || endsAt === null) {
        return 'Data volgen';
    }

    return `${monthYearFormatter.format(new Date(startsAt))} – ${monthYearFormatter.format(new Date(endsAt))}`;
}

export function formatEventTimeRange(
    startsAt: string,
    endsAt: string | null,
): string {
    const start = new Date(startsAt);

    if (endsAt === null) {
        return timeFormatter.format(start);
    }

    const end = new Date(endsAt);
    const sameDay = start.toDateString() === end.toDateString();

    if (sameDay) {
        return `${timeFormatter.format(start)}–${timeFormatter.format(end)}`;
    }

    return `${timeFormatter.format(start)} – ${formatEventDate(endsAt)} ${timeFormatter.format(end)}`;
}

export function formatEventPrice(priceCents: number | null): string {
    if (priceCents === null) {
        return 'Prijs volgt';
    }

    if (priceCents === 0) {
        return 'Gratis';
    }

    return priceFormatter.format(priceCents / 100);
}

type RegistrationTiming = {
    registrationDeadlineAt: string | null;
    registrationOpensAt: string | null;
    registrationStatus: EventRegistrationStatus;
    status: EventStatus;
};

export type EventRegistrationDetail = {
    label: string;
    note: string;
    value: string;
};

export function isEventRegistrationUpcoming(
    event: RegistrationTiming,
    now = new Date(),
): boolean {
    return (
        event.status !== 'cancelled' &&
        event.registrationStatus === 'closed' &&
        event.registrationOpensAt !== null &&
        new Date(event.registrationOpensAt) > now
    );
}

export function getEventRegistrationLabel(
    event: RegistrationTiming,
    now = new Date(),
): string {
    if (event.status === 'cancelled') {
        return eventStatusLabels.cancelled;
    }

    if (isEventRegistrationUpcoming(event, now)) {
        return 'Nog niet geopend';
    }

    return eventRegistrationLabels[event.registrationStatus];
}

export function getEventRegistrationDetail(
    event: RegistrationTiming,
    now = new Date(),
): EventRegistrationDetail {
    if (event.status === 'cancelled') {
        return {
            label: 'Status',
            note: 'Event geannuleerd',
            value: 'Event geannuleerd',
        };
    }

    if (event.registrationStatus === 'full') {
        return {
            label: 'Reden',
            note: 'Gesloten · alle plekken zijn bezet',
            value: 'Alle plekken zijn bezet',
        };
    }

    if (event.registrationStatus === 'open') {
        if (event.registrationDeadlineAt === null) {
            return {
                label: 'Status',
                note: 'Aanmelden is nu mogelijk',
                value: 'Aanmelden mogelijk',
            };
        }

        const deadline = formatEventDateTime(event.registrationDeadlineAt);

        return {
            label: 'Aanmelden tot',
            note: `Open tot ${deadline}`,
            value: deadline,
        };
    }

    if (event.registrationStatus === 'waitlist') {
        if (event.registrationDeadlineAt === null) {
            return {
                label: 'Status',
                note: 'De wachtlijst is geopend',
                value: 'Wachtlijst geopend',
            };
        }

        const deadline = formatEventDateTime(event.registrationDeadlineAt);

        return {
            label: 'Wachtlijst tot',
            note: `Wachtlijst open tot ${deadline}`,
            value: deadline,
        };
    }

    if (
        isEventRegistrationUpcoming(event, now) &&
        event.registrationOpensAt !== null
    ) {
        const opensAt = formatEventDateTime(event.registrationOpensAt);

        return {
            label: 'Aanmelden vanaf',
            note: `Nog niet geopend · inschrijving opent op ${opensAt}`,
            value: opensAt,
        };
    }

    if (
        event.registrationDeadlineAt !== null &&
        new Date(event.registrationDeadlineAt) <= now
    ) {
        const deadline = formatEventDateTime(event.registrationDeadlineAt);

        return {
            label: 'Gesloten sinds',
            note: `Inschrijfdeadline verstreken · gesloten sinds ${deadline}`,
            value: deadline,
        };
    }

    return {
        label: 'Reden',
        note: 'Gesloten door de organisatie',
        value: 'Door de organisatie gesloten',
    };
}

export function formatEventLocation(name: string, city: string): string {
    const normalizedName = normalizeLocationPart(name);
    const normalizedCity = normalizeLocationPart(city);

    if (` ${normalizedName} `.includes(` ${normalizedCity} `)) {
        return name;
    }

    return `${name}, ${city}`;
}

function normalizeLocationPart(value: string): string {
    return value
        .toLocaleLowerCase('nl-NL')
        .replace(/[^\p{L}\p{N}]+/gu, ' ')
        .trim();
}
