import { CircleCheck } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { cn } from '@/lib/utils';

export default function AuthNotice({
    children,
    className,
}: PropsWithChildren<{ className?: string }>) {
    return (
        <div
            role="status"
            aria-live="polite"
            className={cn(
                'flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-left text-sm leading-6 text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-400/8 dark:text-emerald-200',
                className,
            )}
        >
            <CircleCheck className="mt-1 size-4 shrink-0" aria-hidden="true" />
            <span>{children}</span>
        </div>
    );
}
