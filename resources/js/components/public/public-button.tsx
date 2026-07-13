import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    children: ReactNode;
    className?: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: ReactNode;
    inverse?: boolean;
    variant?: 'primary' | 'secondary' | 'text';
};

export default function PublicButton({
    children,
    className,
    href,
    icon,
    inverse = false,
    variant = 'primary',
}: Props) {
    return (
        <Link
            href={href}
            prefetch
            className={cn(
                'group inline-flex min-h-11 items-center justify-center gap-2 rounded-[0.25rem] px-5 py-3 text-sm font-semibold transition duration-200 focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:ring-offset-3 focus-visible:outline-none motion-reduce:transition-none',
                variant === 'primary' &&
                    'bg-flight-500 text-ink shadow-sm hover:bg-flight-400 active:bg-flight-600',
                variant === 'secondary' &&
                    (inverse
                        ? 'border border-white/25 bg-white/5 text-white hover:border-signal-400 hover:bg-white/10'
                        : 'border border-paddock-rule bg-transparent text-ink hover:border-ink hover:bg-paper dark:border-white/20 dark:text-white dark:hover:border-white dark:hover:bg-white/5'),
                variant === 'text' &&
                    (inverse
                        ? 'px-0 text-white hover:text-signal-300'
                        : 'px-0 text-ink hover:text-flight-700 dark:text-white dark:hover:text-flight-300'),
                inverse
                    ? 'focus-visible:ring-offset-night-950'
                    : 'focus-visible:ring-offset-white dark:focus-visible:ring-offset-night-950',
                className,
            )}
        >
            {children}
            {icon ?? (
                <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none" />
            )}
        </Link>
    );
}
