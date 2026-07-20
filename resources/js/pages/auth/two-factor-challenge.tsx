import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { RefreshCw } from 'lucide-react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { store } from '@/routes/two-factor/login';

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [code, setCode] = useState<string>('');

    const authConfigContent = useMemo<{
        title: string;
        description: string;
        toggleText: string;
    }>(() => {
        if (showRecoveryInput) {
            return {
                title: 'Recovery code',
                description:
                    'Please confirm access to your account by entering one of your emergency recovery codes.',
                toggleText: 'an authentication code',
            };
        }

        return {
            title: 'Authentication code',
            description:
                'Enter the authentication code provided by your authenticator application.',
            toggleText: 'a recovery code',
        };
    }, [showRecoveryInput]);

    setLayoutProps({
        title: authConfigContent.title,
        description: authConfigContent.description,
    });

    const toggleRecoveryMode = (clearErrors: () => void): void => {
        setShowRecoveryInput(!showRecoveryInput);
        clearErrors();
        setCode('');
    };

    return (
        <>
            <Head title="Two-factor authentication" />

            <div className="space-y-6">
                <Form
                    {...store.form()}
                    className="space-y-4"
                    resetOnError
                    resetOnSuccess={!showRecoveryInput}
                >
                    {({ errors, processing, clearErrors }) => (
                        <>
                            {showRecoveryInput ? (
                                <div className="grid gap-2">
                                    <Label htmlFor="recovery_code">
                                        Recovery code
                                    </Label>
                                    <Input
                                        id="recovery_code"
                                        name="recovery_code"
                                        type="text"
                                        placeholder="Enter recovery code"
                                        autoFocus={showRecoveryInput}
                                        autoComplete="off"
                                        required
                                        aria-invalid={
                                            errors.recovery_code
                                                ? true
                                                : undefined
                                        }
                                        aria-describedby={
                                            errors.recovery_code
                                                ? 'recovery-code-error'
                                                : undefined
                                        }
                                    />
                                    <InputError
                                        id="recovery-code-error"
                                        message={errors.recovery_code}
                                    />
                                </div>
                            ) : (
                                <div className="flex flex-col items-start justify-center gap-3 text-left">
                                    <Label htmlFor="code">
                                        Six-digit authentication code
                                    </Label>
                                    <div className="flex w-full items-center justify-center">
                                        <InputOTP
                                            id="code"
                                            name="code"
                                            maxLength={OTP_MAX_LENGTH}
                                            value={code}
                                            onChange={(value) => setCode(value)}
                                            disabled={processing}
                                            pattern={REGEXP_ONLY_DIGITS}
                                            inputMode="numeric"
                                            autoComplete="one-time-code"
                                            autoFocus
                                            aria-invalid={
                                                errors.code ? true : undefined
                                            }
                                            aria-describedby={
                                                errors.code
                                                    ? 'code-error'
                                                    : undefined
                                            }
                                        >
                                            <InputOTPGroup className="gap-2">
                                                {Array.from(
                                                    { length: OTP_MAX_LENGTH },
                                                    (_, index) => (
                                                        <InputOTPSlot
                                                            key={index}
                                                            index={index}
                                                            className="h-12 w-11 rounded-xl border-l border-night-200 bg-paper text-base shadow-none first:rounded-xl last:rounded-xl dark:border-white/12 dark:bg-night-900/65"
                                                        />
                                                    ),
                                                )}
                                            </InputOTPGroup>
                                        </InputOTP>
                                    </div>
                                    <InputError
                                        id="code-error"
                                        message={errors.code}
                                        className="w-full text-center"
                                    />
                                </div>
                            )}

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                                data-test="two-factor-submit-button"
                            >
                                {processing && <Spinner />}
                                Continue
                            </Button>

                            <div className="flex justify-center">
                                <button
                                    type="button"
                                    className="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border border-night-200 bg-white px-4 text-sm font-semibold text-night-700 transition-colors hover:border-signal-300 hover:bg-signal-50/70 hover:text-night-950 focus-visible:ring-3 focus-visible:ring-signal-500/25 focus-visible:outline-none dark:border-white/12 dark:bg-night-900/65 dark:text-night-200 dark:hover:border-signal-400/45 dark:hover:bg-signal-500/10 dark:hover:text-white"
                                    onClick={() =>
                                        toggleRecoveryMode(clearErrors)
                                    }
                                    data-test="toggle-recovery-mode"
                                >
                                    <RefreshCw
                                        className="size-3.5"
                                        aria-hidden="true"
                                    />
                                    Switch to {authConfigContent.toggleText}
                                </button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

TwoFactorChallenge.layout = {
    title: 'Authentication code',
    description:
        'Enter the authentication code provided by your authenticator application.',
};
