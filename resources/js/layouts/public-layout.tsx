import { Link, usePage } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { ArrowUpRight, Menu, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import PublicBrand from '@/components/public/public-brand';
import { cn } from '@/lib/utils';
import {
    about,
    contact,
    home,
    house_rules as houseRules,
    partners,
} from '@/routes';
import { index as eventsIndex } from '@/routes/events';
import { index as locationsIndex } from '@/routes/locations';
import { index as newsIndex } from '@/routes/news';
import { index as projectsIndex } from '@/routes/projects';

type PublicNavItem = {
    activePath: string;
    href: NonNullable<InertiaLinkProps['href']>;
    title: string;
};

const headerNavItems: PublicNavItem[] = [
    { title: 'Projecten', href: projectsIndex(), activePath: '/projects' },
    { title: 'Nieuws', href: newsIndex(), activePath: '/news' },
    { title: 'Over DDS', href: about(), activePath: '/about' },
    { title: 'Locaties', href: locationsIndex(), activePath: '/locations' },
    { title: 'Contact', href: contact(), activePath: '/contact' },
];

const mobileNavItems: PublicNavItem[] = [
    { title: 'Projecten', href: projectsIndex(), activePath: '/projects' },
    { title: 'Nieuws', href: newsIndex(), activePath: '/news' },
    { title: 'Over DDS', href: about(), activePath: '/about' },
    { title: 'Locaties', href: locationsIndex(), activePath: '/locations' },
    { title: 'Contact', href: contact(), activePath: '/contact' },
];

const footerExploreItems = [
    { title: 'Agenda', href: eventsIndex() },
    { title: 'Nieuws', href: newsIndex() },
    { title: 'Locaties', href: locationsIndex() },
];

const footerDdsItems = [
    { title: 'Over DDS', href: about() },
    { title: 'Projecten', href: projectsIndex() },
    { title: 'Partners', href: partners() },
];

const footerPracticalItems = [
    { title: 'Huisregels', href: houseRules() },
    { title: 'Contact', href: contact() },
];

type Props = {
    children: ReactNode;
};

export default function PublicLayout({ children }: Props) {
    const { url } = usePage();
    const currentPath = url.split('?')[0];
    const isHome = currentPath === '/';
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isHeaderScrolled, setIsHeaderScrolled] = useState(false);

    useEffect(() => {
        if (!isHome) {
            return;
        }

        const updateHeaderState = () => {
            setIsHeaderScrolled(window.scrollY > 32);
        };

        const animationFrameId =
            window.requestAnimationFrame(updateHeaderState);
        window.addEventListener('scroll', updateHeaderState, { passive: true });

        return () => {
            window.cancelAnimationFrame(animationFrameId);
            window.removeEventListener('scroll', updateHeaderState);
        };
    }, [isHome]);

    return (
        <div className="min-h-screen bg-paper text-ink dark:bg-night-950 dark:text-white">
            <header
                className={cn(
                    'z-50 transition-[background-color,border-color,box-shadow,backdrop-filter] duration-300 motion-reduce:transition-none',
                    isHome
                        ? 'absolute inset-x-0 top-0 border-b border-white/10 bg-linear-to-b from-ink/82 to-ink/38 backdrop-blur-lg lg:sticky lg:-mb-18'
                        : 'sticky top-0 border-b border-paddock-rule bg-paper/96 backdrop-blur-md dark:border-white/10 dark:bg-night-950/94',
                    isHome &&
                        isHeaderScrolled &&
                        'lg:border-white/12 lg:bg-ink/68 lg:bg-none lg:shadow-lg lg:shadow-ink/10 lg:backdrop-blur-xl',
                )}
            >
                <div className="mx-auto flex min-h-18 w-full max-w-7xl items-center gap-5 px-public-gutter">
                    <Link
                        href={home()}
                        prefetch
                        aria-label="Dutch Drone Squad home"
                        onClick={() => setIsMenuOpen(false)}
                        className="min-w-0 rounded-sm focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:ring-offset-3 focus-visible:outline-none dark:focus-visible:ring-signal-400 dark:focus-visible:ring-offset-night-950"
                    >
                        <PublicBrand
                            inverse={isHome}
                            className="max-md:[&>span:last-child]:hidden"
                        />
                    </Link>

                    <nav
                        aria-label="Hoofdnavigatie"
                        className="ml-auto hidden items-center gap-7 lg:flex"
                    >
                        {headerNavItems.map((item) => {
                            const isActive = currentPath.startsWith(
                                item.activePath,
                            );

                            return (
                                <Link
                                    key={item.title}
                                    href={item.href}
                                    prefetch
                                    aria-current={isActive ? 'page' : undefined}
                                    className={cn(
                                        'rounded-sm border-b border-transparent py-2 text-[0.82rem] font-semibold tracking-[0.01em] transition-colors hover:border-flight-500 focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:outline-none motion-reduce:transition-none dark:focus-visible:ring-signal-400',
                                        isHome
                                            ? 'text-white/72 hover:text-white'
                                            : 'dark:text-night-300 text-paddock-slate hover:text-ink dark:hover:text-white',
                                        isActive &&
                                            (isHome
                                                ? 'border-flight-400 text-white'
                                                : 'border-flight-500 text-ink dark:text-white'),
                                    )}
                                >
                                    {item.title}
                                </Link>
                            );
                        })}
                    </nav>

                    <Link
                        href={eventsIndex()}
                        prefetch
                        className={cn(
                            'hidden min-h-10 items-center justify-center rounded-full border px-5 py-2 text-sm font-semibold transition-colors focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:ring-offset-2 focus-visible:outline-none lg:inline-flex',
                            isHome
                                ? 'border-white/15 bg-white/10 text-white hover:border-flight-400 hover:bg-flight-500 hover:text-ink'
                                : 'border-flight-500 bg-flight-500 text-ink hover:border-flight-400 hover:bg-flight-400',
                        )}
                    >
                        Bekijk agenda
                    </Link>

                    <button
                        type="button"
                        aria-controls="mobile-public-navigation"
                        aria-expanded={isMenuOpen}
                        aria-label={
                            isMenuOpen ? 'Sluit navigatie' : 'Open navigatie'
                        }
                        onClick={() => setIsMenuOpen((isOpen) => !isOpen)}
                        className={cn(
                            'ml-auto flex size-11 items-center justify-center rounded-full transition-colors focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:outline-none lg:hidden',
                            isHome
                                ? 'bg-white/10 text-white hover:bg-white/16'
                                : 'bg-ink/6 text-ink hover:bg-ink/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/16 dark:focus-visible:ring-signal-400',
                        )}
                    >
                        {isMenuOpen ? (
                            <X className="size-5" />
                        ) : (
                            <Menu className="size-5" />
                        )}
                    </button>
                </div>

                {isMenuOpen && (
                    <div
                        id="mobile-public-navigation"
                        className="absolute inset-x-0 top-full min-h-[calc(100dvh-4.5rem)] border-t border-white/10 bg-ink text-white shadow-2xl shadow-black/30 lg:hidden"
                    >
                        <nav
                            aria-label="Mobiele hoofdnavigatie"
                            className="mx-auto flex min-h-[calc(100dvh-4.5rem)] w-full max-w-7xl flex-col px-public-gutter pt-6 pb-8"
                        >
                            {mobileNavItems.map((item) => {
                                const isActive = currentPath.startsWith(
                                    item.activePath,
                                );

                                return (
                                    <Link
                                        key={item.title}
                                        href={item.href}
                                        prefetch
                                        aria-current={
                                            isActive ? 'page' : undefined
                                        }
                                        onClick={() => setIsMenuOpen(false)}
                                        className={cn(
                                            'flex min-h-14 items-center justify-between border-b border-white/10 py-4 font-public-display text-xl font-semibold tracking-[-0.025em] text-white/72 transition-colors hover:text-white focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:outline-none dark:focus-visible:ring-signal-400',
                                            isActive && 'text-flight-400',
                                        )}
                                    >
                                        {item.title}
                                        <ArrowUpRight className="size-4 opacity-45" />
                                    </Link>
                                );
                            })}

                            <div className="mt-auto grid gap-3 pt-8">
                                <Link
                                    href={eventsIndex()}
                                    prefetch
                                    onClick={() => setIsMenuOpen(false)}
                                    className="flex min-h-12 items-center justify-center rounded-full bg-flight-500 px-5 py-3 text-base font-semibold text-ink transition-colors hover:bg-flight-400 focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:ring-offset-2 focus-visible:ring-offset-ink focus-visible:outline-none"
                                >
                                    Bekijk agenda
                                </Link>
                            </div>
                        </nav>
                    </div>
                )}
            </header>

            <main>{children}</main>

            <footer className="bg-ink text-white">
                <div className="mx-auto grid w-full max-w-7xl gap-12 px-public-gutter py-14 sm:py-16 lg:grid-cols-[1.5fr_0.58fr_0.58fr_0.58fr] lg:gap-12 lg:py-20">
                    <div className="max-w-sm">
                        <PublicBrand inverse />
                        <p className="mt-6 text-sm leading-7 text-white/52">
                            <strong className="font-semibold text-white/78">
                                Dutch Drone Squad
                            </strong>{' '}
                            is een groep van enthousiaste drone racers uit
                            Alkmaar en omstreken.
                        </p>
                    </div>

                    <div className="grid grid-cols-3 gap-x-4 gap-y-10 sm:gap-x-8 lg:contents">
                        <FooterLinks
                            title="Ontdek"
                            items={footerExploreItems}
                        />
                        <FooterLinks title="DDS" items={footerDdsItems} />
                        <FooterLinks
                            title="Praktisch"
                            items={footerPracticalItems}
                        />
                    </div>
                </div>

                <div className="border-t border-white/10">
                    <div className="mx-auto flex w-full max-w-7xl flex-col gap-2 px-public-gutter py-6 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                        <p>© {new Date().getFullYear()} Dutch Drone Squad</p>
                        <p>Where racing brings pilots together.</p>
                    </div>
                </div>
            </footer>
        </div>
    );
}

type FooterLinksProps = {
    items: { href: NonNullable<InertiaLinkProps['href']>; title: string }[];
    title: string;
};

function FooterLinks({ items, title }: FooterLinksProps) {
    return (
        <div>
            <h2 className="text-xs font-semibold tracking-[0.12em] text-white/58 uppercase">
                {title}
            </h2>
            <div className="mt-5 flex flex-col items-start gap-3.5 text-sm text-white/62">
                {items.map((item) => (
                    <Link
                        key={item.title}
                        href={item.href}
                        prefetch
                        className="rounded-sm transition-colors hover:text-flight-400 focus-visible:ring-2 focus-visible:ring-signal-400 focus-visible:outline-none motion-reduce:transition-none"
                    >
                        {item.title}
                    </Link>
                ))}
            </div>
        </div>
    );
}
