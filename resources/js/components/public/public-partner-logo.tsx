import PublicExternalLink from '@/components/public/public-external-link';
import { cn } from '@/lib/utils';
import type { PublicPartner } from '@/types';

type Props = {
    className?: string;
    imageClassName?: string;
    partner: PublicPartner;
};

export default function PublicPartnerLogo({
    className,
    imageClassName,
    partner,
}: Props) {
    return (
        <PublicExternalLink
            href={partner.websiteUrl}
            aria-label={`Bekijk website van ${partner.name}`}
            showIcon={false}
            className={cn(
                'inline-flex w-full items-center rounded-sm opacity-80 transition-opacity hover:opacity-100 motion-reduce:transition-none',
                className,
            )}
        >
            <img
                src={partner.logoSrc}
                alt={partner.logoAlt}
                loading="lazy"
                className={cn(
                    'max-h-14 max-w-full object-contain',
                    imageClassName,
                )}
            />
        </PublicExternalLink>
    );
}
