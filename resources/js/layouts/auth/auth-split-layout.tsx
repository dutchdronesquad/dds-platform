import { Link, usePage } from '@inertiajs/react';
import { KeyRound, MailCheck, ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import DdsBrand from '@/components/dds-brand';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

const authPhotos = [
    {
        src: '/images/dds/racing/pilot-preparing-drone.jpg',
        position: 'object-center',
    },
    {
        src: '/images/dds/racing/homepage-hero.jpg',
        position: 'object-[52%_center]',
    },
] as const;

function AuthPhotoRotation({ rotationInterval }: { rotationInterval: number }) {
    const [rotationStep, setRotationStep] = useState(0);
    const activePhotoIndex = rotationStep % authPhotos.length;

    useEffect(() => {
        const reducedMotionQuery = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        );
        let rotationTimer: number | undefined;

        const updateRotation = () => {
            if (rotationTimer !== undefined) {
                window.clearInterval(rotationTimer);
            }

            if (reducedMotionQuery.matches) {
                setRotationStep(0);

                return;
            }

            rotationTimer = window.setInterval(() => {
                setRotationStep((currentStep) => currentStep + 1);
            }, rotationInterval);
        };

        updateRotation();
        reducedMotionQuery.addEventListener('change', updateRotation);

        return () => {
            reducedMotionQuery.removeEventListener('change', updateRotation);

            if (rotationTimer !== undefined) {
                window.clearInterval(rotationTimer);
            }
        };
    }, [rotationInterval]);

    return (
        <div
            className="absolute inset-0"
            data-testid="auth-photo-rotation"
            data-active-photo={authPhotos[activePhotoIndex].src}
            data-rotation-count={rotationStep}
            data-rotation-interval={rotationInterval}
        >
            {authPhotos.map((photo, photoIndex) => (
                <img
                    key={photo.src}
                    src={photo.src}
                    alt=""
                    data-auth-photo
                    data-active={photoIndex === activePhotoIndex}
                    className={`absolute inset-0 size-full object-cover transition-opacity duration-1000 ease-in-out motion-reduce:transition-none ${photo.position} ${photoIndex === activePhotoIndex ? 'opacity-100' : 'opacity-0'}`}
                />
            ))}
        </div>
    );
}

function AuthContext({ title }: { title?: string }) {
    if (title?.toLowerCase().includes('email')) {
        return (
            <>
                <MailCheck className="size-4" />
                Secure email check
            </>
        );
    }

    if (title?.toLowerCase().includes('password')) {
        return (
            <>
                <KeyRound className="size-4" />
                Protected account step
            </>
        );
    }

    return (
        <>
            <ShieldCheck className="size-4" />
            Secure member access
        </>
    );
}

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { ui } = usePage().props;

    return (
        <div
            className="relative min-h-svh overflow-x-hidden bg-paper text-night-950 lg:grid lg:grid-cols-[minmax(0,1fr)_clamp(28rem,36vw,32rem)] dark:bg-night-900 dark:text-white"
            data-testid="auth-layout"
            data-auth-layout="split"
        >
            <aside
                className="relative hidden min-h-svh overflow-hidden lg:flex lg:flex-col lg:justify-between"
                data-testid="auth-visual"
                aria-hidden="true"
            >
                <AuthPhotoRotation
                    rotationInterval={ui.authPhotoRotationInterval}
                />
                <div className="absolute inset-0 bg-linear-to-t from-night-950/95 via-night-950/50 to-night-950/20" />
                <div className="absolute inset-0 bg-linear-to-r from-night-950/10 via-transparent to-night-950/32" />

                <div className="relative z-10 px-10 py-9 xl:px-14 xl:py-11">
                    <span>
                        <DdsBrand inverse />
                    </span>
                </div>

                <div className="relative z-10 max-w-2xl px-10 pb-12 xl:px-14 xl:pb-16">
                    <div className="mb-6 h-px w-24 bg-linear-to-r from-dds-orange via-dds-cyan to-transparent" />
                    <p className="text-xs font-semibold tracking-[0.2em] text-signal-300 uppercase">
                        DDS member portal
                    </p>
                    <p className="mt-4 max-w-xl font-public-display text-4xl leading-[1.06] font-semibold tracking-[-0.035em] text-white xl:text-5xl">
                        Ready for the next heat.
                    </p>
                    <p className="mt-5 max-w-lg text-base leading-7 text-white/68">
                        Events, accounts and race-day operations in one
                        protected Dutch Drone Squad workspace.
                    </p>
                </div>
            </aside>

            <main
                className="relative flex min-h-svh items-center justify-center border-night-200/70 bg-paper px-5 py-8 sm:px-10 sm:py-12 lg:border-l lg:px-8 xl:px-10 dark:border-white/10 dark:bg-night-900"
                data-testid="auth-form-side"
            >
                <div
                    aria-hidden="true"
                    className="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-signal-300/70 to-transparent lg:hidden dark:via-signal-500/35"
                />
                <div
                    aria-hidden="true"
                    className="absolute -top-20 -right-16 size-48 rounded-full bg-signal-100/70 blur-3xl dark:bg-signal-500/10"
                />

                <div className="relative z-10 flex w-full max-w-[27rem] flex-col items-center gap-7">
                    <Link
                        href={home()}
                        aria-label="Dutch Drone Squad home"
                        className="rounded-sm focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:ring-offset-3 focus-visible:outline-none"
                    >
                        <DdsBrand />
                    </Link>

                    <section
                        className="w-full rounded-2xl border border-night-200/80 bg-white p-6 shadow-[0_24px_70px_-42px_rgb(10_23_32/0.48)] max-sm:border-0 max-sm:bg-transparent max-sm:p-0 max-sm:shadow-none sm:p-8 lg:rounded-none lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none dark:border-white/10 dark:bg-night-800 dark:shadow-black/30 max-sm:dark:bg-transparent lg:dark:bg-transparent"
                        data-testid="auth-panel"
                    >
                        <div
                            className="mb-7 grid gap-3 text-center"
                            data-testid="auth-heading"
                        >
                            <p className="flex items-center justify-center gap-2 text-[0.68rem] font-semibold tracking-[0.16em] text-signal-700 uppercase dark:text-signal-300">
                                <AuthContext title={title} />
                            </p>
                            <h1 className="font-public-display text-2xl leading-tight font-semibold tracking-[-0.025em] text-night-950 sm:text-[1.7rem] dark:text-white">
                                {title}
                            </h1>
                            <p className="mx-auto max-w-sm text-sm leading-6 text-night-500 dark:text-night-400">
                                {description}
                            </p>
                        </div>

                        <div className="dark:[&_a]:hover:text-flight-200 [&_[data-slot=input]]:h-12 [&_[data-slot=input]]:rounded-xl [&_[data-slot=input]]:border-night-200 [&_[data-slot=input]]:bg-paper [&_[data-slot=input]]:px-3.5 [&_[data-slot=input]]:shadow-none [&_[data-slot=input]]:focus-visible:border-dds-blue [&_[data-slot=input]]:focus-visible:ring-dds-cyan/20 dark:[&_[data-slot=input]]:border-white/12 dark:[&_[data-slot=input]]:bg-night-900/65 [&_a]:font-medium [&_a]:text-night-700 [&_a]:decoration-flight-400 [&_a]:decoration-2 [&_a]:hover:text-flight-700 dark:[&_a]:text-flight-300 [&_button[type=submit][data-slot=button]]:h-12 [&_button[type=submit][data-slot=button]]:rounded-xl [&_button[type=submit][data-slot=button]]:bg-dds-orange [&_button[type=submit][data-slot=button]]:font-semibold [&_button[type=submit][data-slot=button]]:text-deep-signal [&_button[type=submit][data-slot=button]]:shadow-xs [&_button[type=submit][data-slot=button]]:hover:bg-flight-400">
                            {children}
                        </div>
                    </section>

                    <p className="flex items-center gap-2 text-center text-xs leading-5 text-night-500 dark:text-night-400">
                        <ShieldCheck className="size-3.5" aria-hidden="true" />
                        Protected access for DDS members and race organizers.
                    </p>
                </div>
            </main>
        </div>
    );
}
