import { Form, Head, Link } from '@inertiajs/react';
import {
    BadgeCheck,
    Ban,
    Clock3,
    RotateCcw,
    Save,
    ShieldCheck,
    Trash2,
    UserRound,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import {
    destroy,
    index,
    update,
} from '@/actions/App/Http/Controllers/Admin/UserController';
import {
    block,
    unblock,
} from '@/actions/App/Http/Controllers/Admin/UserStatusController';
import { AdminConfirmationDialog } from '@/components/admin/admin-confirmation-dialog';
import {
    AdminFormActions,
    AdminFormErrorSummary,
    AdminFormLayout,
    AdminFormNavigationGuard,
    AdminFormSection,
} from '@/components/admin/admin-form';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import type { EditableUser, Option, RoleOption } from './types';

const dateFormatter = new Intl.DateTimeFormat('nl-NL', {
    dateStyle: 'long',
    timeStyle: 'short',
    timeZone: 'Europe/Amsterdam',
});

export default function EditUser({
    user,
    roleOptions,
    localeOptions,
}: {
    user: EditableUser;
    roleOptions: RoleOption[];
    localeOptions: Option[];
}) {
    const [locale, setLocale] = useState(user.locale);
    const localeLabel =
        localeOptions.find((option) => option.value === locale)?.label ??
        locale;

    return (
        <>
            <Head title={`${user.name} bewerken`} />
            <AdminResourcePageShell user={user}>
                <Form
                    {...update.form(user.id)}
                    options={{ preserveScroll: true }}
                    className="grid gap-0"
                >
                    {({ errors, isDirty, processing, recentlySuccessful }) => (
                        <>
                            <AdminFormNavigationGuard isDirty={isDirty} />
                            <AdminFormActions
                                context={user.name}
                                isDirty={isDirty}
                                processing={processing}
                                recentlySuccessful={recentlySuccessful}
                            >
                                <Button asChild type="button" variant="outline">
                                    <Link href={index()}>Annuleren</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    <Save />{' '}
                                    {processing
                                        ? 'Opslaan…'
                                        : 'Wijzigingen opslaan'}
                                </Button>
                            </AdminFormActions>

                            <AdminFormLayout
                                aside={<UserAside user={user} />}
                                asideFirstOnSmallScreens={false}
                                asideLayoutClassName="@min-[48rem]/admin-page:grid-cols-[minmax(0,1fr)_19rem]"
                            >
                                <AdminFormErrorSummary errors={errors} />

                                <AdminFormSection
                                    id="profile"
                                    icon={UserRound}
                                    title="Profiel en voorkeuren"
                                    description="Beheer de herkenbare accountgegevens en persoonlijke taalvoorkeur."
                                >
                                    <div className="grid items-start gap-5 sm:grid-cols-2">
                                        <FormField
                                            id="name"
                                            label="Naam"
                                            error={errors.name}
                                        >
                                            <Input
                                                id="name"
                                                name="name"
                                                defaultValue={user.name}
                                                required
                                                maxLength={255}
                                                autoFocus
                                            />
                                        </FormField>
                                        <FormField
                                            id="email"
                                            label="E-mailadres"
                                            error={errors.email}
                                            hint="Bij een gewijzigd adres vervalt de huidige e-mailbevestiging."
                                        >
                                            <Input
                                                id="email"
                                                name="email"
                                                type="email"
                                                defaultValue={user.email}
                                                required
                                                maxLength={255}
                                            />
                                        </FormField>
                                        <FormField
                                            id="locale"
                                            label="Voorkeurstaal"
                                            error={errors.locale}
                                            hint="Wordt gebruikt voor beschikbare persoonlijke interface-inhoud."
                                        >
                                            <input
                                                data-testid="locale-value"
                                                type="hidden"
                                                name="locale"
                                                value={locale}
                                            />
                                            <Select
                                                value={locale}
                                                onValueChange={setLocale}
                                                required
                                            >
                                                <SelectTrigger
                                                    id="locale"
                                                    aria-labelledby="locale-label"
                                                    aria-invalid={Boolean(
                                                        errors.locale,
                                                    )}
                                                    className="w-full"
                                                >
                                                    <SelectValue>
                                                        {localeLabel}
                                                    </SelectValue>
                                                </SelectTrigger>
                                                <SelectContent align="start">
                                                    {localeOptions.map(
                                                        (option) => (
                                                            <SelectItem
                                                                key={
                                                                    option.value
                                                                }
                                                                data-testid={`locale-option-${option.value}`}
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </FormField>
                                    </div>
                                </AdminFormSection>

                                <AdminFormSection
                                    id="access"
                                    icon={ShieldCheck}
                                    title="Beheerrollen"
                                    description="Rollen bepalen welke beheerfuncties beschikbaar zijn. Accounttoegang beheer je afzonderlijk."
                                >
                                    {user.isSoleActiveAdmin && (
                                        <Alert className="border-amber-200 bg-amber-50/70 dark:border-amber-500/25 dark:bg-amber-500/8">
                                            <ShieldCheck />
                                            <AlertTitle>
                                                Laatste actieve beheerder
                                            </AlertTitle>
                                            <AlertDescription>
                                                Maak eerst een andere actieve
                                                beheerder aan voordat je deze
                                                beheerrol verwijdert.
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                    <fieldset className="grid gap-3">
                                        <legend className="sr-only">
                                            Beschikbare beheerrollen
                                        </legend>
                                        {roleOptions.map((role) => {
                                            const locked =
                                                user.isSoleActiveAdmin &&
                                                role.value === 'admin';

                                            return (
                                                <label
                                                    key={role.value}
                                                    className="flex cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50/40 p-4 transition-colors hover:bg-neutral-50 has-[[data-disabled]]:cursor-not-allowed dark:border-neutral-800 dark:bg-neutral-900/20 dark:hover:bg-neutral-900/50"
                                                >
                                                    {locked && (
                                                        <input
                                                            type="hidden"
                                                            name="roles[]"
                                                            value={role.value}
                                                        />
                                                    )}
                                                    <Checkbox
                                                        name={
                                                            locked
                                                                ? undefined
                                                                : 'roles[]'
                                                        }
                                                        value={role.value}
                                                        defaultChecked={user.roles.includes(
                                                            role.value,
                                                        )}
                                                        disabled={locked}
                                                        aria-invalid={Boolean(
                                                            errors.roles,
                                                        )}
                                                        className="mt-0.5"
                                                    />
                                                    <span>
                                                        <span className="block text-sm font-medium text-neutral-950 dark:text-white">
                                                            {role.label}
                                                        </span>
                                                        <span className="mt-0.5 block text-xs leading-5 text-neutral-500">
                                                            {role.description}
                                                        </span>
                                                    </span>
                                                </label>
                                            );
                                        })}
                                        <InputError message={errors.roles} />
                                    </fieldset>
                                </AdminFormSection>

                                <AdminFormSection
                                    id="account-management"
                                    icon={Ban}
                                    title="Accountbeheer"
                                    description="Blokkeer de toegang tijdelijk of verwijder een geblokkeerd account definitief."
                                >
                                    <AccountManagement
                                        user={user}
                                        disabled={isDirty}
                                    />
                                </AdminFormSection>
                            </AdminFormLayout>
                        </>
                    )}
                </Form>
            </AdminResourcePageShell>
        </>
    );
}

function AdminResourcePageShell({
    user,
    children,
}: {
    user: EditableUser;
    children: ReactNode;
}) {
    return (
        <AdminResourcePage
            contentClassName="@container/admin-page"
            eyebrow="Gebruikersbeheer"
            title={user.name}
            description="Werk profiel, rollen en accounttoegang zorgvuldig bij."
            variant="form"
        >
            {children}
        </AdminResourcePage>
    );
}

function UserAside({ user }: { user: EditableUser }) {
    const initials = user.name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');

    return (
        <div className="overflow-clip rounded-2xl border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-950">
            <div className="border-b border-neutral-200 bg-neutral-50/60 p-5 dark:border-neutral-800 dark:bg-neutral-900/30">
                <div className="flex items-start gap-3">
                    <span
                        aria-hidden="true"
                        className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-signal-50 text-sm font-semibold text-signal-700 ring-1 ring-signal-100 dark:bg-signal-500/10 dark:text-signal-300 dark:ring-signal-500/20"
                    >
                        {initials}
                    </span>
                    <div className="min-w-0 flex-1">
                        <h2 className="truncate text-sm font-semibold text-neutral-950 dark:text-white">
                            {user.name}
                        </h2>
                        <p className="mt-0.5 truncate text-xs text-neutral-500 dark:text-neutral-400">
                            {user.email}
                        </p>
                    </div>
                    <AdminStatusBadge
                        className="mt-0.5"
                        status={user.isActive ? 'active' : 'inactive'}
                    />
                </div>
            </div>
            <dl className="divide-y divide-neutral-100 text-sm dark:divide-neutral-800">
                <div className="px-5 py-4">
                    <dt className="flex items-center gap-2 text-xs text-neutral-500">
                        <BadgeCheck className="size-3.5" /> E-mailstatus
                    </dt>
                    <dd className="mt-1 font-medium text-neutral-950 dark:text-white">
                        {user.emailVerifiedAt ? 'Bevestigd' : 'Niet bevestigd'}
                    </dd>
                </div>
                <div className="px-5 py-4">
                    <dt className="flex items-center gap-2 text-xs text-neutral-500">
                        <Clock3 className="size-3.5" /> Laatste sessie
                    </dt>
                    <dd className="mt-1 font-medium text-neutral-950 dark:text-white">
                        {user.lastActiveAt
                            ? dateFormatter.format(new Date(user.lastActiveAt))
                            : 'Geen sessie gevonden'}
                    </dd>
                </div>
                <div className="px-5 py-4">
                    <dt className="text-xs text-neutral-500">Account sinds</dt>
                    <dd className="mt-1 font-medium text-neutral-950 dark:text-white">
                        {dateFormatter.format(new Date(user.createdAt))}
                    </dd>
                </div>
                <div className="flex flex-wrap gap-1.5 px-5 py-4">
                    {user.roles.length > 0 ? (
                        user.roles.map((role) => (
                            <Badge key={role} variant="secondary">
                                {role === 'admin' ? 'Beheerder' : 'Redacteur'}
                            </Badge>
                        ))
                    ) : (
                        <span className="text-xs text-neutral-500">
                            Geen beheerrol
                        </span>
                    )}
                </div>
            </dl>
        </div>
    );
}

function AccountManagement({
    disabled,
    user,
}: {
    disabled: boolean;
    user: EditableUser;
}) {
    const unavailableReason = user.isCurrentUser
        ? 'Je kunt je eigen account hier niet blokkeren of verwijderen.'
        : user.isSoleActiveAdmin
          ? 'Wijs eerst een andere actieve beheerder aan.'
          : null;

    return (
        <div className="grid gap-3" data-testid="account-management">
            {disabled && (
                <p
                    data-testid="account-action-warning"
                    className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-300"
                >
                    Sla eerst je profielwijzigingen op voordat je een
                    accountactie uitvoert.
                </p>
            )}

            <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
                <section className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                            <h3 className="text-sm font-semibold text-neutral-950 dark:text-white">
                                Aanmeldtoegang
                            </h3>
                            <AdminStatusBadge
                                status={user.isActive ? 'active' : 'inactive'}
                            />
                        </div>
                        <p className="mt-1 max-w-2xl text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            {user.isActive
                                ? 'Blokkeren beëindigt alle sessies en voorkomt nieuwe aanmeldingen.'
                                : 'Deblokkeren geeft deze gebruiker direct weer toegang.'}
                        </p>
                        {unavailableReason && (
                            <p className="mt-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                                {unavailableReason}
                            </p>
                        )}
                    </div>

                    {user.isActive && user.capabilities.block && (
                        <AdminConfirmationDialog
                            form={block.form(user.id)}
                            intent="block"
                            subject={user.name}
                            trigger={
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    disabled={disabled}
                                    className="text-destructive hover:text-destructive"
                                >
                                    <Ban />
                                    Account blokkeren
                                </Button>
                            }
                        />
                    )}

                    {!user.isActive && user.capabilities.unblock && (
                        <AdminConfirmationDialog
                            form={unblock.form(user.id)}
                            intent="unblock"
                            subject={user.name}
                            trigger={
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    disabled={disabled}
                                >
                                    <RotateCcw />
                                    Deblokkeren
                                </Button>
                            }
                        />
                    )}
                </section>

                <section className="flex flex-col gap-4 border-t border-destructive/15 bg-destructive/[0.025] p-4 sm:flex-row sm:items-center sm:justify-between dark:bg-destructive/[0.045]">
                    <div className="min-w-0">
                        <h3 className="text-sm font-semibold text-destructive">
                            Account definitief verwijderen
                        </h3>
                        <p className="mt-1 max-w-2xl text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            Persoonsgegevens, rollen, passkeys en 2FA-gegevens
                            worden verwijderd. Geschreven content blijft zonder
                            auteur bewaard.
                        </p>
                    </div>

                    {user.capabilities.delete ? (
                        <AdminConfirmationDialog
                            form={destroy.form(user.id)}
                            intent="delete"
                            subject={user.name}
                            trigger={
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="sm"
                                    disabled={disabled}
                                >
                                    <Trash2 />
                                    Gebruiker verwijderen
                                </Button>
                            }
                        />
                    ) : (
                        !user.isCurrentUser && (
                            <Badge
                                variant="outline"
                                className="text-neutral-500"
                            >
                                {user.isActive
                                    ? 'Blokkeer eerst'
                                    : 'Niet beschikbaar'}
                            </Badge>
                        )
                    )}
                </section>
            </div>
        </div>
    );
}

function FormField({
    id,
    label,
    error,
    hint,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label id={`${id}-label`} htmlFor={id}>
                {label}
            </Label>
            {children}
            {hint && !error && (
                <p className="text-xs leading-5 text-neutral-500">{hint}</p>
            )}
            <InputError message={error} />
        </div>
    );
}

EditUser.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Gebruikers', href: index() },
        { title: 'Gebruiker bewerken', href: index() },
    ],
};
