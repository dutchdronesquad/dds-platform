import { Link, usePage } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogo from '@/components/app-logo';
import { cn } from '@/lib/utils';
import { about, contact, dashboard, home, login } from '@/routes';
import { index as eventsIndex } from '@/routes/events';
import { index as newsIndex } from '@/routes/news';
import { index as projectsIndex } from '@/routes/projects';

type PublicNavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    activePath: string;
};

const publicNavItems: PublicNavItem[] = [
    { title: 'Events', href: eventsIndex(), activePath: '/events' },
    { title: 'Projects', href: projectsIndex(), activePath: '/projects' },
    { title: 'News', href: newsIndex(), activePath: '/news' },
    { title: 'About', href: about(), activePath: '/about' },
    { title: 'Contact', href: contact(), activePath: '/contact' },
];

type Props = {
    children: ReactNode;
};

export default function PublicLayout({ children }: Props) {
    const { props, url } = usePage();
    const { auth } = props;
    const currentPath = url.split('?')[0];

    return (
        <div className="min-h-screen bg-stone-50 text-neutral-950 dark:bg-neutral-950 dark:text-neutral-50">
            <header className="border-b border-neutral-200 bg-white/95 dark:border-neutral-800 dark:bg-neutral-950/95">
                <div className="mx-auto flex min-h-16 w-full max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
                    <Link
                        href={home()}
                        prefetch
                        className="flex min-w-0 items-center rounded-md focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 focus-visible:outline-none dark:focus-visible:ring-red-400 dark:focus-visible:ring-offset-neutral-950"
                    >
                        <AppLogo />
                    </Link>

                    <nav
                        aria-label="Hoofdnavigatie"
                        className="hidden items-center gap-1 lg:flex"
                    >
                        {publicNavItems.map((item) => (
                            <Link
                                key={item.title}
                                href={item.href}
                                prefetch
                                className={cn(
                                    'rounded-md px-3 py-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-100 hover:text-neutral-950 focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 focus-visible:outline-none dark:text-neutral-300 dark:hover:bg-neutral-900 dark:hover:text-white dark:focus-visible:ring-red-400 dark:focus-visible:ring-offset-neutral-950',
                                    currentPath.startsWith(item.activePath) &&
                                        'bg-neutral-100 text-neutral-950 dark:bg-neutral-900 dark:text-white',
                                )}
                            >
                                {item.title}
                            </Link>
                        ))}
                    </nav>

                    <div className="ml-auto flex items-center gap-2">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="rounded-md border border-neutral-300 px-3 py-2 text-sm font-semibold text-neutral-900 transition-colors hover:border-neutral-950 hover:bg-neutral-950 hover:text-white focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 focus-visible:outline-none dark:border-neutral-700 dark:text-neutral-100 dark:hover:border-white dark:hover:bg-white dark:hover:text-neutral-950 dark:focus-visible:ring-red-400 dark:focus-visible:ring-offset-neutral-950"
                            >
                                Beheer
                            </Link>
                        ) : (
                            <Link
                                href={login()}
                                className="rounded-md border border-neutral-300 px-3 py-2 text-sm font-semibold text-neutral-900 transition-colors hover:border-neutral-950 hover:bg-neutral-950 hover:text-white focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 focus-visible:outline-none dark:border-neutral-700 dark:text-neutral-100 dark:hover:border-white dark:hover:bg-white dark:hover:text-neutral-950 dark:focus-visible:ring-red-400 dark:focus-visible:ring-offset-neutral-950"
                            >
                                Inloggen
                            </Link>
                        )}
                    </div>
                </div>

                <nav
                    aria-label="Mobiele hoofdnavigatie"
                    className="flex gap-1 overflow-x-auto border-t border-neutral-200 px-4 py-2 lg:hidden dark:border-neutral-800"
                >
                    {publicNavItems.map((item) => (
                        <Link
                            key={item.title}
                            href={item.href}
                            prefetch
                            className={cn(
                                'shrink-0 rounded-md px-3 py-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-100 hover:text-neutral-950 focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:outline-none dark:text-neutral-300 dark:hover:bg-neutral-900 dark:hover:text-white dark:focus-visible:ring-red-400',
                                currentPath.startsWith(item.activePath) &&
                                    'bg-neutral-100 text-neutral-950 dark:bg-neutral-900 dark:text-white',
                            )}
                        >
                            {item.title}
                        </Link>
                    ))}
                </nav>
            </header>

            <main>{children}</main>

            <footer className="border-t border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-950">
                <div className="mx-auto grid w-full max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1.2fr_1fr_1fr] lg:px-8">
                    <div className="flex flex-col gap-4">
                        <AppLogo />
                        <p className="max-w-md text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                            Dutch Drone Squad bouwt aan een toegankelijke
                            community voor dronevliegers, makers en partners in
                            Nederland.
                        </p>
                    </div>

                    <div>
                        <h2 className="text-sm font-semibold text-neutral-950 dark:text-white">
                            Platform
                        </h2>
                        <div className="mt-3 flex flex-col gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                            <Link
                                href={eventsIndex()}
                                prefetch
                                className="hover:text-neutral-950 dark:hover:text-white"
                            >
                                Events
                            </Link>
                            <Link
                                href={projectsIndex()}
                                prefetch
                                className="hover:text-neutral-950 dark:hover:text-white"
                            >
                                Projects
                            </Link>
                            <Link
                                href={newsIndex()}
                                prefetch
                                className="hover:text-neutral-950 dark:hover:text-white"
                            >
                                News
                            </Link>
                        </div>
                    </div>

                    <div>
                        <h2 className="text-sm font-semibold text-neutral-950 dark:text-white">
                            Community
                        </h2>
                        <div className="mt-3 flex flex-col gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                            <Link
                                href={contact()}
                                prefetch
                                className="hover:text-neutral-950 dark:hover:text-white"
                            >
                                Contact
                            </Link>
                            <Link
                                href={about()}
                                prefetch
                                className="hover:text-neutral-950 dark:hover:text-white"
                            >
                                About
                            </Link>
                            <Link
                                href={home()}
                                prefetch
                                className="hover:text-neutral-950 dark:hover:text-white"
                            >
                                Home
                            </Link>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}
