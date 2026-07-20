import { Link } from '@inertiajs/react';
import DdsBrand from '@/components/dds-brand';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div
            className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
            data-auth-layout="simple"
        >
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            aria-label="Dutch Drone Squad home"
                            className="rounded-sm focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:ring-offset-3 focus-visible:outline-none"
                        >
                            <DdsBrand logoOnly />
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">{title}</h1>
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>

                    {children}
                </div>
            </div>
        </div>
    );
}
