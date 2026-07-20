import { cn } from '@/lib/utils';

type Props = {
    className?: string;
    compact?: boolean;
    inverse?: boolean;
    logoOnly?: boolean;
};

export default function DdsBrand({
    className,
    compact = false,
    inverse = false,
    logoOnly = false,
}: Props) {
    const variant = compact ? 'compact' : logoOnly ? 'logo' : 'full';

    return (
        <span
            className={cn(
                'flex min-w-0 items-center',
                compact || logoOnly ? 'justify-center' : 'gap-3',
                className,
            )}
            data-brand-variant={variant}
        >
            <span
                className={cn(
                    'flex shrink-0 items-center justify-center',
                    compact ? 'size-8' : 'h-11 w-24 px-1',
                )}
            >
                <img
                    src="/brand/dds-logo.svg"
                    alt=""
                    aria-hidden="true"
                    width="888"
                    height="343"
                    className="h-auto max-h-full w-full object-contain"
                    data-testid="dds-brand-logo"
                />
            </span>

            {!compact && !logoOnly && (
                <span className="grid min-w-0 leading-none">
                    <span
                        className={cn(
                            'truncate text-sm font-semibold tracking-[-0.01em]',
                            inverse
                                ? 'text-white'
                                : 'text-night-950 dark:text-white',
                        )}
                    >
                        Dutch Drone Squad
                    </span>
                    <span
                        className={cn(
                            'mt-1 truncate text-[0.68rem] font-medium tracking-[0.08em]',
                            inverse
                                ? 'text-white/55'
                                : 'text-night-500 dark:text-night-400',
                        )}
                    >
                        FPV racing community
                    </span>
                </span>
            )}
        </span>
    );
}
