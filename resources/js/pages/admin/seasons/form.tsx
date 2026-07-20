import { Form, Link } from '@inertiajs/react';
import {
    ArrowUpRight,
    CalendarRange,
    ChevronDown,
    ExternalLink,
    Save,
    Ticket,
    TriangleAlert,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import {
    destroy,
    index,
} from '@/actions/App/Http/Controllers/Admin/SeasonController';
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
import { show as publicSeasonShow } from '@/routes/seasons';
import type { EditableSeason, SalesState, SalesStateOption } from './types';

type MutationForm = {
    action: string;
    method: 'post';
};

const advancedTicketErrorKeys = [
    'ticket_capacity',
    'ticket_copy',
    'ticket_sales_closes_at',
    'ticket_sales_opens_at',
] as const;

const seasonFormOutlineItems = [
    {
        description: 'Naam en publieke URL',
        icon: CalendarRange,
        id: 'season-basics',
        title: 'Seizoensgegevens',
    },
    {
        description: 'Pakketprijs en verkoop',
        icon: Ticket,
        id: 'season-ticket',
        title: 'Seizoensticket',
    },
];

export function SeasonForm({
    form,
    salesStateOptions,
    season,
}: {
    form: MutationForm;
    salesStateOptions: SalesStateOption[];
    season?: EditableSeason;
}) {
    const [name, setName] = useState(season?.name ?? '');
    const [slug, setSlug] = useState(season?.slug ?? '');
    const [slugManuallyEdited, setSlugManuallyEdited] = useState(
        Boolean(season?.slug),
    );
    const [ticketOffered, setTicketOffered] = useState(
        season?.ticketOffered ?? false,
    );
    const [ticketSalesState, setTicketSalesState] = useState(
        season?.ticketSalesState ?? 'coming_soon',
    );
    const [advancedTicketSettingsOpen, setAdvancedTicketSettingsOpen] =
        useState(
            Boolean(
                season?.ticketCapacity ||
                season?.ticketCopy ||
                season?.ticketSalesClosesAt ||
                season?.ticketSalesOpensAt,
            ),
        );

    return (
        <Form
            {...form}
            className="grid gap-0"
            options={{ preserveScroll: true }}
        >
            {({ errors, isDirty, processing, recentlySuccessful }) => {
                const hasAdvancedTicketErrors = advancedTicketErrorKeys.some(
                    (key) => Boolean(errors[key]),
                );

                return (
                    <>
                        <AdminFormNavigationGuard isDirty={isDirty} />
                        <AdminFormActions
                            context={
                                name.trim() ||
                                (season ? 'Seizoen bewerken' : 'Nieuw seizoen')
                            }
                            isDirty={isDirty}
                            isNew={!season}
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
                                ) : season ? (
                                    <>
                                        <span className="sm:hidden">
                                            Opslaan
                                        </span>
                                        <span className="hidden sm:inline">
                                            Wijzigingen opslaan
                                        </span>
                                    </>
                                ) : (
                                    'Seizoen aanmaken'
                                )}
                            </Button>
                        </AdminFormActions>

                        <AdminFormLayout
                            asideFirstOnSmallScreens={false}
                            asideLayoutClassName="@min-[56rem]/admin-page:grid-cols-[minmax(0,1fr)_18.5rem] @min-[84rem]/admin-page:grid-cols-[minmax(0,1fr)_21.5rem]"
                            className="mx-auto w-full"
                            aside={
                                <SeasonAside
                                    eventCount={season?.eventCount ?? 0}
                                    season={season}
                                />
                            }
                        >
                            <AdminFormErrorSummary errors={errors} />

                            <div
                                data-testid="season-form-sections"
                                className="grid"
                            >
                                <AdminFormSection
                                    id="season-basics"
                                    className="@container/fields"
                                    icon={CalendarRange}
                                    title="Seizoensgegevens"
                                    description={
                                        season
                                            ? 'De naam verschijnt bij gekoppelde events en op de publieke seizoenpagina. Laat de URL-slug leeg om hem opnieuw uit de naam te maken.'
                                            : 'Geef het seizoen een herkenbare naam. De URL wordt automatisch uit deze naam gemaakt.'
                                    }
                                >
                                    <div className="grid items-start gap-5 @min-[40rem]/fields:grid-cols-2">
                                        <FormField
                                            id="name"
                                            label="Seizoensnaam"
                                            error={errors.name}
                                        >
                                            {(fieldProps) => (
                                                <Input
                                                    id="name"
                                                    name="name"
                                                    value={name}
                                                    onChange={(event) => {
                                                        const nextName =
                                                            event.target.value;

                                                        setName(nextName);

                                                        if (
                                                            !slugManuallyEdited
                                                        ) {
                                                            setSlug(
                                                                createSlug(
                                                                    nextName,
                                                                ),
                                                            );
                                                        }
                                                    }}
                                                    required
                                                    maxLength={255}
                                                    autoFocus
                                                    placeholder={
                                                        season
                                                            ? undefined
                                                            : 'Zomerseizoen 2026'
                                                    }
                                                    {...fieldProps}
                                                />
                                            )}
                                        </FormField>
                                        <FormField
                                            id="slug"
                                            label="URL-slug (optioneel)"
                                            error={errors.slug}
                                        >
                                            {(fieldProps) => (
                                                <Input
                                                    id="slug"
                                                    name="slug"
                                                    value={slug}
                                                    onChange={(event) => {
                                                        const nextSlug =
                                                            event.target.value;

                                                        setSlug(nextSlug);
                                                        setSlugManuallyEdited(
                                                            nextSlug !== '' &&
                                                                nextSlug !==
                                                                    createSlug(
                                                                        name,
                                                                    ),
                                                        );
                                                    }}
                                                    maxLength={255}
                                                    placeholder="Automatisch uit seizoensnaam"
                                                    autoCapitalize="none"
                                                    spellCheck={false}
                                                    {...fieldProps}
                                                />
                                            )}
                                        </FormField>
                                    </div>
                                </AdminFormSection>

                                <AdminFormSection
                                    id="season-ticket"
                                    className="@container/ticket"
                                    icon={Ticket}
                                    title="Seizoensticket"
                                    description="Sla deze stap over als deelnemers per event inschrijven. Ticketvelden verschijnen pas wanneer je een pakket aanbiedt."
                                >
                                    <div
                                        data-testid="season-ticket-disclosure"
                                        data-enabled={ticketOffered}
                                        className="overflow-hidden border-y border-neutral-200 bg-transparent dark:border-neutral-800"
                                    >
                                        <input
                                            type="hidden"
                                            name="ticket_offered"
                                            value={ticketOffered ? '1' : '0'}
                                        />
                                        <button
                                            id="ticket_offered"
                                            type="button"
                                            role="switch"
                                            aria-label="Seizoensticket aanbieden"
                                            aria-checked={ticketOffered}
                                            aria-controls="season-ticket-fields"
                                            aria-describedby={
                                                errors.ticket_offered
                                                    ? 'ticket-offered-description ticket-offered-error'
                                                    : 'ticket-offered-description'
                                            }
                                            aria-expanded={ticketOffered}
                                            aria-invalid={Boolean(
                                                errors.ticket_offered,
                                            )}
                                            onClick={() =>
                                                setTicketOffered(
                                                    (isOffered) => !isOffered,
                                                )
                                            }
                                            data-testid="season-ticket-toggle"
                                            className={`grid min-h-18 w-full cursor-pointer grid-cols-[minmax(0,1fr)_auto] items-center gap-4 border-l-[3px] px-3 py-3.5 text-left transition-colors outline-none focus-visible:ring-2 focus-visible:ring-signal-500/50 focus-visible:ring-inset sm:px-4 ${
                                                ticketOffered
                                                    ? 'border-flight-500 bg-flight-50/55 hover:bg-flight-50/80 dark:border-flight-400 dark:bg-flight-500/[0.07] dark:hover:bg-flight-500/[0.1]'
                                                    : 'border-transparent hover:bg-neutral-50 dark:hover:bg-neutral-900/60'
                                            }`}
                                        >
                                            <span className="min-w-0">
                                                <span
                                                    className={`block text-sm font-semibold ${
                                                        ticketOffered
                                                            ? 'text-flight-700 dark:text-flight-300'
                                                            : 'text-neutral-950 dark:text-white'
                                                    }`}
                                                >
                                                    Seizoensticket aanbieden
                                                </span>
                                                <span
                                                    id="ticket-offered-description"
                                                    className="mt-1 block text-xs leading-5 text-neutral-500 dark:text-neutral-400"
                                                >
                                                    Prijs, verkoopstatus en
                                                    ticketlink gelden voor alle
                                                    events.
                                                </span>
                                            </span>
                                            <span className="flex shrink-0 items-center gap-3">
                                                <span
                                                    aria-hidden="true"
                                                    className={`hidden text-xs font-medium @min-[30rem]/ticket:inline ${
                                                        ticketOffered
                                                            ? 'text-flight-700 dark:text-flight-300'
                                                            : 'text-neutral-500 dark:text-neutral-400'
                                                    }`}
                                                >
                                                    {ticketOffered
                                                        ? 'Ingeschakeld'
                                                        : 'Uit'}
                                                </span>
                                                <span
                                                    aria-hidden="true"
                                                    className={`block h-6 w-11 rounded-full shadow-inner ring-1 transition-colors ring-inset ${
                                                        ticketOffered
                                                            ? 'bg-flight-500 ring-flight-700/15'
                                                            : 'bg-neutral-400 ring-neutral-500/25 dark:bg-neutral-600 dark:ring-neutral-500/40'
                                                    }`}
                                                >
                                                    <span
                                                        className={`block size-5 translate-y-0.5 rounded-full bg-white shadow-sm transition-transform motion-reduce:transition-none ${
                                                            ticketOffered
                                                                ? 'translate-x-5.5'
                                                                : 'translate-x-0.5'
                                                        }`}
                                                    />
                                                </span>
                                            </span>
                                        </button>
                                        <InputError
                                            id="ticket-offered-error"
                                            message={errors.ticket_offered}
                                            className="border-t border-destructive/15 px-3 py-3 sm:px-4"
                                        />

                                        <fieldset
                                            id="season-ticket-fields"
                                            role="region"
                                            aria-label="Instellingen voor het seizoensticket"
                                            disabled={!ticketOffered}
                                            hidden={!ticketOffered}
                                            className="min-w-0 border-t border-neutral-200 bg-white px-3 py-5 sm:px-4 sm:py-6 dark:border-neutral-800 dark:bg-neutral-950"
                                        >
                                            <legend className="sr-only">
                                                Instellingen voor het
                                                seizoensticket
                                            </legend>
                                            <div className="grid gap-6">
                                                <div className="flex items-center gap-3">
                                                    <span
                                                        aria-hidden="true"
                                                        className="size-1.5 shrink-0 rounded-full bg-flight-500 dark:bg-flight-400"
                                                    />
                                                    <p className="text-xs font-semibold tracking-[0.08em] text-neutral-500 uppercase dark:text-neutral-400">
                                                        Verkoopinstellingen
                                                    </p>
                                                    <span
                                                        aria-hidden="true"
                                                        className="h-px flex-1 bg-neutral-200 dark:bg-neutral-800"
                                                    />
                                                </div>
                                                <div
                                                    data-testid="season-ticket-primary-fields"
                                                    className="grid items-start gap-x-5 gap-y-4 @min-[40rem]/ticket:grid-cols-2"
                                                >
                                                    <FormField
                                                        id="ticket_sales_state"
                                                        label="Verkoopstatus"
                                                        error={
                                                            errors.ticket_sales_state
                                                        }
                                                        reserveSupportingTextSpace
                                                    >
                                                        {(fieldProps) => (
                                                            <>
                                                                <input
                                                                    type="hidden"
                                                                    name="ticket_sales_state"
                                                                    value={
                                                                        ticketSalesState
                                                                    }
                                                                />
                                                                <Select
                                                                    value={
                                                                        ticketSalesState
                                                                    }
                                                                    onValueChange={(
                                                                        value,
                                                                    ) =>
                                                                        setTicketSalesState(
                                                                            value as SalesState,
                                                                        )
                                                                    }
                                                                    required
                                                                >
                                                                    <SelectTrigger
                                                                        id="ticket_sales_state"
                                                                        aria-labelledby="ticket_sales_state-label"
                                                                        className="w-full"
                                                                        {...fieldProps}
                                                                    >
                                                                        <SelectValue>
                                                                            {
                                                                                salesStateOptions.find(
                                                                                    (
                                                                                        option,
                                                                                    ) =>
                                                                                        option.value ===
                                                                                        ticketSalesState,
                                                                                )
                                                                                    ?.label
                                                                            }
                                                                        </SelectValue>
                                                                    </SelectTrigger>
                                                                    <SelectContent align="start">
                                                                        {salesStateOptions.map(
                                                                            (
                                                                                option,
                                                                            ) => (
                                                                                <SelectItem
                                                                                    key={
                                                                                        option.value
                                                                                    }
                                                                                    value={
                                                                                        option.value
                                                                                    }
                                                                                >
                                                                                    {
                                                                                        option.label
                                                                                    }
                                                                                </SelectItem>
                                                                            ),
                                                                        )}
                                                                    </SelectContent>
                                                                </Select>
                                                            </>
                                                        )}
                                                    </FormField>
                                                    <FormField
                                                        id="ticket_price_euros"
                                                        label="Seizoenprijs (optioneel)"
                                                        hint="Vul 0 in voor gratis; laat leeg als de prijs later volgt."
                                                        error={
                                                            errors.ticket_price_euros
                                                        }
                                                        reserveSupportingTextSpace
                                                    >
                                                        {(fieldProps) => (
                                                            <div className="relative">
                                                                <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-neutral-500">
                                                                    €
                                                                </span>
                                                                <Input
                                                                    id="ticket_price_euros"
                                                                    name="ticket_price_euros"
                                                                    type="number"
                                                                    inputMode="decimal"
                                                                    min="0"
                                                                    max="42949672.95"
                                                                    step="0.01"
                                                                    defaultValue={
                                                                        season?.ticketPriceEuros ??
                                                                        ''
                                                                    }
                                                                    className="pl-8"
                                                                    {...fieldProps}
                                                                />
                                                            </div>
                                                        )}
                                                    </FormField>
                                                    <FormField
                                                        id="ticket_registration_url"
                                                        label={
                                                            ticketSalesState ===
                                                            'available'
                                                                ? 'Ticketlink'
                                                                : 'Ticketlink (optioneel)'
                                                        }
                                                        hint={
                                                            ticketSalesState ===
                                                            'available'
                                                                ? 'Verplicht zolang de verkoopstatus Beschikbaar is.'
                                                                : undefined
                                                        }
                                                        error={
                                                            errors.ticket_registration_url
                                                        }
                                                        className="@min-[40rem]/ticket:col-span-2"
                                                    >
                                                        {(fieldProps) => (
                                                            <Input
                                                                id="ticket_registration_url"
                                                                name="ticket_registration_url"
                                                                type="url"
                                                                inputMode="url"
                                                                maxLength={2048}
                                                                placeholder="https://tickets.example.nl/…"
                                                                defaultValue={
                                                                    season?.ticketRegistrationUrl ??
                                                                    ''
                                                                }
                                                                required={
                                                                    ticketSalesState ===
                                                                    'available'
                                                                }
                                                                autoCapitalize="none"
                                                                spellCheck={
                                                                    false
                                                                }
                                                                {...fieldProps}
                                                            />
                                                        )}
                                                    </FormField>
                                                </div>

                                                <details
                                                    open={
                                                        advancedTicketSettingsOpen ||
                                                        hasAdvancedTicketErrors
                                                    }
                                                    onToggle={(event) =>
                                                        setAdvancedTicketSettingsOpen(
                                                            event.currentTarget
                                                                .open,
                                                        )
                                                    }
                                                    className="group @container/ticket-advanced border-t border-neutral-200 pt-3 dark:border-neutral-800"
                                                >
                                                    <summary className="-mx-2 flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 rounded-lg px-2 text-neutral-800 transition-colors outline-none hover:bg-flight-50/60 hover:text-flight-700 focus-visible:ring-2 focus-visible:ring-signal-500/50 dark:text-neutral-200 dark:hover:bg-flight-500/[0.07] dark:hover:text-flight-300 [&::-webkit-details-marker]:hidden">
                                                        <span className="min-w-0">
                                                            <span className="block text-sm font-semibold">
                                                                Aanvullende
                                                                ticketinstellingen
                                                            </span>
                                                            <span className="mt-0.5 block text-xs leading-5 font-normal text-neutral-500 dark:text-neutral-400">
                                                                Verkoopperiode,
                                                                ticketlimiet en
                                                                publieke
                                                                omschrijving
                                                            </span>
                                                        </span>
                                                        <span className="flex shrink-0 items-center gap-2">
                                                            {hasAdvancedTicketErrors && (
                                                                <span className="rounded-full bg-destructive/10 px-2 py-1 text-[0.68rem] font-semibold text-destructive">
                                                                    Controle
                                                                    nodig
                                                                </span>
                                                            )}
                                                            <ChevronDown
                                                                aria-hidden="true"
                                                                className="size-4 text-neutral-500 transition-transform group-open:rotate-180 motion-reduce:transition-none"
                                                            />
                                                        </span>
                                                    </summary>
                                                    <div className="grid gap-6 pt-5">
                                                        <div
                                                            data-testid="season-ticket-sales-window"
                                                            className="grid items-start gap-5 @min-[40rem]/ticket-advanced:grid-cols-2"
                                                        >
                                                            <FormField
                                                                id="ticket_sales_opens_at"
                                                                label="Verkoop opent (optioneel)"
                                                                error={
                                                                    errors.ticket_sales_opens_at
                                                                }
                                                            >
                                                                {(
                                                                    fieldProps,
                                                                ) => (
                                                                    <DateTimePicker
                                                                        id="ticket_sales_opens_at"
                                                                        label="Verkoop opent"
                                                                        name="ticket_sales_opens_at"
                                                                        defaultValue={
                                                                            season?.ticketSalesOpensAt ??
                                                                            ''
                                                                        }
                                                                        {...fieldProps}
                                                                    />
                                                                )}
                                                            </FormField>
                                                            <FormField
                                                                id="ticket_sales_closes_at"
                                                                label="Verkoop sluit (optioneel)"
                                                                hint="Moet na het startmoment liggen."
                                                                error={
                                                                    errors.ticket_sales_closes_at
                                                                }
                                                            >
                                                                {(
                                                                    fieldProps,
                                                                ) => (
                                                                    <DateTimePicker
                                                                        id="ticket_sales_closes_at"
                                                                        label="Verkoop sluit"
                                                                        name="ticket_sales_closes_at"
                                                                        defaultValue={
                                                                            season?.ticketSalesClosesAt ??
                                                                            ''
                                                                        }
                                                                        {...fieldProps}
                                                                    />
                                                                )}
                                                            </FormField>
                                                        </div>
                                                        <div className="grid items-start gap-5 @min-[40rem]/ticket-advanced:grid-cols-[minmax(14rem,18rem)_minmax(0,1fr)]">
                                                            <FormField
                                                                id="ticket_capacity"
                                                                label="Ticketlimiet (optioneel)"
                                                                hint="Maximaal aantal tickets voor het hele seizoen."
                                                                error={
                                                                    errors.ticket_capacity
                                                                }
                                                            >
                                                                {(
                                                                    fieldProps,
                                                                ) => (
                                                                    <Input
                                                                        id="ticket_capacity"
                                                                        name="ticket_capacity"
                                                                        type="number"
                                                                        inputMode="numeric"
                                                                        min="1"
                                                                        max="65535"
                                                                        placeholder="Geen limiet"
                                                                        defaultValue={
                                                                            season?.ticketCapacity ??
                                                                            ''
                                                                        }
                                                                        {...fieldProps}
                                                                    />
                                                                )}
                                                            </FormField>
                                                            <FormField
                                                                id="ticket_copy"
                                                                label="Ticketomschrijving (optioneel)"
                                                                error={
                                                                    errors.ticket_copy
                                                                }
                                                            >
                                                                {(
                                                                    fieldProps,
                                                                ) => (
                                                                    <textarea
                                                                        id="ticket_copy"
                                                                        name="ticket_copy"
                                                                        defaultValue={
                                                                            season?.ticketCopy ??
                                                                            ''
                                                                        }
                                                                        rows={4}
                                                                        maxLength={
                                                                            5000
                                                                        }
                                                                        placeholder="Toegang tot alle events in dit seizoen…"
                                                                        {...fieldProps}
                                                                        className="min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm"
                                                                    />
                                                                )}
                                                            </FormField>
                                                        </div>
                                                    </div>
                                                </details>
                                            </div>
                                        </fieldset>
                                    </div>
                                </AdminFormSection>
                            </div>

                            {season && season.eventCount === 0 && (
                                <AdminFormSection
                                    id="season-danger-zone"
                                    icon={TriangleAlert}
                                    tone="danger"
                                    title="Gevarenzone"
                                    description="Verwijder dit seizoen alleen wanneer je het niet meer nodig hebt. Deze actie kan niet ongedaan worden gemaakt."
                                >
                                    <div>
                                        <AdminConfirmationDialog
                                            form={destroy.form(season.slug)}
                                            intent="delete"
                                            subject={season.name}
                                            trigger={
                                                <Button
                                                    type="button"
                                                    variant="destructive"
                                                    size="sm"
                                                >
                                                    <Trash2 />
                                                    Seizoen verwijderen
                                                </Button>
                                            }
                                        />
                                    </div>
                                </AdminFormSection>
                            )}
                        </AdminFormLayout>
                    </>
                );
            }}
        </Form>
    );
}

function SeasonAside({
    eventCount,
    season,
}: {
    eventCount: number;
    season?: EditableSeason;
}) {
    return (
        <div className="overflow-clip rounded-2xl border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-950">
            <section className="p-5 @min-[84rem]/admin-page:p-6">
                <div>
                    <p className="text-xs font-semibold tracking-[0.14em] text-neutral-500 uppercase">
                        {season ? 'Gebruik' : 'Nieuw seizoen'}
                    </p>
                    {season ? (
                        <>
                            <p className="mt-3 text-3xl font-semibold text-neutral-950 tabular-nums dark:text-white">
                                {eventCount}
                            </p>
                            <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                {eventCount === 1
                                    ? 'gekoppeld event'
                                    : 'gekoppelde events'}
                            </p>
                            <p className="mt-4 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                Een seizoen met gekoppelde events kan niet
                                worden verwijderd. Verplaats die events eerst.
                            </p>
                            {eventCount > 0 && (
                                <Button
                                    asChild
                                    variant="outline"
                                    className="border-signal-200 text-signal-800 hover:text-signal-900 dark:text-signal-200 mt-5 h-11 w-full justify-start rounded-xl bg-signal-50/70 px-3 shadow-none hover:border-signal-300 hover:bg-signal-100 focus-visible:ring-signal-500/30 dark:border-signal-500/25 dark:bg-signal-500/10 dark:hover:border-signal-500/40 dark:hover:bg-signal-500/15 dark:hover:text-signal-100"
                                >
                                    <Link
                                        data-sidebar-action="public"
                                        href={publicSeasonShow(season.slug)}
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
                        </>
                    ) : (
                        <>
                            <p className="mt-3 text-base font-semibold text-neutral-950 dark:text-white">
                                Begin met de basis
                            </p>
                            <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                Je kunt events na het opslaan aan dit seizoen
                                koppelen. Een seizoensticket blijft volledig
                                optioneel.
                            </p>
                        </>
                    )}
                </div>
            </section>
            {season && <AdminActivityMetadata activity={season.activity} />}
            <AdminFormOutline
                description="Spring direct naar een onderdeel van het formulier."
                items={seasonFormOutlineItems}
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
    reserveSupportingTextSpace = false,
}: {
    children: (props: {
        'aria-describedby'?: string;
        'aria-invalid': boolean;
    }) => React.ReactNode;
    className?: string;
    error?: string;
    hint?: string;
    id: string;
    label: string;
    reserveSupportingTextSpace?: boolean;
}) {
    const errorId = error ? `${id}-error` : undefined;
    const hintId = hint && !error ? `${id}-hint` : undefined;
    const describedBy =
        [hintId, errorId].filter(Boolean).join(' ') || undefined;

    return (
        <div
            data-field={id}
            className={className ? `grid gap-2 ${className}` : 'grid gap-2'}
        >
            <Label id={`${id}-label`} htmlFor={id}>
                {label}
            </Label>
            {children({
                'aria-describedby': describedBy,
                'aria-invalid': Boolean(error),
            })}
            {(hint || error || reserveSupportingTextSpace) && (
                <div
                    className={
                        reserveSupportingTextSpace ? 'min-h-10' : 'min-h-5'
                    }
                >
                    {hint && !error && (
                        <p
                            id={hintId}
                            className="text-xs leading-5 text-neutral-500 dark:text-neutral-400"
                        >
                            {hint}
                        </p>
                    )}
                    <InputError id={errorId} message={error} />
                </div>
            )}
        </div>
    );
}
