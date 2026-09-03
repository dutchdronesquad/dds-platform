import { Form, Link } from '@inertiajs/react';
import {
    ArrowUpRight,
    ChevronLeft,
    ExternalLink,
    Globe,
    Home,
    MapPin,
    Pencil,
    Save,
    Trash2,
    TriangleAlert,
} from 'lucide-react';
import { useState } from 'react';
import {
    destroy,
    index,
} from '@/actions/App/Http/Controllers/Admin/LocationController';
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
import { LocationAddressSearch } from '@/components/admin/location-address-search';
import type { ResolvedAddress } from '@/components/admin/location-address-search';
import { MarkdownEditor } from '@/components/admin/markdown-editor';
import { MediaAssetPicker } from '@/components/admin/media-asset-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { show as publicLocationShow } from '@/routes/locations';
import type {
    EditableLocation,
    LocationFormOptions,
    SelectOption,
} from './types';

type MutationForm = {
    action: string;
    method: 'post';
};

const facilityOptions: SelectOption[] = [
    { value: 'parking', label: 'Parkeren' },
    { value: 'power', label: 'Stroomvoorziening' },
    { value: 'toilets', label: 'Toiletten' },
    { value: 'tables_and_chairs', label: 'Tafels en stoelen' },
    { value: 'catering', label: 'Catering' },
    { value: 'wifi', label: 'Wifi' },
];

const locationFormOutlineItems = [
    {
        description: 'Naam, slug en omgeving',
        icon: Home,
        id: 'location-basics',
        title: 'Basisinformatie',
    },
    {
        description: 'Straat, postcode en plaats',
        icon: MapPin,
        id: 'location-address',
        title: 'Adres',
    },
    {
        description: 'Afmetingen en faciliteiten',
        icon: MapPin,
        id: 'location-practical',
        title: 'Praktische informatie',
    },
    {
        description: 'Coördinaten en website',
        icon: Globe,
        id: 'location-map',
        title: 'Kaart en contact',
    },
];

export function LocationForm({
    form,
    location,
    options,
}: {
    form: MutationForm;
    location?: EditableLocation;
    options: LocationFormOptions;
}) {
    const [name, setName] = useState(location?.name ?? '');
    const [slug, setSlug] = useState(location?.slug ?? '');
    const [slugManuallyEdited, setSlugManuallyEdited] = useState(
        Boolean(location?.slug),
    );
    const [coverImage, setCoverImage] = useState(location?.coverImage ?? null);
    const [facilities, setFacilities] = useState<string[]>(
        location?.facilities ?? [],
    );
    const [street, setStreet] = useState(location?.street ?? '');
    const [houseNumber, setHouseNumber] = useState(location?.houseNumber ?? '');
    const [postalCode, setPostalCode] = useState(location?.postalCode ?? '');
    const [city, setCity] = useState(location?.city ?? '');
    const [countryCode, setCountryCode] = useState(
        location?.countryCode ?? 'NL',
    );
    const [latitude, setLatitude] = useState(location?.latitude ?? '');
    const [longitude, setLongitude] = useState(location?.longitude ?? '');
    const hasResolvedAddress = Boolean(street && postalCode && city);
    const [manualAddressEditing, setManualAddressEditing] = useState(false);

    function handleAddressSelected(address: ResolvedAddress): void {
        setStreet(address.street);
        setHouseNumber(address.houseNumber);
        setPostalCode(address.postalCode);
        setCity(address.city);
        setLatitude(address.latitude);
        setLongitude(address.longitude);
        setCountryCode(address.countryCode);
        setManualAddressEditing(false);
    }

    function toggleFacility(value: string): void {
        setFacilities((current) =>
            current.includes(value)
                ? current.filter((facility) => facility !== value)
                : [...current, value],
        );
    }

    return (
        <Form
            {...form}
            className="grid gap-0"
            options={{ preserveScroll: true }}
            setDefaultsOnSuccess
        >
            {({ errors, isDirty, processing, recentlySuccessful }) => {
                const hasAddressErrors = Boolean(
                    errors.street ||
                    errors.house_number ||
                    errors.postal_code ||
                    errors.city ||
                    errors.country_code,
                );
                const showAddressSummary =
                    hasResolvedAddress &&
                    !manualAddressEditing &&
                    !hasAddressErrors;
                const showManualAddressFields =
                    !showAddressSummary &&
                    (manualAddressEditing ||
                        hasAddressErrors ||
                        hasResolvedAddress);

                return (
                    <>
                        <AdminFormNavigationGuard isDirty={isDirty} />
                        <AdminFormActions
                            context={
                                name.trim() ||
                                (location
                                    ? 'Locatie bewerken'
                                    : 'Nieuwe locatie')
                            }
                            isDirty={isDirty}
                            isNew={!location}
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
                                ) : location ? (
                                    <>
                                        <span className="sm:hidden">
                                            Opslaan
                                        </span>
                                        <span className="hidden sm:inline">
                                            Wijzigingen opslaan
                                        </span>
                                    </>
                                ) : (
                                    'Locatie aanmaken'
                                )}
                            </Button>
                        </AdminFormActions>

                        <AdminFormLayout
                            asideFirstOnSmallScreens={false}
                            asideLayoutClassName="@min-[56rem]/admin-page:grid-cols-[minmax(0,1fr)_18.5rem] @min-[84rem]/admin-page:grid-cols-[minmax(0,1fr)_21.5rem]"
                            className="mx-auto w-full"
                            contentClassName="@container/location-main"
                            aside={<LocationFormAside location={location} />}
                        >
                            <AdminFormErrorSummary errors={errors} />

                            <AdminFormSection
                                id="location-basics"
                                className="@container/fields"
                                icon={Home}
                                title="Basisinformatie"
                                description="De naam, URL-slug en omgeving vormen de herkenbare basis van de locatie."
                            >
                                <div className="grid grid-cols-1 gap-5 @min-[46rem]/fields:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                                    <FormField
                                        id="name"
                                        label="Naam"
                                        error={errors.name}
                                        reserveSupportingTextSpace
                                    >
                                        <Input
                                            id="name"
                                            name="name"
                                            value={name}
                                            onChange={(inputEvent) => {
                                                const nextName =
                                                    inputEvent.target.value;

                                                setName(nextName);

                                                if (!slugManuallyEdited) {
                                                    setSlug(
                                                        createSlug(nextName),
                                                    );
                                                }
                                            }}
                                            required
                                            maxLength={255}
                                            autoFocus={!location}
                                            autoComplete="off"
                                            placeholder="Bijv. Sportpaleis Alkmaar"
                                            aria-invalid={Boolean(errors.name)}
                                            aria-describedby={fieldDescription(
                                                'name',
                                                errors.name,
                                            )}
                                        />
                                    </FormField>
                                    <FormField
                                        id="slug"
                                        label="URL-slug (optioneel)"
                                        error={errors.slug}
                                        hint={
                                            slug
                                                ? `Publieke URL: /locations/${slug}`
                                                : undefined
                                        }
                                        reserveSupportingTextSpace
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
                                                            createSlug(name),
                                                );
                                            }}
                                            maxLength={255}
                                            placeholder="Automatisch uit naam"
                                            autoComplete="off"
                                            autoCapitalize="none"
                                            spellCheck={false}
                                            aria-invalid={Boolean(errors.slug)}
                                            aria-describedby={fieldDescription(
                                                'slug',
                                                errors.slug,
                                            )}
                                        />
                                    </FormField>
                                </div>
                                <div className="grid gap-5 @min-[46rem]/fields:max-w-xs">
                                    <FormField
                                        id="environment"
                                        label="Omgeving"
                                        error={errors.environment}
                                    >
                                        <FormSelect
                                            id="environment"
                                            name="environment"
                                            defaultValue={
                                                location?.environment ??
                                                'indoor'
                                            }
                                            options={options.environments}
                                            required
                                            invalid={Boolean(
                                                errors.environment,
                                            )}
                                            describedBy={fieldDescription(
                                                'environment',
                                                errors.environment,
                                            )}
                                        />
                                    </FormField>
                                </div>
                                <div className="grid gap-5">
                                    <FormField
                                        id="description_nl"
                                        label="Omschrijving"
                                        error={errors['description.nl']}
                                        hint="Markdown wordt ondersteund, zoals koppen, lijsten, links, vet en cursief."
                                    >
                                        <MarkdownEditor
                                            id="description_nl"
                                            name="description[nl]"
                                            defaultValue={
                                                location?.description.nl ??
                                                location?.description.en ??
                                                ''
                                            }
                                            required
                                            rows={8}
                                            maxLength={5000}
                                            placeholder="Een binnenlocatie voor FPV-droneraces."
                                            aria-invalid={Boolean(
                                                errors['description.nl'],
                                            )}
                                            aria-describedby={fieldDescription(
                                                'description_nl',
                                                errors['description.nl'],
                                                true,
                                            )}
                                        />
                                    </FormField>
                                </div>
                            </AdminFormSection>

                            <AdminFormSection
                                id="location-address"
                                className="@container/fields"
                                icon={MapPin}
                                title="Adres"
                                description="Het volledige, structurele adres van de locatie."
                            >
                                <FormField
                                    id="address_search"
                                    label="Adres opzoeken"
                                    hint={
                                        hasResolvedAddress
                                            ? undefined
                                            : 'Typ minimaal 4 tekens en kies een suggestie om straat, plaats en coördinaten automatisch in te vullen.'
                                    }
                                >
                                    <LocationAddressSearch
                                        onSelect={handleAddressSelected}
                                    />
                                </FormField>

                                {showAddressSummary ? (
                                    <div className="flex items-start justify-between gap-4 rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/40">
                                        <input
                                            type="hidden"
                                            name="street"
                                            value={street}
                                        />
                                        <input
                                            type="hidden"
                                            name="house_number"
                                            value={houseNumber}
                                        />
                                        <input
                                            type="hidden"
                                            name="postal_code"
                                            value={postalCode}
                                        />
                                        <input
                                            type="hidden"
                                            name="city"
                                            value={city}
                                        />
                                        <input
                                            type="hidden"
                                            name="country_code"
                                            value={countryCode}
                                        />
                                        <div className="min-w-0">
                                            <p className="font-medium text-neutral-950 dark:text-white">
                                                {street} {houseNumber}
                                            </p>
                                            <p className="mt-0.5 text-sm text-neutral-600 dark:text-neutral-400">
                                                {postalCode} {city}
                                                {countryCode !== 'NL' &&
                                                    ` · ${countryCode}`}
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                setManualAddressEditing(true)
                                            }
                                            className="shrink-0"
                                        >
                                            <Pencil />
                                            Corrigeren
                                        </Button>
                                    </div>
                                ) : showManualAddressFields ? (
                                    <div className="grid gap-5">
                                        {hasResolvedAddress &&
                                            !hasAddressErrors && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        setManualAddressEditing(
                                                            false,
                                                        )
                                                    }
                                                    className="w-fit"
                                                >
                                                    <ChevronLeft />
                                                    Terug naar adresweergave
                                                </Button>
                                            )}
                                        <div className="grid gap-5 @min-[36rem]/fields:grid-cols-[minmax(0,1fr)_9rem]">
                                            <FormField
                                                id="street"
                                                label="Straat"
                                                error={errors.street}
                                            >
                                                <Input
                                                    id="street"
                                                    name="street"
                                                    value={street}
                                                    onChange={(inputEvent) =>
                                                        setStreet(
                                                            inputEvent.target
                                                                .value,
                                                        )
                                                    }
                                                    required
                                                    maxLength={255}
                                                    aria-invalid={Boolean(
                                                        errors.street,
                                                    )}
                                                    aria-describedby={fieldDescription(
                                                        'street',
                                                        errors.street,
                                                    )}
                                                />
                                            </FormField>
                                            <FormField
                                                id="house_number"
                                                label="Huisnummer"
                                                error={errors.house_number}
                                            >
                                                <Input
                                                    id="house_number"
                                                    name="house_number"
                                                    value={houseNumber}
                                                    onChange={(inputEvent) =>
                                                        setHouseNumber(
                                                            inputEvent.target
                                                                .value,
                                                        )
                                                    }
                                                    required
                                                    maxLength={20}
                                                    aria-invalid={Boolean(
                                                        errors.house_number,
                                                    )}
                                                    aria-describedby={fieldDescription(
                                                        'house_number',
                                                        errors.house_number,
                                                    )}
                                                />
                                            </FormField>
                                        </div>
                                        <div className="grid gap-5 @min-[36rem]/fields:grid-cols-[9rem_minmax(0,1fr)_6rem]">
                                            <FormField
                                                id="postal_code"
                                                label="Postcode"
                                                error={errors.postal_code}
                                            >
                                                <Input
                                                    id="postal_code"
                                                    name="postal_code"
                                                    value={postalCode}
                                                    onChange={(inputEvent) =>
                                                        setPostalCode(
                                                            inputEvent.target
                                                                .value,
                                                        )
                                                    }
                                                    required
                                                    maxLength={20}
                                                    aria-invalid={Boolean(
                                                        errors.postal_code,
                                                    )}
                                                    aria-describedby={fieldDescription(
                                                        'postal_code',
                                                        errors.postal_code,
                                                    )}
                                                />
                                            </FormField>
                                            <FormField
                                                id="city"
                                                label="Plaats"
                                                error={errors.city}
                                            >
                                                <Input
                                                    id="city"
                                                    name="city"
                                                    value={city}
                                                    onChange={(inputEvent) =>
                                                        setCity(
                                                            inputEvent.target
                                                                .value,
                                                        )
                                                    }
                                                    required
                                                    maxLength={255}
                                                    aria-invalid={Boolean(
                                                        errors.city,
                                                    )}
                                                    aria-describedby={fieldDescription(
                                                        'city',
                                                        errors.city,
                                                    )}
                                                />
                                            </FormField>
                                            <FormField
                                                id="country_code"
                                                label="Landcode"
                                                error={errors.country_code}
                                            >
                                                <Input
                                                    id="country_code"
                                                    name="country_code"
                                                    value={countryCode}
                                                    onChange={(inputEvent) =>
                                                        setCountryCode(
                                                            inputEvent.target.value.toUpperCase(),
                                                        )
                                                    }
                                                    required
                                                    maxLength={2}
                                                    autoCapitalize="characters"
                                                    aria-invalid={Boolean(
                                                        errors.country_code,
                                                    )}
                                                    aria-describedby={fieldDescription(
                                                        'country_code',
                                                        errors.country_code,
                                                    )}
                                                />
                                            </FormField>
                                        </div>
                                    </div>
                                ) : (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            setManualAddressEditing(true)
                                        }
                                        className="w-fit"
                                    >
                                        <Pencil />
                                        Adres handmatig invoeren
                                    </Button>
                                )}
                            </AdminFormSection>

                            <AdminFormSection
                                id="location-practical"
                                className="@container/fields"
                                icon={MapPin}
                                title="Praktische informatie"
                                description="Fysieke afmetingen en beschikbare faciliteiten voor bezoekers."
                            >
                                <div className="grid gap-5 @min-[36rem]/fields:grid-cols-2">
                                    <FormField
                                        id="floor_size_square_metres"
                                        label="Vloeroppervlak (m², optioneel)"
                                        error={errors.floor_size_square_metres}
                                        reserveSupportingTextSpace
                                    >
                                        <Input
                                            id="floor_size_square_metres"
                                            name="floor_size_square_metres"
                                            type="number"
                                            inputMode="numeric"
                                            min="1"
                                            max="65535"
                                            defaultValue={
                                                location?.floorSizeSquareMetres ??
                                                ''
                                            }
                                            aria-invalid={Boolean(
                                                errors.floor_size_square_metres,
                                            )}
                                            aria-describedby={fieldDescription(
                                                'floor_size_square_metres',
                                                errors.floor_size_square_metres,
                                            )}
                                        />
                                    </FormField>
                                    <FormField
                                        id="ceiling_height_metres"
                                        label="Plafondhoogte (m, optioneel)"
                                        error={errors.ceiling_height_metres}
                                        reserveSupportingTextSpace
                                    >
                                        <Input
                                            id="ceiling_height_metres"
                                            name="ceiling_height_metres"
                                            type="number"
                                            inputMode="decimal"
                                            min="0"
                                            max="999.99"
                                            step="0.01"
                                            defaultValue={
                                                location?.ceilingHeightMetres ??
                                                ''
                                            }
                                            aria-invalid={Boolean(
                                                errors.ceiling_height_metres,
                                            )}
                                            aria-describedby={fieldDescription(
                                                'ceiling_height_metres',
                                                errors.ceiling_height_metres,
                                            )}
                                        />
                                    </FormField>
                                </div>
                                <fieldset className="grid gap-3">
                                    <legend className="text-sm font-medium text-neutral-950 dark:text-white">
                                        Faciliteiten
                                    </legend>
                                    <div className="grid grid-cols-2 gap-2 @min-[30rem]/fields:grid-cols-3">
                                        {facilityOptions.map((option) => (
                                            <label
                                                key={option.value}
                                                className="flex items-center gap-2 rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-700 has-checked:border-signal-400 has-checked:bg-signal-50/60 dark:border-neutral-800 dark:text-neutral-300 dark:has-checked:border-signal-500 dark:has-checked:bg-signal-500/10"
                                            >
                                                <input
                                                    type="checkbox"
                                                    name="facilities[]"
                                                    value={option.value}
                                                    checked={facilities.includes(
                                                        option.value,
                                                    )}
                                                    onChange={() =>
                                                        toggleFacility(
                                                            option.value,
                                                        )
                                                    }
                                                    className="size-4 rounded border-neutral-300 text-signal-600 focus-visible:ring-2 focus-visible:ring-ring"
                                                />
                                                {option.label}
                                            </label>
                                        ))}
                                    </div>
                                    <InputError
                                        id="facilities-error"
                                        message={errors.facilities}
                                    />
                                </fieldset>
                            </AdminFormSection>

                            <AdminFormSection
                                id="location-map"
                                className="@container/fields"
                                icon={Globe}
                                title="Kaart en contact"
                                description="Coördinaten voor de kaartweergave en een optionele website."
                            >
                                <div className="grid gap-5">
                                    <div className="grid gap-5 @min-[30rem]/fields:grid-cols-2">
                                        <FormField
                                            id="latitude"
                                            label="Breedtegraad (optioneel)"
                                            error={errors.latitude}
                                            hint="Automatisch ingevuld door hierboven een adres op te zoeken."
                                            reserveSupportingTextSpace
                                        >
                                            <Input
                                                id="latitude"
                                                name="latitude"
                                                type="number"
                                                inputMode="decimal"
                                                min="-90"
                                                max="90"
                                                step="0.0000001"
                                                value={latitude}
                                                onChange={(inputEvent) =>
                                                    setLatitude(
                                                        inputEvent.target.value,
                                                    )
                                                }
                                                placeholder="52.6317600"
                                                aria-invalid={Boolean(
                                                    errors.latitude,
                                                )}
                                                aria-describedby={fieldDescription(
                                                    'latitude',
                                                    errors.latitude,
                                                )}
                                            />
                                        </FormField>
                                        <FormField
                                            id="longitude"
                                            label="Lengtegraad (optioneel)"
                                            error={errors.longitude}
                                            reserveSupportingTextSpace
                                        >
                                            <Input
                                                id="longitude"
                                                name="longitude"
                                                type="number"
                                                inputMode="decimal"
                                                min="-180"
                                                max="180"
                                                step="0.0000001"
                                                value={longitude}
                                                onChange={(inputEvent) =>
                                                    setLongitude(
                                                        inputEvent.target.value,
                                                    )
                                                }
                                                placeholder="4.7336300"
                                                aria-invalid={Boolean(
                                                    errors.longitude,
                                                )}
                                                aria-describedby={fieldDescription(
                                                    'longitude',
                                                    errors.longitude,
                                                )}
                                            />
                                        </FormField>
                                    </div>
                                    <FormField
                                        id="website_url"
                                        label="Website (optioneel)"
                                        error={errors.website_url}
                                    >
                                        <Input
                                            id="website_url"
                                            name="website_url"
                                            type="url"
                                            defaultValue={
                                                location?.websiteUrl ?? ''
                                            }
                                            maxLength={2048}
                                            placeholder="https://…"
                                            inputMode="url"
                                            autoComplete="url"
                                            aria-invalid={Boolean(
                                                errors.website_url,
                                            )}
                                            aria-describedby={fieldDescription(
                                                'website_url',
                                                errors.website_url,
                                            )}
                                        />
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
                                            invalid={Boolean(
                                                errors.cover_image_id,
                                            )}
                                            describedBy={fieldDescription(
                                                'cover_image_id',
                                                errors.cover_image_id,
                                            )}
                                        />
                                    </FormField>
                                </div>
                            </AdminFormSection>

                            {location?.capabilities.delete && (
                                <AdminFormSection
                                    id="location-danger-zone"
                                    icon={TriangleAlert}
                                    tone="danger"
                                    title="Gevarenzone"
                                    description="Verwijder deze locatie alleen wanneer er geen events meer naar verwijzen. Deze actie kan niet ongedaan worden gemaakt."
                                >
                                    <div>
                                        <AdminConfirmationDialog
                                            form={destroy.form(location.id)}
                                            intent="delete"
                                            subject={location.name}
                                            trigger={
                                                <Button
                                                    type="button"
                                                    variant="destructive"
                                                    size="sm"
                                                >
                                                    <Trash2 />
                                                    Locatie verwijderen
                                                </Button>
                                            }
                                        />
                                    </div>
                                </AdminFormSection>
                            )}

                            {location &&
                                !location.capabilities.delete &&
                                location.eventsCount > 0 && (
                                    <AdminFormSection
                                        id="location-danger-zone"
                                        icon={TriangleAlert}
                                        tone="danger"
                                        title="Gevarenzone"
                                        description={`Deze locatie kan niet worden verwijderd zolang er nog ${location.eventsCount === 1 ? '1 event' : `${location.eventsCount} events`} aan gekoppeld ${location.eventsCount === 1 ? 'is' : 'zijn'}.`}
                                    >
                                        <div />
                                    </AdminFormSection>
                                )}
                        </AdminFormLayout>
                    </>
                );
            }}
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

function LocationFormAside({ location }: { location?: EditableLocation }) {
    return (
        <div className="overflow-clip rounded-2xl border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-950">
            <section className="p-5 @min-[84rem]/admin-page:p-6">
                <p className="text-xs font-semibold tracking-[0.14em] text-neutral-500 uppercase">
                    Gebruik
                </p>
                <p className="mt-1 text-sm font-semibold text-neutral-950 dark:text-white">
                    {location
                        ? location.eventsCount === 1
                            ? '1 event gekoppeld'
                            : `${location.eventsCount} events gekoppeld`
                        : 'Nog geen events gekoppeld'}
                </p>
                {location && (
                    <Button
                        asChild
                        variant="outline"
                        className="border-signal-200 text-signal-800 hover:text-signal-900 dark:text-signal-200 mt-4 h-11 w-full justify-start rounded-xl bg-signal-50/70 px-3 shadow-none hover:border-signal-300 hover:bg-signal-100 focus-visible:ring-signal-500/30 dark:border-signal-500/25 dark:bg-signal-500/10 dark:hover:border-signal-500/40 dark:hover:bg-signal-500/15 dark:hover:text-signal-100"
                    >
                        <Link
                            data-sidebar-action="public"
                            href={publicLocationShow(location.slug)}
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
            </section>
            {location && <AdminActivityMetadata activity={location.activity} />}
            <AdminFormOutline
                description="Spring direct naar een onderdeel van het formulier."
                items={locationFormOutlineItems}
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
    children: React.ReactNode;
    className?: string;
    error?: string;
    hint?: string;
    id: string;
    label: string;
    reserveSupportingTextSpace?: boolean;
}) {
    return (
        <div
            data-field={id}
            className={className ? `grid gap-2 ${className}` : 'grid gap-2'}
        >
            <Label htmlFor={id}>{label}</Label>
            {children}
            {(hint || error || reserveSupportingTextSpace) && (
                <div
                    className={
                        reserveSupportingTextSpace ? 'min-h-10' : 'min-h-5'
                    }
                >
                    {hint && !error && (
                        <p className="text-xs leading-5 text-neutral-500 dark:text-neutral-400">
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
    options,
    required,
}: {
    defaultValue: string;
    describedBy?: string;
    id: string;
    invalid: boolean;
    name: string;
    options: SelectOption[];
    required?: boolean;
}) {
    const [value, setValue] = useState(defaultValue);
    const selectedValue =
        value === '' ? (required ? undefined : emptySelectValue) : value;

    return (
        <>
            <input type="hidden" name={name} value={value} />
            <Select
                value={selectedValue}
                onValueChange={(nextValue) =>
                    setValue(nextValue === emptySelectValue ? '' : nextValue)
                }
                required={required}
            >
                <SelectTrigger
                    id={id}
                    aria-invalid={invalid}
                    aria-describedby={describedBy}
                    className="w-full"
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent align="start">
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
