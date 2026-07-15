import type { EventRegistrationStatus, EventStatus, EventType } from '@/types';

const dateFormatter = new Intl.DateTimeFormat('nl-NL', {
    day: 'numeric',
    month: 'long',
    weekday: 'long',
    year: 'numeric',
});

const shortDateFormatter = new Intl.DateTimeFormat('nl-NL', {
    day: '2-digit',
    month: 'short',
});

const timeFormatter = new Intl.DateTimeFormat('nl-NL', {
    hour: '2-digit',
    minute: '2-digit',
});

const priceFormatter = new Intl.NumberFormat('nl-NL', {
    currency: 'EUR',
    style: 'currency',
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
        closed: 'Aanmelding gesloten',
        full: 'Vol',
        open: 'Aanmelden mogelijk',
        waitlist: 'Wachtlijst',
    };

export const eventStatusLabels: Record<EventStatus, string> = {
    cancelled: 'Geannuleerd',
    published: 'Gepland',
};

export function formatEventDate(value: string): string {
    return dateFormatter.format(new Date(value));
}

export function formatEventShortDate(value: string): {
    day: string;
    month: string;
} {
    const parts = shortDateFormatter.formatToParts(new Date(value));

    return {
        day: parts.find((part) => part.type === 'day')?.value ?? '',
        month: (
            parts.find((part) => part.type === 'month')?.value ?? ''
        ).replace('.', ''),
    };
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
