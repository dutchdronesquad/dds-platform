import { cn } from '@/lib/utils';

type Props = {
    className?: string;
    compact?: boolean;
    inverse?: boolean;
};

export default function PublicBrand({
    className,
    compact = false,
    inverse = false,
}: Props) {
    return (
        <span className={cn('flex min-w-0 items-center gap-3', className)}>
            <span className="flex h-11 w-24 shrink-0 items-center justify-center px-1">
                <img
                    src="/brand/dds-logo.svg"
                    alt=""
                    className="h-auto w-full"
                />
            </span>

            {!compact && (
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
