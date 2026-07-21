import { Form, Link } from '@inertiajs/react';
import {
    ArrowUpRight,
    Ban,
    CalendarClock,
    CircleAlert,
    ClipboardCheck,
    Coins,
    ExternalLink,
    EyeOff,
    FileText,
    Globe,
    Save,
    Send,
    TriangleAlert,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import {
    destroy,
    index,
} from '@/actions/App/Http/Controllers/Admin/EventController';
import {
    cancel,
    publish,
    unpublish,
} from '@/actions/App/Http/Controllers/Admin/EventStatusController';
import { index as seasonsIndex } from '@/actions/App/Http/Controllers/Admin/SeasonController';
import { AdminActivityMetadata } from '@/components/admin/admin-activity-metadata';
import { AdminConfirmationDialog } from '@/components/admin/admin-confirmation-dialog';
import {
    AdminFormActions,
    AdminFormErrorSummary,
    AdminFormLayout,
    AdminFormNavigationGuard,
    AdminFormOutline,
    AdminFormSection,
} from '@/components/admin/admin-form';
import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import { MediaAssetPicker } from '@/components/admin/media-asset-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DateTimePicker } from '@/components/ui/date-time-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { createSlug } from '@/lib/create-slug';
import { show as publicEventShow } from '@/routes/events';
import type {
    AdminRegistrationStatus,
    EditableEvent,
    EventFormOptions,
    SelectOption,
} from './types';

type MutationForm = {
    action: string;
    method: 'post';
};

const eventFormOutlineItems = [
    {
        description: 'Titel, type en locatie',
        icon: FileText,
        id: 'event-basics',
        title: 'Basisinformatie',
    },
    {
        description: 'Seizoen, datum en tijd',
        icon: CalendarClock,
        id: 'event-schedule',
        title: 'Wanneer',
    },
    {
        description: 'Prijs en deelnemerslimiet',
        icon: Coins,
        id: 'event-capacity',
        title: 'Capaciteit en prijs',
    },
    {
        description: 'Status, link en deadlines',
        icon: ClipboardCheck,
        id: 'event-registration',
        title: 'Inschrijving',
    },
    {
        description: 'Publieke URL en inhoud',
        icon: Globe,
        id: 'event-public-page',
        title: 'Publieke pagina',
    },
];

export function EventForm({
    canManageSeasons,
    event,
    form,
    options,
}: {
    canManageSeasons: boolean;
    event?: EditableEvent;
    form: MutationForm;
    options: EventFormOptions;
}) {
    const [title, setTitle] = useState(event?.title ?? '');
    const [slug, setSlug] = useState(event?.slug ?? '');
    const [slugManuallyEdited, setSlugManuallyEdited] = useState(
        Boolean(event?.slug),
    );
    const [registrationStatus, setRegistrationStatus] = useState(
        event?.registrationStatus ?? 'closed',
    );
    const [coverImage, setCoverImage] = useState(event?.coverImage ?? null);

    const defaultEventType =
        event?.type ??
        (options.types.some((option) => option.value === 'training')
            ? 'training'
            : (options.types[0]?.value ?? 'other'));
    const defaultLocationId = event
        ? String(event.locationId ?? '')
        : options.locations.length === 1
          ? String(options.locations[0].id)
          : '';
    const registrationRequiresUrl = ['open', 'waitlist'].includes(
        registrationStatus,
    );

    return (
        <Form
            {...form}
            className="grid gap-0"
            options={{ preserveScroll: true }}
        >
            {({ errors, isDirty, processing, recentlySuccessful }) => (
                <>
                    <AdminFormNavigationGuard isDirty={isDirty} />
                    <AdminFormActions
                        context={
                            title.trim() ||
                            (event ? 'Event bewerken' : 'Nieuw event')
                        }
                        isDirty={isDirty}
                        isNew={!event}
                        processing={processing}
                        recentlySuccessful={recentlySuccessful}
                    >
                        <Button asChild type="button" variant="outline">
                            <Link href={index()}>Annuleren</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save />
                            {processing ? (
                                'Opslaan…'
                            ) : event ? (
                                <>
                                    <span className="sm:hidden">Opslaan</span>
                                    <span className="hidden sm:inline">
                                        Wijzigingen opslaan
                                    </span>
                                </>
                            ) : (
                                'Concept opslaan'
                            )}
                        </Button>
                    </AdminFormActions>

                    <AdminFormLayout
                        asideFirstOnSmallScreens={false}
                        asideLayoutClassName="@min-[56rem]/admin-page:grid-cols-[minmax(0,1fr)_18.5rem] @min-[84rem]/admin-page:grid-cols-[minmax(0,1fr)_21.5rem]"
                        className="mx-auto w-full"
                        contentClassName="@container/event-main"
                        aside={
                            <EventFormAside event={event} isDirty={isDirty} />
                        }
                    >
                        <AdminFormErrorSummary errors={errors} />

                        {/* 1. Identiteit: wat is het event, en waar vindt het plaats. */}
                        <AdminFormSection
                            id="event-basics"
                            className="@container/fields"
                            icon={FileText}
                            title="Basisinformatie"
                            description={
                                event
                                    ? 'De titel, het type en de locatie vormen de herkenbare basis van het event.'
                                    : 'Geef het event een titel en kies het type en de locatie. De URL wordt automatisch uit de titel gemaakt.'
                            }
                        >
                            <div className="grid gap-5 @min-[40rem]/fields:grid-cols-2">
                                <FormField
                                    id="title"
                                    label="Titel"
                                    error={errors.title}
                                    className="@min-[40rem]/fields:col-span-2"
                                >
                                    <Input
                                        id="title"
                                        name="title"
                                        value={title}
                                        onChange={(inputEvent) => {
                                            const nextTitle =
                                                inputEvent.target.value;

                                            setTitle(nextTitle);

                                            if (!slugManuallyEdited) {
                                                setSlug(createSlug(nextTitle));
                                            }
                                        }}
                                        required
                                        maxLength={255}
                                        autoFocus={!event}
                                        autoComplete="off"
                                        placeholder={
                                            event
                                                ? undefined
                                                : 'Bijv. Indoor training Rotterdam'
                                        }
                                        aria-invalid={Boolean(errors.title)}
                                        aria-describedby={fieldDescription(
                                            'title',
                                            errors.title,
                                        )}
                                    />
                                </FormField>
                                <FormField
                                    id="type"
                                    label="Eventtype"
                                    error={errors.type}
                                >
                                    <FormSelect
                                        id="type"
                                        name="type"
                                        defaultValue={defaultEventType}
                                        options={options.types}
                                        required
                                        invalid={Boolean(errors.type)}
                                        describedBy={fieldDescription(
                                            'type',
                                            errors.type,
                                        )}
                                    />
                                </FormField>
                                <FormField
                                    id="location_id"
                                    label="Locatie"
                                    error={errors.location_id}
                                >
                                    <FormSelect
                                        id="location_id"
                                        name="location_id"
                                        defaultValue={defaultLocationId}
                                        options={options.locations.map(
                                            (option) => ({
                                                value: String(option.id),
                                                label: option.label,
                                            }),
                                        )}
                                        placeholder="Kies een locatie"
                                        required
                                        invalid={Boolean(errors.location_id)}
                                        describedBy={fieldDescription(
                                            'location_id',
                                            errors.location_id,
                                        )}
                                    />
                                </FormField>
                            </div>
                        </AdminFormSection>

                        {/* 2. Planning: wanneer het event plaatsvindt en of het bij een seizoen hoort. */}
                        <AdminFormSection
                            id="event-schedule"
                            className="@container/fields"
                            icon={CalendarClock}
                            title="Wanneer"
                            description="Koppel het event optioneel aan een seizoen en leg start- en eindtijd vast."
                        >
                            <div
                                data-testid="event-schedule-fields"
                                className="grid grid-cols-1 items-start gap-5 @min-[44rem]/fields:grid-cols-2"
                            >
                                <FormField
                                    id="season_id"
                                    label="Seizoen (optioneel)"
                                    error={errors.season_id}
                                    className="max-w-[23rem] @min-[44rem]/fields:col-span-2"
                                >
                                    <FormSelect
                                        id="season_id"
                                        name="season_id"
                                        defaultValue={String(
                                            event?.seasonId ?? '',
                                        )}
                                        options={options.seasons.map(
                                            (option) => ({
                                                value: String(option.id),
                                                label: option.label,
                                            }),
                                        )}
                                        placeholder="Geen seizoen"
                                        invalid={Boolean(errors.season_id)}
                                        describedBy={fieldDescription(
                                            'season_id',
                                            errors.season_id,
                                        )}
                                    />
                                    {canManageSeasons && (
                                        <Link
                                            href={seasonsIndex()}
                                            className="w-fit text-xs font-medium text-signal-700 hover:underline dark:text-signal-300"
                                        >
                                            Seizoenen beheren
                                        </Link>
                                    )}
                                </FormField>
                                <FormField
                                    id="starts_at"
                                    label="Start"
                                    error={errors.starts_at}
                                    className="max-w-[28rem] @min-[44rem]/fields:max-w-none"
                                >
                                    <DateTimePicker
                                        id="starts_at"
                                        name="starts_at"
                                        label="Start"
                                        defaultValue={event?.startsAt ?? ''}
                                        aria-invalid={Boolean(errors.starts_at)}
                                        aria-describedby={fieldDescription(
                                            'starts_at',
                                            errors.starts_at,
                                        )}
                                    />
                                </FormField>
                                <FormField
                                    id="ends_at"
                                    label="Einde (optioneel)"
                                    error={errors.ends_at}
                                    className="max-w-[28rem] @min-[44rem]/fields:max-w-none"
                                >
                                    <DateTimePicker
                                        id="ends_at"
                                        name="ends_at"
                                        label="Einde"
                                        defaultValue={event?.endsAt ?? ''}
                                        aria-invalid={Boolean(errors.ends_at)}
                                        aria-describedby={fieldDescription(
                                            'ends_at',
                                            errors.ends_at,
                                        )}
                                    />
                                </FormField>
                            </div>
                        </AdminFormSection>

                        {/* 3. Praktische grenzen: los van de planning, dus een eigen, kleinere sectie. */}
                        <AdminFormSection
                            id="event-capacity"
                            className="@container/fields"
                            icon={Coins}
                            title="Capaciteit en prijs"
                            description="Bepaal of er een limiet aan deelnemers zit en wat meedoen kost."
                        >
                            <div
                                data-testid="event-capacity-fields"
                                className="grid grid-cols-1 gap-5 @min-[36rem]/fields:grid-cols-2"
                            >
                                <FormField
                                    id="price_euros"
                                    label="Deelnameprijs (optioneel)"
                                    hint="Vul 0 in voor gratis; laat leeg als de prijs later volgt."
                                    error={errors.price_euros}
                                    reserveSupportingTextSpace
                                >
                                    <div className="relative">
                                        <span
                                            aria-hidden="true"
                                            className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-neutral-500"
                                        >
                                            €
                                        </span>
                                        <Input
                                            id="price_euros"
                                            name="price_euros"
                                            type="number"
                                            inputMode="decimal"
                                            min="0"
                                            max="42949672.95"
                                            step="0.01"
                                            defaultValue={
                                                event?.priceEuros ?? ''
                                            }
                                            className="pl-8"
                                            aria-invalid={Boolean(
                                                errors.price_euros,
                                            )}
                                            aria-describedby={fieldDescription(
                                                'price_euros',
                                                errors.price_euros,
                                                true,
                                            )}
                                        />
                                    </div>
                                </FormField>
                                <FormField
                                    id="capacity"
                                    label="Deelnemerslimiet (optioneel)"
                                    error={errors.capacity}
                                    reserveSupportingTextSpace
                                >
                                    <Input
                                        id="capacity"
                                        name="capacity"
                                        type="number"
                                        inputMode="numeric"
                                        min="1"
                                        max="65535"
                                        placeholder="Geen limiet"
                                        defaultValue={event?.capacity ?? ''}
                                        aria-invalid={Boolean(errors.capacity)}
                                        aria-describedby={fieldDescription(
                                            'capacity',
                                            errors.capacity,
                                        )}
                                    />
                                </FormField>
                            </div>
                        </AdminFormSection>

                        {/* 4. Inschrijving: status stuurt de rest van de sectie aan, dus die staat eerst
                        en de link krijgt een duidelijk zichtbare "verplicht"-behandeling. */}
                        <AdminFormSection
                            id="event-registration"
                            className="@container/fields"
                            icon={ClipboardCheck}
                            title="Inschrijving"
                            description="Bepaal welke inschrijfstatus bezoekers zien en waar de inschrijving naartoe leidt."
                        >
                            <div className="grid gap-5 @min-[40rem]/fields:grid-cols-[16rem_minmax(18rem,1fr)]">
                                <FormField
                                    id="registration_status"
                                    label="Inschrijfstatus"
                                    error={errors.registration_status}
                                >
                                    <FormSelect
                                        id="registration_status"
                                        name="registration_status"
                                        defaultValue={
                                            event?.registrationStatus ??
                                            'closed'
                                        }
                                        value={registrationStatus}
                                        onValueChange={(value) =>
                                            setRegistrationStatus(
                                                value as AdminRegistrationStatus,
                                            )
                                        }
                                        options={options.registrationStatuses}
                                        required
                                        invalid={Boolean(
                                            errors.registration_status,
                                        )}
                                        describedBy={fieldDescription(
                                            'registration_status',
                                            errors.registration_status,
                                        )}
                                    />
                                </FormField>
                                <FormField
                                    id="registration_url"
                                    label="Inschrijflink"
                                    labelSuffix={
                                        registrationRequiresUrl ? (
                                            <span className="rounded-full bg-flight-100 px-2 py-0.5 text-[0.65rem] font-semibold tracking-wide text-flight-700 uppercase dark:bg-flight-500/15 dark:text-flight-300">
                                                Verplicht
                                            </span>
                                        ) : (
                                            <span className="text-xs font-normal text-neutral-500 dark:text-neutral-400">
                                                optioneel
                                            </span>
                                        )
                                    }
                                    hint={
                                        registrationRequiresUrl
                                            ? 'Verplicht bij een open inschrijving of wachtlijst.'
                                            : undefined
                                    }
                                    error={errors.registration_url}
                                    className={
                                        registrationRequiresUrl
                                            ? 'border-l-2 border-flight-300 pl-4 dark:border-flight-500/40'
                                            : undefined
                                    }
                                >
                                    <Input
                                        id="registration_url"
                                        name="registration_url"
                                        type="url"
                                        defaultValue={
                                            event?.registrationUrl ?? ''
                                        }
                                        maxLength={2048}
                                        placeholder="https://…"
                                        inputMode="url"
                                        autoComplete="url"
                                        required={registrationRequiresUrl}
                                        aria-invalid={Boolean(
                                            errors.registration_url,
                                        )}
                                        aria-describedby={fieldDescription(
                                            'registration_url',
                                            errors.registration_url,
                                            registrationRequiresUrl,
                                        )}
                                    />
                                </FormField>
                            </div>
                            <div
                                data-testid="event-registration-dates"
                                className="grid gap-5 @min-[44rem]/fields:grid-cols-2"
                            >
                                <FormField
                                    id="registration_opens_at"
                                    label="Inschrijving opent (optioneel)"
                                    error={errors.registration_opens_at}
                                    className="max-w-[28rem] @min-[44rem]/fields:max-w-none"
                                >
                                    <DateTimePicker
                                        id="registration_opens_at"
                                        name="registration_opens_at"
                                        label="Inschrijving opent"
                                        defaultValue={
                                            event?.registrationOpensAt ?? ''
                                        }
                                        aria-invalid={Boolean(
                                            errors.registration_opens_at,
                                        )}
                                        aria-describedby={fieldDescription(
                                            'registration_opens_at',
                                            errors.registration_opens_at,
                                        )}
                                    />
                                </FormField>
                                <FormField
                                    id="registration_deadline_at"
                                    label="Inschrijfdeadline (optioneel)"
                                    error={errors.registration_deadline_at}
                                    className="max-w-[28rem] @min-[44rem]/fields:max-w-none"
                                >
                                    <DateTimePicker
                                        id="registration_deadline_at"
                                        name="registration_deadline_at"
                                        label="Inschrijfdeadline"
                                        defaultValue={
                                            event?.registrationDeadlineAt ?? ''
                                        }
                                        aria-invalid={Boolean(
                                            errors.registration_deadline_at,
                                        )}
                                        aria-describedby={fieldDescription(
                                            'registration_deadline_at',
                                            errors.registration_deadline_at,
                                        )}
                                    />
                                </FormField>
                            </div>
                        </AdminFormSection>

                        {/* 5. Publieke copy: dit vullen mensen als laatste in, nadat de kern vaststaat. */}
                        <AdminFormSection
                            id="event-public-page"
                            className="@container/fields"
                            icon={Globe}
                            title="Publieke pagina"
                            description="Beheer de URL en de uitgebreide informatie die bezoekers op de eventpagina zien."
                        >
                            <div className="grid gap-5">
                                <FormField
                                    id="slug"
                                    label="URL-slug (optioneel)"
                                    error={errors.slug}
                                >
                                    <Input
                                        id="slug"
                                        name="slug"
                                        value={slug}
                                        onChange={(inputEvent) => {
                                            const nextSlug =
                                                inputEvent.target.value;

                                            setSlug(nextSlug);
                                            setSlugManuallyEdited(
                                                nextSlug !== '' &&
                                                    nextSlug !==
                                                        createSlug(title),
                                            );
                                        }}
                                        maxLength={255}
                                        placeholder="Automatisch uit titel"
                                        autoComplete="off"
                                        autoCapitalize="none"
                                        spellCheck={false}
                                        aria-invalid={Boolean(errors.slug)}
                                        aria-describedby={fieldDescription(
                                            'slug',
                                            errors.slug,
                                        )}
                                    />
                                    {slug && (
                                        <p className="truncate text-xs text-neutral-500 dark:text-neutral-400">
                                            Publieke URL: /events/{slug}
                                        </p>
                                    )}
                                </FormField>
                                <FormField
                                    id="cover_image_id"
                                    label="Omslagafbeelding (optioneel)"
                                    error={errors.cover_image_id}
                                >
                                    <MediaAssetPicker
                                        id="cover_image_id"
                                        name="cover_image_id"
                                        selected={coverImage}
                                        onChange={setCoverImage}
                                        invalid={Boolean(errors.cover_image_id)}
                                        describedBy={fieldDescription(
                                            'cover_image_id',
                                            errors.cover_image_id,
                                        )}
                                    />
                                </FormField>
                                <FormField
                                    id="content"
                                    label="Omschrijving (optioneel)"
                                    error={errors.content}
                                >
                                    <textarea
                                        id="content"
                                        name="content"
                                        defaultValue={event?.content ?? ''}
                                        rows={8}
                                        maxLength={50000}
                                        placeholder="Praktische informatie, programma en benodigdheden…"
                                        aria-invalid={Boolean(errors.content)}
                                        aria-describedby={fieldDescription(
                                            'content',
                                            errors.content,
                                        )}
                                        className="min-h-36 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:bg-input/30"
                                    />
                                </FormField>
                            </div>
                        </AdminFormSection>

                        {event?.capabilities.delete && (
                            <AdminFormSection
                                id="event-danger-zone"
                                icon={TriangleAlert}
                                tone="danger"
                                title="Gevarenzone"
                                description="Verwijder dit event alleen wanneer het niet langer nodig is. Deze actie kan niet ongedaan worden gemaakt."
                            >
                                <div>
                                    <AdminConfirmationDialog
                                        form={destroy.form(event.id)}
                                        intent="delete"
                                        subject={event.title}
                                        trigger={
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                            >
                                                <Trash2 />
                                                Event verwijderen
                                            </Button>
                                        }
                                    />
                                </div>
                            </AdminFormSection>
                        )}
                    </AdminFormLayout>
                </>
            )}
        </Form>
    );
}

function fieldDescription(
    id: string,
    error?: string,
    hasHint = false,
): string | undefined {
    if (error) {
        return `${id}-error`;
    }

    return hasHint ? `${id}-hint` : undefined;
}

function EventStatusPanel({
    event,
    isDirty,
}: {
    event?: EditableEvent;
    isDirty: boolean;
}) {
    return (
        <section className="p-5 @min-[84rem]/admin-page:p-6">
            <div>
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.14em] text-neutral-500 uppercase">
                            Publicatie
                        </p>
                        <p className="mt-1 text-sm font-semibold text-neutral-950 dark:text-white">
                            Zichtbaarheid en status
                        </p>
                    </div>
                    <AdminStatusBadge status={event?.status ?? 'draft'} />
                </div>
                <p className="mt-3 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                    {event
                        ? 'Statusacties worden direct uitgevoerd.'
                        : 'Een nieuw event wordt altijd eerst als concept opgeslagen.'}
                </p>

                {isDirty && event && (
                    <div
                        aria-live="polite"
                        className="mt-4 flex gap-2 border-l-2 border-flight-400 bg-warmup/70 px-3 py-2.5 text-xs leading-5 text-night-700 dark:bg-flight-500/10 dark:text-flight-300"
                    >
                        <CircleAlert className="mt-0.5 size-3.5 shrink-0" />
                        Sla je wijzigingen op voordat je de publicatiestatus
                        aanpast.
                    </div>
                )}

                {event && (
                    <div className="mt-5 grid gap-4">
                        {event.status !== 'draft' && (
                            <Button
                                asChild
                                variant="outline"
                                className="border-signal-200 text-signal-800 hover:text-signal-900 dark:text-signal-200 h-11 w-full justify-start rounded-xl bg-signal-50/70 px-3 shadow-none hover:border-signal-300 hover:bg-signal-100 focus-visible:ring-signal-500/30 dark:border-signal-500/25 dark:bg-signal-500/10 dark:hover:border-signal-500/40 dark:hover:bg-signal-500/15 dark:hover:text-signal-100"
                            >
                                <Link
                                    data-sidebar-action="public"
                                    href={publicEventShow(event.slug)}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <ExternalLink />
                                    Publieke pagina
                                    <ArrowUpRight className="ml-auto size-3.5 opacity-60" />
                                    <span className="sr-only">
                                        {' '}
                                        (opent in een nieuw tabblad)
                                    </span>
                                </Link>
                            </Button>
                        )}

                        {event.capabilities.publish &&
                            event.status !== 'published' && (
                                <AdminConfirmationDialog
                                    form={publish.form(event.id)}
                                    intent="publish"
                                    subject={event.title}
                                    trigger={
                                        <Button
                                            type="button"
                                            data-sidebar-action="publish"
                                            className="h-10 w-full justify-start rounded-lg bg-signal-600 px-3 text-white shadow-xs hover:bg-signal-700 focus-visible:ring-signal-500/40 dark:bg-signal-500 dark:hover:bg-signal-400"
                                            disabled={isDirty}
                                        >
                                            <Send />
                                            Event publiceren
                                        </Button>
                                    }
                                />
                            )}

                        {event.status === 'published' &&
                            (event.capabilities.publish ||
                                event.capabilities.cancel) && (
                                <div className="grid gap-2">
                                    <p className="text-[0.68rem] font-semibold tracking-[0.12em] text-neutral-500 uppercase dark:text-neutral-400">
                                        Status beheren
                                    </p>
                                    <div className="grid grid-cols-[repeat(auto-fit,minmax(7.5rem,1fr))] gap-2">
                                        {event.capabilities.publish && (
                                            <AdminConfirmationDialog
                                                form={unpublish.form(event.id)}
                                                intent="unpublish"
                                                subject={event.title}
                                                trigger={
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        data-sidebar-action="unpublish"
                                                        className="h-10 w-full rounded-lg border-neutral-200 bg-white px-2.5 text-neutral-700 shadow-none hover:border-amber-200 hover:bg-amber-50 hover:text-amber-900 focus-visible:ring-amber-500/30 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-300 dark:hover:border-amber-500/30 dark:hover:bg-amber-500/10 dark:hover:text-amber-200"
                                                        disabled={isDirty}
                                                    >
                                                        <EyeOff />
                                                        Intrekken
                                                    </Button>
                                                }
                                            />
                                        )}
                                        {event.capabilities.cancel && (
                                            <AdminConfirmationDialog
                                                form={cancel.form(event.id)}
                                                intent="cancel"
                                                subject={event.title}
                                                trigger={
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        data-sidebar-action="cancel"
                                                        className="h-10 w-full rounded-lg border-destructive/20 bg-white px-2.5 text-destructive shadow-none hover:border-destructive/30 hover:bg-destructive/[0.06] hover:text-destructive focus-visible:ring-destructive/25 dark:bg-neutral-950 dark:hover:bg-destructive/[0.1]"
                                                        disabled={isDirty}
                                                    >
                                                        <Ban />
                                                        Annuleren
                                                    </Button>
                                                }
                                            />
                                        )}
                                    </div>
                                </div>
                            )}
                    </div>
                )}
            </div>
        </section>
    );
}

function EventFormAside({
    event,
    isDirty,
}: {
    event?: EditableEvent;
    isDirty: boolean;
}) {
    return (
        <div className="overflow-clip rounded-2xl border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-950">
            <EventStatusPanel event={event} isDirty={isDirty} />
            {event && <AdminActivityMetadata activity={event.activity} />}
            <AdminFormOutline
                description="Spring direct naar een onderdeel van het formulier."
                items={eventFormOutlineItems}
            />
        </div>
    );
}

function FormField({
    children,
    className,
    error,
    hint,
    id,
    label,
    labelSuffix,
    reserveSupportingTextSpace = false,
}: {
    children: React.ReactNode;
    className?: string;
    error?: string;
    hint?: string;
    id: string;
    label: string;
    labelSuffix?: ReactNode;
    reserveSupportingTextSpace?: boolean;
}) {
    return (
        <div
            data-field={id}
            className={className ? `grid gap-2 ${className}` : 'grid gap-2'}
        >
            <div className="flex items-center justify-between gap-2">
                <Label htmlFor={id}>{label}</Label>
                {labelSuffix}
            </div>
            {children}
            {(hint || error || reserveSupportingTextSpace) && (
                <div
                    className={
                        reserveSupportingTextSpace ? 'min-h-10' : 'min-h-5'
                    }
                >
                    {hint && !error && (
                        <p
                            id={`${id}-hint`}
                            className="text-xs leading-5 text-neutral-500"
                        >
                            {hint}
                        </p>
                    )}
                    <InputError id={`${id}-error`} message={error} />
                </div>
            )}
        </div>
    );
}

const emptySelectValue = '__empty__';

function FormSelect({
    defaultValue,
    describedBy,
    id,
    invalid,
    name,
    onValueChange,
    options,
    placeholder,
    required,
    value,
}: {
    defaultValue: string;
    describedBy?: string;
    id: string;
    invalid: boolean;
    name: string;
    onValueChange?: (value: string) => void;
    options: SelectOption[];
    placeholder?: string;
    required?: boolean;
    value?: string;
}) {
    const [uncontrolledValue, setUncontrolledValue] = useState(defaultValue);
    const currentValue = value ?? uncontrolledValue;
    const selectedValue =
        currentValue === ''
            ? required
                ? undefined
                : emptySelectValue
            : currentValue;

    const handleValueChange = (nextValue: string) => {
        const normalizedValue = nextValue === emptySelectValue ? '' : nextValue;

        if (value === undefined) {
            setUncontrolledValue(normalizedValue);
        }

        onValueChange?.(normalizedValue);
    };

    return (
        <>
            <input type="hidden" name={name} value={currentValue} />
            <Select
                value={selectedValue}
                onValueChange={handleValueChange}
                required={required}
            >
                <SelectTrigger
                    id={id}
                    aria-invalid={invalid}
                    aria-describedby={describedBy}
                    className="w-full"
                >
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent align="start">
                    {placeholder && !required && (
                        <SelectItem value={emptySelectValue}>
                            {placeholder}
                        </SelectItem>
                    )}
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </>
    );
}
