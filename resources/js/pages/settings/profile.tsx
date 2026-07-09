import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
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
import localeRoute from '@/routes/locale';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { Auth, LocaleProps } from '@/types';

type PageProps = {
    auth: Auth;
    locale: LocaleProps;
};

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth, locale } = usePage<PageProps>().props;
    const pageLocale = auth.user.locale ?? locale.active;
    const [optimisticLocale, setOptimisticLocale] = useState<string | null>(
        null,
    );
    const [isSavingLocale, setIsSavingLocale] = useState(false);
    const selectedLocale = optimisticLocale ?? pageLocale;
    const supportedLocales = Object.entries(locale.supported);

    const handleLocaleChange = (newLocale: string) => {
        if (newLocale === selectedLocale) {
            return;
        }

        setOptimisticLocale(newLocale);

        router.patch(
            localeRoute.update.url(),
            { locale: newLocale },
            {
                preserveScroll: true,
                onStart: () => setIsSavingLocale(true),
                onSuccess: () => setOptimisticLocale(null),
                onError: () => setOptimisticLocale(null),
                onFinish: () => setIsSavingLocale(false),
            },
        );
    };

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name and email address"
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Full name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Email address"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to re-send the
                                                verification email.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been
                                                sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <div className="mt-10 space-y-6">
                <Heading
                    variant="small"
                    title="Language"
                    description="Choose the language used for the DDS interface"
                />

                <div className="grid gap-2">
                    <Label htmlFor="locale">Language</Label>

                    <Select
                        value={selectedLocale}
                        onValueChange={handleLocaleChange}
                        disabled={isSavingLocale}
                    >
                        <SelectTrigger
                            id="locale"
                            className="w-full sm:w-64"
                            aria-busy={isSavingLocale}
                        >
                            <SelectValue placeholder="Select language" />
                        </SelectTrigger>
                        <SelectContent>
                            {supportedLocales.map(([code, supportedLocale]) => (
                                <SelectItem key={code} value={code}>
                                    {supportedLocale.native_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
