import { ArrowUpRight } from 'lucide-react';
import type { AnchorHTMLAttributes, ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = Omit<AnchorHTMLAttributes<HTMLAnchorElement>, 'children'> & {
    children: ReactNode;
    showIcon?: boolean;
};

export default function PublicExternalLink({
    children,
    className,
    showIcon = true,
    ...props
}: Props) {
    return (
        <a
            {...props}
            target="_blank"
            rel="noopener noreferrer"
            className={cn(
                'group inline-flex items-center gap-2 rounded-sm focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:ring-offset-3 focus-visible:outline-none dark:focus-visible:ring-signal-400 dark:focus-visible:ring-offset-night-950',
                className,
            )}
        >
            {children}
            {showIcon && (
                <ArrowUpRight
                    aria-hidden="true"
                    className="size-4 shrink-0 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none"
                />
            )}
            <span className="sr-only"> (opent in een nieuw tabblad)</span>
        </a>
    );
}
