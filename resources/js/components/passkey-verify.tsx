import type { UrlMethodPair } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { usePasskeyVerify } from '@laravel/passkeys/react';
import { KeyRound } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    routes?: {
        options: UrlMethodPair;
        submit: UrlMethodPair;
    };
    label?: string;
    loadingLabel?: string;
    separator?: string;
};

export default function PasskeyVerify({
    routes,
    label,
    loadingLabel,
    separator,
}: Props = {}) {
    const { verify, isLoading, error, isSupported } = usePasskeyVerify({
        ...(routes && {
            routes: {
                options: routes.options.url,
                submit: routes.submit.url,
            },
        }),
        onSuccess: (response) => {
            router.visit(response.redirect ?? '/dashboard');
        },
    });

    if (!isSupported) {
        return null;
    }

    return (
        <>
            <div className="grid gap-2">
                <Button
                    type="button"
                    variant="outline"
                    className="h-12 w-full rounded-xl border-night-200 bg-white font-semibold text-night-800 shadow-none hover:border-signal-300 hover:bg-signal-50/70 hover:text-night-950 dark:border-white/12 dark:bg-night-900/65 dark:text-white dark:hover:border-signal-400/45 dark:hover:bg-signal-500/10 dark:hover:text-white"
                    onClick={verify}
                    disabled={isLoading}
                >
                    {isLoading ? <Spinner /> : <KeyRound className="h-4 w-4" />}
                    {isLoading
                        ? (loadingLabel ?? 'Authenticating...')
                        : (label ?? 'Sign in with a passkey')}
                </Button>
                {error && (
                    <InputError message={error} className="text-center" />
                )}
            </div>

            <div className="relative my-7">
                <div className="absolute inset-0 flex items-center">
                    <Separator className="w-full bg-night-200/80 dark:bg-white/10" />
                </div>
                <div className="relative flex justify-center text-xs uppercase">
                    <span className="bg-white px-3 text-[0.65rem] font-semibold tracking-[0.08em] text-night-600 max-sm:bg-paper dark:bg-night-800 dark:text-night-400 max-sm:dark:bg-night-900">
                        {separator ?? 'Or continue with email'}
                    </span>
                </div>
            </div>
        </>
    );
}
