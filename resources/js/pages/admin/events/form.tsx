import { Form, Link, router } from '@inertiajs/react';
import {
    ArrowUpRight,
    Ban,
    CalendarClock,
    CircleAlert,
    ClipboardCheck,
    Coins,
    Copy,
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
    duplicate,
    index,
} from '@/actions/App/Http/Controllers/Admin/EventController';
import {
    cancel,
    publish,
    unpublish,
} from '@/actions/App/Http/Controllers/Admin/EventStatusController';
import { preview } from '@/actions/App/Http/Controllers/Public/EventController';
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
import { MarkdownEditor } from '@/components/admin/markdown-editor';
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
import { show as publicEventShow } from '@/routes/events';
import type { EditableEvent, EventFormOptions, SelectOption } from './types';

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
        description: 'Automatisch openen en sluiten',
        icon: ClipboardCheck,
        id: 'event-registration',
        title: 'Inschrijving',
    },
    {
        description: 'Omslag en inhoud',
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
    const [registrationEnabled, setRegistrationEnabled] = useState(
        event?.registrationEnabled ?? false,
    );
    const [registrationClosedManually, setRegistrationClosedManually] =
        useState(event?.registrationClosedManually ?? false);
    const [registrationFull, setRegistrationFull] = useState(
        event?.registrationFull ?? false,
    );
    const [registrationWaitlistEnabled, setRegistrationWaitlistEnabled] =
        useState(event?.registrationWaitlistEnabled ?? false);
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
    const registrationRequiresUrl = registrationEnabled;

    return (
        <Form
            {...form}
            className="grid gap-0"
            options={{ preserveScroll: true }}
            setDefaultsOnSuccess
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
                                    : 'Geef het event een titel en kies het type en de locatie. De URL wordt automatisch uit de titel en startdatum gemaakt.'
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
                            description="Plan wanneer inschrijven mogelijk is. Het platform opent en sluit de inschrijving automatisch."
                        >
                            <div className="overflow-hidden border-y border-neutral-200 dark:border-neutral-800">
                                <RegistrationSwitch
                                    id="registration_enabled"
                                    checked={registrationEnabled}
                                    description="Toon de inschrijving en gebruik de planning hieronder."
                                    error={errors.registration_enabled}
                                    onCheckedChange={setRegistrationEnabled}
                                    title="Inschrijving aanbieden"
                                />
                                <div
                                    id="event-registration-fields"
                                    hidden={!registrationEnabled}
                                    className="grid gap-6 border-t border-neutral-200 bg-white px-3 py-5 sm:px-4 sm:py-6 dark:border-neutral-800 dark:bg-neutral-950"
                                >
                                    <FormField
                                        id="registration_url"
                                        label="Inschrijflink"
                                        labelSuffix={
                                            <span className="rounded-full bg-flight-100 px-2 py-0.5 text-[0.65rem] font-semibold tracking-wide text-flight-700 uppercase dark:bg-flight-500/15 dark:text-flight-300">
                                                Verplicht
                                            </span>
                                        }
                                        hint="Deze link wordt pas publiek zodra de inschrijving automatisch opent."
                                        error={errors.registration_url}
                                        className="border-l-2 border-flight-300 pl-4 dark:border-flight-500/40"
                                    >
                                        <Input
                                            id="registration_url"
                                            name="registration_url"
                                            type="url"
                                            defaultValue={
                                                event?.registrationUrl ?? ''
                                            }
                                            maxLength={2048}
                                            placeholder="https://… of mailto:…"
                                            inputMode="url"
                                            autoComplete="url"
                                            required={registrationRequiresUrl}
                                            aria-invalid={Boolean(
                                                errors.registration_url,
                                            )}
                                            aria-describedby={fieldDescription(
                                                'registration_url',
                                                errors.registration_url,
                                                true,
                                            )}
                                        />
                                    </FormField>
                                    <div
                                        data-testid="event-registration-dates"
                                        className="grid gap-5 @min-[44rem]/fields:grid-cols-2"
                                    >
                                        <FormField
                                            id="registration_opens_at"
                                            label="Inschrijving opent (optioneel)"
                                            hint="Laat leeg als inschrijven direct mogelijk is."
                                            error={errors.registration_opens_at}
                                            className="max-w-[28rem] @min-[44rem]/fields:max-w-none"
                                        >
                                            <DateTimePicker
                                                id="registration_opens_at"
                                                name="registration_opens_at"
                                                label="Inschrijving opent"
                                                defaultValue={
                                                    event?.registrationOpensAt ??
                                                    ''
                                                }
                                                showTodayShortcut
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
                                            hint="Op dit tijdstip sluit de inschrijving automatisch."
                                            error={
                                                errors.registration_deadline_at
                                            }
                                            className="max-w-[28rem] @min-[44rem]/fields:max-w-none"
                                        >
                                            <DateTimePicker
                                                id="registration_deadline_at"
                                                name="registration_deadline_at"
                                                label="Inschrijfdeadline"
                                                defaultValue={
                                                    event?.registrationDeadlineAt ??
                                                    ''
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
                                    <div className="grid gap-3 @min-[44rem]/fields:grid-cols-2">
                                        <RegistrationSwitch
                                            id="registration_closed_manually"
                                            checked={registrationClosedManually}
                                            description="Noodrem die de automatische planning tijdelijk overstemt."
                                            error={
                                                errors.registration_closed_manually
                                            }
                                            onCheckedChange={
                                                setRegistrationClosedManually
                                            }
                                            title="Tijdelijk gesloten"
                                            variant="warning"
                                        />
                                        <RegistrationSwitch
                                            id="registration_full"
                                            checked={registrationFull}
                                            description="Markeer de reguliere inschrijving handmatig als vol."
                                            error={errors.registration_full}
                                            onCheckedChange={(checked) => {
                                                setRegistrationFull(checked);

                                                if (!checked) {
                                                    setRegistrationWaitlistEnabled(
                                                        false,
                                                    );
                                                }
                                            }}
                                            title="Event is vol"
                                        />
                                    </div>
                                    {registrationFull ? (
                                        <RegistrationSwitch
                                            id="registration_waitlist_enabled"
                                            checked={
                                                registrationWaitlistEnabled
                                            }
                                            description="Gebruik de inschrijflink voor aanmeldingen op de wachtlijst."
                                            error={
                                                errors.registration_waitlist_enabled
                                            }
                                            onCheckedChange={
                                                setRegistrationWaitlistEnabled
                                            }
                                            title="Wachtlijst openen"
                                        />
                                    ) : (
                                        <input
                                            type="hidden"
                                            name="registration_waitlist_enabled"
                                            value="0"
                                        />
                                    )}
                                </div>
                            </div>
                        </AdminFormSection>

                        {/* 5. Publieke copy: dit vullen mensen als laatste in, nadat de kern vaststaat. */}
                        <AdminFormSection
                            id="event-public-page"
                            className="@container/fields"
                            icon={Globe}
                            title="Publieke pagina"
                            description="Voeg een omslag en uitgebreide informatie toe. De publieke URL wordt automatisch uit de titel en startdatum gemaakt."
                        >
                            <div className="grid gap-5">
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
                                    hint="Markdown wordt ondersteund, zoals koppen, lijsten, links, vet en cursief."
                                >
                                    <MarkdownEditor
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
                                            true,
                                        )}
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
                        {event.status === 'draft' &&
                            (isDirty ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled
                                    className="border-signal-200 text-signal-800 dark:text-signal-200 h-11 w-full justify-start rounded-xl bg-signal-50/70 px-3 shadow-none dark:border-signal-500/25 dark:bg-signal-500/10"
                                >
                                    <ExternalLink />
                                    Voorbeeld bekijken
                                    <ArrowUpRight className="ml-auto size-3.5 opacity-60" />
                                </Button>
                            ) : (
                                <Button
                                    asChild
                                    variant="outline"
                                    className="border-signal-200 text-signal-800 hover:text-signal-900 dark:text-signal-200 h-11 w-full justify-start rounded-xl bg-signal-50/70 px-3 shadow-none hover:border-signal-300 hover:bg-signal-100 focus-visible:ring-signal-500/30 dark:border-signal-500/25 dark:bg-signal-500/10 dark:hover:border-signal-500/40 dark:hover:bg-signal-500/15 dark:hover:text-signal-100"
                                >
                                    <Link
                                        data-sidebar-action="preview"
                                        href={preview(event.id)}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <ExternalLink />
                                        Voorbeeld bekijken
                                        <ArrowUpRight className="ml-auto size-3.5 opacity-60" />
                                        <span className="sr-only">
                                            {' '}
                                            (opent in een nieuw tabblad)
                                        </span>
                                    </Link>
                                </Button>
                            ))}

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

                        {event.capabilities.duplicate && (
                            <Button
                                type="button"
                                variant="outline"
                                className="hover:border-signal-200 hover:text-signal-900 dark:hover:text-signal-200 h-10 w-full justify-start rounded-lg border-neutral-200 bg-white px-3 text-neutral-700 shadow-none hover:bg-signal-50 focus-visible:ring-signal-500/30 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-300 dark:hover:border-signal-500/30 dark:hover:bg-signal-500/10"
                                disabled={isDirty}
                                onClick={() => router.post(duplicate(event.id))}
                            >
                                <Copy />
                                Event dupliceren
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

function RegistrationSwitch({
    checked,
    description,
    error,
    id,
    onCheckedChange,
    title,
    variant = 'default',
}: {
    checked: boolean;
    description: string;
    error?: string;
    id: string;
    onCheckedChange: (checked: boolean) => void;
    title: string;
    variant?: 'default' | 'warning';
}) {
    const activeClasses =
        variant === 'warning'
            ? 'border-amber-500 bg-amber-50/60 dark:border-amber-400 dark:bg-amber-500/[0.08]'
            : 'border-flight-500 bg-flight-50/55 dark:border-flight-400 dark:bg-flight-500/[0.07]';

    return (
        <div data-field={id}>
            <input type="hidden" name={id} value={checked ? '1' : '0'} />
            <button
                id={id}
                type="button"
                role="switch"
                aria-checked={checked}
                aria-controls={
                    id === 'registration_enabled'
                        ? 'event-registration-fields'
                        : undefined
                }
                aria-describedby={`${id}-description${error ? ` ${id}-error` : ''}`}
                aria-expanded={
                    id === 'registration_enabled' ? checked : undefined
                }
                aria-invalid={Boolean(error)}
                onClick={() => onCheckedChange(!checked)}
                className={`grid min-h-18 w-full cursor-pointer grid-cols-[minmax(0,1fr)_auto] items-center gap-4 border-l-[3px] px-3 py-3.5 text-left transition-colors outline-none focus-visible:ring-2 focus-visible:ring-signal-500/50 focus-visible:ring-inset sm:px-4 ${
                    checked
                        ? activeClasses
                        : 'border-transparent bg-neutral-50/60 hover:bg-neutral-100/70 dark:bg-neutral-900/30 dark:hover:bg-neutral-900/60'
                }`}
            >
                <span className="min-w-0">
                    <span className="block text-sm font-semibold text-neutral-950 dark:text-white">
                        {title}
                    </span>
                    <span
                        id={`${id}-description`}
                        className="mt-1 block text-xs leading-5 text-neutral-500 dark:text-neutral-400"
                    >
                        {description}
                    </span>
                </span>
                <span className="flex shrink-0 items-center gap-3">
                    <span
                        aria-hidden="true"
                        className="hidden text-xs font-medium text-neutral-500 @min-[30rem]/fields:inline dark:text-neutral-400"
                    >
                        {checked ? 'Aan' : 'Uit'}
                    </span>
                    <span
                        aria-hidden="true"
                        className={`block h-6 w-11 rounded-full shadow-inner ring-1 transition-colors ring-inset ${
                            checked
                                ? variant === 'warning'
                                    ? 'bg-amber-500 ring-amber-700/15'
                                    : 'bg-flight-500 ring-flight-700/15'
                                : 'bg-neutral-400 ring-neutral-500/25 dark:bg-neutral-600 dark:ring-neutral-500/40'
                        }`}
                    >
                        <span
                            className={`block size-5 translate-y-0.5 rounded-full bg-white shadow-sm transition-transform motion-reduce:transition-none ${
                                checked ? 'translate-x-5.5' : 'translate-x-0.5'
                            }`}
                        />
                    </span>
                </span>
            </button>
            <InputError
                id={`${id}-error`}
                message={error}
                className="px-3 py-2 sm:px-4"
            />
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
