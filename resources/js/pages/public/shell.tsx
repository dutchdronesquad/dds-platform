import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Compass, RadioTower } from 'lucide-react';

type PublicPageSection = {
    heading: string;
    body: string;
};

type PublicPage = {
    title: string;
    eyebrow: string;
    description: string;
    primaryAction: {
        label: string;
        href: string;
    };
    sections: PublicPageSection[];
};

type Props = {
    page: PublicPage;
};

export default function PublicShell({ page }: Props) {
    return (
        <>
            <Head title={page.title} />

            <section className="border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-950">
                <div className="mx-auto grid w-full max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8 lg:py-16">
                    <div className="max-w-3xl">
                        <p className="text-sm font-semibold tracking-wide text-red-600 uppercase dark:text-red-400">
                            {page.eyebrow}
                        </p>
                        <h1 className="mt-4 text-4xl font-semibold text-neutral-950 sm:text-5xl dark:text-white">
                            {page.title}
                        </h1>
                        <p className="mt-5 max-w-2xl text-base leading-7 text-neutral-600 sm:text-lg dark:text-neutral-400">
                            {page.description}
                        </p>
                        <div className="mt-8">
                            <Link
                                href={page.primaryAction.href}
                                className="inline-flex items-center justify-center gap-2 rounded-md bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700 focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 focus-visible:outline-none dark:bg-red-500 dark:text-neutral-950 dark:hover:bg-red-400 dark:focus-visible:ring-red-400 dark:focus-visible:ring-offset-neutral-950"
                            >
                                {page.primaryAction.label}
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </div>

                    <div className="grid gap-4">
                        {page.sections.map((section, index) => (
                            <article
                                key={section.heading}
                                className="rounded-lg border border-neutral-200 bg-neutral-50 p-5 dark:border-neutral-800 dark:bg-neutral-900"
                            >
                                <div className="flex items-start gap-4">
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-md bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                        {index === 0 ? (
                                            <Compass className="size-5" />
                                        ) : (
                                            <RadioTower className="size-5" />
                                        )}
                                    </span>
                                    <div>
                                        <h2 className="text-lg font-semibold text-neutral-950 dark:text-white">
                                            {section.heading}
                                        </h2>
                                        <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                            {section.body}
                                        </p>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                </div>
            </section>
        </>
    );
}
