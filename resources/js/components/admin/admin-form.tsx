import { AlertCircleIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { cn } from '@/lib/utils';

export function AdminFormLayout({
    aside,
    children,
}: {
    aside?: ReactNode;
    children: ReactNode;
}) {
    return (
        <div
            className={cn(
                'grid gap-6',
                aside && 'xl:grid-cols-[minmax(0,1fr)_20rem]',
            )}
        >
            <div className="grid min-w-0 gap-6">{children}</div>
            {aside && <aside className="min-w-0">{aside}</aside>}
        </div>
    );
}

export function AdminFormSection({
    children,
    description,
    title,
}: {
    children: ReactNode;
    description?: string;
    title: string;
}) {
    return (
        <section className="rounded-lg border border-sidebar-border/70 bg-white shadow-xs dark:border-sidebar-border dark:bg-neutral-950">
            <div className="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
                <h2 className="font-semibold text-neutral-950 dark:text-white">
                    {title}
                </h2>
                {description && (
                    <p className="mt-1 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                        {description}
                    </p>
                )}
            </div>
            <div className="grid gap-5 p-5">{children}</div>
        </section>
    );
}

export function AdminFormActions({
    children,
    destructiveActions,
}: {
    children: ReactNode;
    destructiveActions?: ReactNode;
}) {
    return (
        <div className="sticky bottom-4 z-10 flex flex-col gap-3 rounded-lg border border-sidebar-border/70 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border dark:bg-neutral-950/95">
            <div>{destructiveActions}</div>
            <div className="flex flex-wrap items-center justify-end gap-2">
                {children}
            </div>
        </div>
    );
}

export function AdminFormErrorSummary({
    errors,
}: {
    errors: Record<string, string>;
}) {
    const messages = Array.from(new Set(Object.values(errors)));

    if (messages.length === 0) {
        return null;
    }

    return (
        <Alert variant="destructive">
            <AlertCircleIcon />
            <AlertTitle>Controleer de gemarkeerde velden</AlertTitle>
            <AlertDescription>
                <ul className="list-inside list-disc text-sm">
                    {messages.map((message) => (
                        <li key={message}>{message}</li>
                    ))}
                </ul>
            </AlertDescription>
        </Alert>
    );
}
