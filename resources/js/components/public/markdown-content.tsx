import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    className?: string;
    fallback?: ReactNode;
    html: string | null;
};

export default function MarkdownContent({ className, fallback, html }: Props) {
    const classes = cn(
        'dark:text-night-300 max-w-none text-base leading-8 text-signal-muted sm:text-lg',
        '[&_a]:font-semibold [&_a]:text-dds-blue [&_a]:underline [&_a]:decoration-dds-cyan/50 [&_a]:underline-offset-4 hover:[&_a]:text-deep-signal dark:[&_a]:text-dds-cyan dark:hover:[&_a]:text-white',
        '[&_blockquote]:my-6 [&_blockquote]:border-l-4 [&_blockquote]:border-dds-cyan [&_blockquote]:pl-5 [&_blockquote]:italic',
        '[&_code]:rounded [&_code]:bg-paddock [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-[0.9em] dark:[&_code]:bg-night-800',
        '[&_h1]:mt-10 [&_h1]:font-public-display [&_h1]:text-3xl [&_h1]:font-semibold [&_h1]:tracking-[-0.04em] [&_h1:first-child]:mt-0',
        '[&_h2]:mt-10 [&_h2]:font-public-display [&_h2]:text-2xl [&_h2]:font-semibold [&_h2]:tracking-[-0.03em] [&_h2:first-child]:mt-0',
        '[&_h3]:mt-8 [&_h3]:font-public-display [&_h3]:text-xl [&_h3]:font-semibold [&_h3:first-child]:mt-0',
        '[&_hr]:my-8 [&_hr]:border-paddock-rule dark:[&_hr]:border-white/12',
        '[&_li]:my-1 [&_ol]:my-5 [&_ol]:list-decimal [&_ol]:pl-7 [&_p]:my-5 [&_p:first-child]:mt-0 [&_p:last-child]:mb-0 [&_strong]:font-semibold [&_strong]:text-deep-signal dark:[&_strong]:text-white [&_ul]:my-5 [&_ul]:list-disc [&_ul]:pl-7',
        '[&_pre]:my-6 [&_pre]:overflow-x-auto [&_pre]:rounded-xl [&_pre]:bg-night-900 [&_pre]:p-5 [&_pre]:text-night-100 [&_pre_code]:bg-transparent [&_pre_code]:p-0',
        '[&_table]:my-6 [&_table]:w-full [&_table]:border-collapse [&_td]:border [&_td]:border-paddock-rule [&_td]:p-3 dark:[&_td]:border-white/12 [&_th]:border [&_th]:border-paddock-rule [&_th]:bg-paddock [&_th]:p-3 [&_th]:text-left dark:[&_th]:border-white/12 dark:[&_th]:bg-night-800',
        className,
    );

    if (html === null) {
        return <div className={classes}>{fallback}</div>;
    }

    return (
        <div className={classes} dangerouslySetInnerHTML={{ __html: html }} />
    );
}
