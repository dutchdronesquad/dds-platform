import { Link, usePage } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { ArrowUpRight, Menu, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import DdsBrand from '@/components/dds-brand';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
} from '@/components/ui/navigation-menu';
import { cn } from '@/lib/utils';
import {
    about,
    contact,
    home,
    house_rules as houseRules,
    media,
    partners,
} from '@/routes';
import { index as eventsIndex } from '@/routes/events';
import { index as gettingStartedIndex } from '@/routes/getting_started';
import { index as locationsIndex } from '@/routes/locations';
import { index as newsIndex } from '@/routes/news';
import { index as projectsIndex } from '@/routes/projects';

type PublicNavItem = {
    activePath: string;
    href: NonNullable<InertiaLinkProps['href']>;
    title: string;
};

type FooterNavItem = PublicNavItem;

const headerNavItems: PublicNavItem[] = [
    {
        title: 'Starten met FPV',
        href: gettingStartedIndex({ query: { source: 'navigation' } }),
        activePath: '/getting-started',
    },
    { title: 'Locaties', href: locationsIndex(), activePath: '/locations' },
    { title: 'Nieuws', href: newsIndex(), activePath: '/news' },
    { title: 'Contact', href: contact(), activePath: '/contact' },
];

const informationNavItems: PublicNavItem[] = [
    {
        title: 'Over DDS',
        href: about(),
        activePath: '/about',
    },
    {
        title: 'Projecten',
        href: projectsIndex(),
        activePath: '/projects',
    },
    {
        title: 'In de media',
        href: media(),
        activePath: '/media',
    },
    {
        title: 'Partners',
        href: partners(),
        activePath: '/partners',
    },
];

const footerExploreItems: FooterNavItem[] = [
    { title: 'Agenda', href: eventsIndex(), activePath: '/events' },
    { title: 'Nieuws', href: newsIndex(), activePath: '/news' },
    { title: 'Locaties', href: locationsIndex(), activePath: '/locations' },
];

const footerDdsItems: FooterNavItem[] = [
    { title: 'Over DDS', href: about(), activePath: '/about' },
    { title: 'Projecten', href: projectsIndex(), activePath: '/projects' },
    { title: 'Partners', href: partners(), activePath: '/partners' },
    { title: 'In de media', href: media(), activePath: '/media' },
];

const footerPracticalItems: FooterNavItem[] = [
    {
        title: 'Starten met FPV',
        href: gettingStartedIndex({ query: { source: 'footer' } }),
        activePath: '/getting-started',
    },
    { title: 'Huisregels', href: houseRules(), activePath: '/house-rules' },
    { title: 'Contact', href: contact(), activePath: '/contact' },
];

const socialItems: { href: string; icon: ReactNode; title: string }[] = [
    {
        title: 'Instagram',
        href: 'https://www.instagram.com/dutchdronesquad/',
        icon: (
            <>
                <rect x="3" y="3" width="18" height="18" rx="5" />
                <circle cx="12" cy="12" r="4" />
                <circle
                    cx="17.5"
                    cy="6.5"
                    r="1"
                    fill="currentColor"
                    stroke="none"
                />
            </>
        ),
    },
    {
        title: 'Facebook',
        href: 'https://www.facebook.com/dutchdronesquad',
        icon: (
            <path d="M10 21v-8H7V9h3V7.5C10 4.5 11.9 3 15 3h2v4h-1.5C14.4 7 14 7.6 14 8.5V9h3l-.7 4H14v8" />
        ),
    },
    {
        title: 'YouTube',
        href: 'https://www.youtube.com/@dutchdronesquad',
        icon: (
            <>
                <rect x="2.5" y="6" width="19" height="12" rx="4" />
                <path d="m10 9 5 3-5 3Z" fill="currentColor" stroke="none" />
            </>
        ),
    },
    {
        title: 'Twitch',
        href: 'https://www.twitch.tv/dutchdronesquad',
        icon: (
            <>
                <path d="M5 3h15v11l-5 5h-4l-3 3v-3H5Z" />
                <path d="M10 8v5M15 8v5" />
            </>
        ),
    },
];

type Props = {
    children: ReactNode;
};

export default function PublicLayout({ children }: Props) {
    const { url } = usePage();
    const currentPath = url.split('?')[0];
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isHeaderScrolled, setIsHeaderScrolled] = useState(false);
    const menuButtonRef = useRef<HTMLButtonElement>(null);
    const mobileMenuRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        let animationFrameId: number | null = null;
        let lastHeaderScrolled: boolean | null = null;

        const updateHeaderState = () => {
            animationFrameId = null;
            const nextHeaderScrolled = window.scrollY > 32;

            if (nextHeaderScrolled === lastHeaderScrolled) {
                return;
            }

            lastHeaderScrolled = nextHeaderScrolled;
            setIsHeaderScrolled(nextHeaderScrolled);
        };

        const scheduleHeaderUpdate = () => {
            if (animationFrameId !== null) {
                return;
            }

            animationFrameId = window.requestAnimationFrame(updateHeaderState);
        };

        scheduleHeaderUpdate();
        window.addEventListener('scroll', scheduleHeaderUpdate, {
            passive: true,
        });

        return () => {
            window.removeEventListener('scroll', scheduleHeaderUpdate);

            if (animationFrameId !== null) {
                window.cancelAnimationFrame(animationFrameId);
            }
        };
    }, []);

    useEffect(() => {
        if (!isMenuOpen) {
            return;
        }

        const previousBodyOverflow = document.body.style.overflow;
        const desktopMediaQuery = window.matchMedia('(min-width: 64rem)');

        const focusableElements = (): HTMLElement[] => {
            const menuButton = menuButtonRef.current;
            const menuElements = Array.from(
                mobileMenuRef.current?.querySelectorAll<HTMLElement>(
                    'a[href], button:not([disabled])',
                ) ?? [],
            );

            return menuButton ? [menuButton, ...menuElements] : menuElements;
        };

        const closeMenu = () => {
            setIsMenuOpen(false);
            window.requestAnimationFrame(() => menuButtonRef.current?.focus());
        };

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeMenu();

                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const elements = focusableElements();
            const firstElement = elements[0];
            const lastElement = elements.at(-1);

            if (!firstElement || !lastElement) {
                return;
            }

            if (event.shiftKey && document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            } else if (
                !event.shiftKey &&
                document.activeElement === lastElement
            ) {
                event.preventDefault();
                firstElement.focus();
            }
        };

        const handleViewportChange = (event: MediaQueryListEvent) => {
            if (event.matches) {
                setIsMenuOpen(false);
            }
        };

        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', handleKeyDown);
        desktopMediaQuery.addEventListener('change', handleViewportChange);
        mobileMenuRef.current?.querySelector<HTMLElement>('a[href]')?.focus();

        return () => {
            document.body.style.overflow = previousBodyOverflow;
            document.removeEventListener('keydown', handleKeyDown);
            desktopMediaQuery.removeEventListener(
                'change',
                handleViewportChange,
            );
        };
    }, [isMenuOpen]);

    const isSectionActive = (activePath: string): boolean =>
        currentPath === activePath || currentPath.startsWith(`${activePath}/`);

    const isEventsActive = isSectionActive('/events');

    return (
        <div className="min-h-screen bg-paper text-ink dark:bg-night-950 dark:text-white">
            <a
                href="#main-content"
                className="fixed top-3 left-3 z-[100] -translate-y-20 rounded-sm bg-white px-4 py-3 text-sm font-semibold text-ink shadow-xl transition-transform focus-visible:translate-y-0 focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:ring-offset-2 focus-visible:outline-none motion-reduce:transition-none"
            >
                Ga naar hoofdinhoud
            </a>

            <header
                className={cn(
                    'absolute inset-x-0 top-0 z-50 border-b border-white/10 bg-linear-to-b from-ink/82 to-ink/38 backdrop-blur-lg transition-[background-color,border-color,box-shadow,backdrop-filter] duration-300 motion-reduce:transition-none lg:sticky lg:-mb-18',
                    isHeaderScrolled &&
                        'lg:border-white/12 lg:bg-ink/94 lg:bg-none lg:shadow-lg lg:shadow-ink/10 lg:backdrop-blur-md',
                )}
            >
                <div className="mx-auto flex min-h-18 w-full max-w-7xl items-center gap-5 px-public-gutter">
                    <Link
                        href={home()}
                        prefetch
                        aria-label="Dutch Drone Squad home"
                        aria-current={currentPath === '/' ? 'page' : undefined}
                        onClick={() => setIsMenuOpen(false)}
                        className="min-w-0 rounded-sm focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:ring-offset-3 focus-visible:outline-none dark:focus-visible:ring-signal-400 dark:focus-visible:ring-offset-night-950"
                    >
                        <DdsBrand
                            inverse
                            className="max-md:[&>span:last-child]:hidden"
                        />
                    </Link>

                    <NavigationMenu
                        aria-label="Hoofdnavigatie"
                        viewport={false}
                        className="ml-auto hidden lg:flex"
                    >
                        <NavigationMenuList className="gap-7">
                            {headerNavItems.slice(0, 3).map((item) => (
                                <DesktopNavigationLink
                                    key={item.title}
                                    item={item}
                                    currentPath={currentPath}
                                />
                            ))}

                            <NavigationMenuItem>
                                <NavigationMenuTrigger
                                    aria-label="Open Informatie menu"
                                    className={cn(
                                        'h-auto rounded-sm border-b border-transparent bg-transparent px-0 py-2 text-[0.82rem] font-semibold tracking-[0.01em] text-white/72 hover:border-flight-500 hover:bg-transparent hover:text-white focus:bg-white/10 focus:text-white focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:outline-none data-[state=open]:bg-white/10 data-[state=open]:text-white dark:focus-visible:ring-signal-400',
                                        informationNavItems.some((item) =>
                                            isSectionActive(item.activePath),
                                        ) && 'border-flight-400 text-white',
                                    )}
                                >
                                    Informatie
                                </NavigationMenuTrigger>
                                <NavigationMenuContent
                                    data-testid="information-navigation-content"
                                    className="right-0 left-auto w-40 border-white/12 bg-ink! p-2 text-white! shadow-2xl shadow-black/30 md:w-40"
                                >
                                    <div className="grid gap-1">
                                        {informationNavItems.map((item) => {
                                            const isActive = isSectionActive(
                                                item.activePath,
                                            );

                                            return (
                                                <NavigationMenuLink
                                                    key={item.title}
                                                    asChild
                                                    active={isActive}
                                                    className="rounded-sm border border-transparent px-4 py-3.5 text-white/72 transition-[background-color,border-color,color] hover:border-white/12 hover:bg-white/10 hover:text-white focus:border-white/12 focus:bg-white/10 focus:text-white data-[active=true]:border-flight-400/30 data-[active=true]:bg-white/8 data-[active=true]:text-flight-400"
                                                >
                                                    <Link
                                                        href={item.href}
                                                        prefetch
                                                        aria-current={
                                                            isActive
                                                                ? 'page'
                                                                : undefined
                                                        }
                                                    >
                                                        <span className="font-semibold whitespace-nowrap">
                                                            {item.title}
                                                        </span>
                                                    </Link>
                                                </NavigationMenuLink>
                                            );
                                        })}
                                    </div>
                                </NavigationMenuContent>
                            </NavigationMenuItem>

                            <DesktopNavigationLink
                                item={headerNavItems[3]}
                                currentPath={currentPath}
                            />
                        </NavigationMenuList>
                    </NavigationMenu>

                    <Link
                        href={eventsIndex()}
                        prefetch
                        aria-current={isEventsActive ? 'page' : undefined}
                        className={cn(
                            'hidden min-h-10 items-center justify-center rounded-full border border-white/15 bg-white/10 px-5 py-2 text-sm font-semibold text-white transition-colors hover:border-flight-400 hover:bg-flight-500 hover:text-ink focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:ring-offset-2 focus-visible:ring-offset-ink focus-visible:outline-none lg:inline-flex',
                            isEventsActive &&
                                'border-flight-400 bg-flight-500 text-ink',
                        )}
                    >
                        Bekijk agenda
                    </Link>

                    <button
                        ref={menuButtonRef}
                        type="button"
                        aria-controls="mobile-public-navigation"
                        aria-expanded={isMenuOpen}
                        aria-label={
                            isMenuOpen ? 'Sluit navigatie' : 'Open navigatie'
                        }
                        onClick={() => setIsMenuOpen((isOpen) => !isOpen)}
                        className="ml-auto flex size-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/16 focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:outline-none lg:hidden dark:focus-visible:ring-signal-400"
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
                        ref={mobileMenuRef}
                        id="mobile-public-navigation"
                        className="absolute inset-x-0 top-full min-h-[calc(100dvh-4.5rem)] border-t border-white/10 bg-ink text-white shadow-2xl shadow-black/30 lg:hidden"
                    >
                        <nav
                            aria-label="Mobiele hoofdnavigatie"
                            className="mx-auto flex min-h-[calc(100dvh-4.5rem)] w-full max-w-7xl flex-col px-public-gutter pt-6 pb-8"
                        >
                            {headerNavItems.slice(0, 3).map((item) => {
                                const isActive = isSectionActive(
                                    item.activePath,
                                );

                                return (
                                    <MobileNavigationLink
                                        key={item.title}
                                        item={item}
                                        isActive={isActive}
                                        onNavigate={() => setIsMenuOpen(false)}
                                    />
                                );
                            })}

                            <div className="border-b border-white/10 py-4">
                                <p
                                    className={cn(
                                        'font-public-display text-xl font-semibold tracking-[-0.025em] text-white/72',
                                        informationNavItems.some((item) =>
                                            isSectionActive(item.activePath),
                                        ) && 'text-flight-400',
                                    )}
                                >
                                    Informatie
                                </p>
                                <div className="mt-3 grid gap-1.5 border-l border-white/14 pl-4">
                                    {informationNavItems.map((item) => {
                                        const isActive = isSectionActive(
                                            item.activePath,
                                        );

                                        return (
                                            <Link
                                                key={item.title}
                                                href={item.href}
                                                prefetch
                                                aria-current={
                                                    isActive
                                                        ? 'page'
                                                        : undefined
                                                }
                                                onClick={() =>
                                                    setIsMenuOpen(false)
                                                }
                                                className={cn(
                                                    'flex min-h-10 items-center justify-between rounded-sm px-2 text-sm font-semibold text-white/58 transition-colors hover:bg-white/6 hover:text-white focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:outline-none dark:focus-visible:ring-signal-400',
                                                    isActive &&
                                                        'text-flight-400',
                                                )}
                                            >
                                                {item.title}
                                                <ArrowUpRight className="size-3.5 opacity-45" />
                                            </Link>
                                        );
                                    })}
                                </div>
                            </div>

                            {headerNavItems.slice(3).map((item) => {
                                const isActive = isSectionActive(
                                    item.activePath,
                                );

                                return (
                                    <MobileNavigationLink
                                        key={item.title}
                                        item={item}
                                        isActive={isActive}
                                        onNavigate={() => setIsMenuOpen(false)}
                                    />
                                );
                            })}

                            <div className="mt-auto grid gap-3 pt-8">
                                <Link
                                    href={eventsIndex()}
                                    prefetch
                                    aria-current={
                                        isEventsActive ? 'page' : undefined
                                    }
                                    onClick={() => setIsMenuOpen(false)}
                                    className={cn(
                                        'flex min-h-12 items-center justify-center rounded-full bg-flight-500 px-5 py-3 text-base font-semibold text-ink transition-colors hover:bg-flight-400 focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:ring-offset-2 focus-visible:ring-offset-ink focus-visible:outline-none',
                                        isEventsActive &&
                                            'ring-flight-200 ring-2 ring-offset-2 ring-offset-ink',
                                    )}
                                >
                                    Bekijk agenda
                                </Link>
                            </div>
                        </nav>
                    </div>
                )}
            </header>

            <main
                id="main-content"
                tabIndex={-1}
                inert={isMenuOpen}
                aria-hidden={isMenuOpen ? true : undefined}
            >
                {children}
            </main>

            <footer
                inert={isMenuOpen}
                aria-hidden={isMenuOpen ? true : undefined}
                className="bg-ink text-white"
            >
                <div className="mx-auto grid w-full max-w-7xl gap-10 px-public-gutter py-10 sm:py-12 lg:grid-cols-[1.5fr_0.58fr_0.58fr_0.58fr] lg:gap-10 lg:py-14">
                    <div className="max-w-sm">
                        <DdsBrand inverse />
                        <p className="mt-4 text-sm leading-6 text-white/52">
                            <strong className="font-semibold text-white/78">
                                Dutch Drone Squad
                            </strong>{' '}
                            is een groep van enthousiaste drone racers uit
                            Alkmaar en omstreken.
                        </p>
                        <div className="mt-5">
                            <p className="text-xs font-semibold tracking-[0.12em] text-white/58 uppercase">
                                Volg ons
                            </p>
                            <div className="mt-3 flex items-center gap-2.5">
                                {socialItems.map((item) => (
                                    <a
                                        key={item.title}
                                        href={item.href}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label={`Volg Dutch Drone Squad op ${item.title} (opent in een nieuw tabblad)`}
                                        className="flex size-10 items-center justify-center rounded-full border border-white/14 text-white/62 transition-colors hover:border-flight-400/60 hover:bg-flight-400 hover:text-ink focus-visible:ring-2 focus-visible:ring-signal-400 focus-visible:ring-offset-2 focus-visible:ring-offset-ink focus-visible:outline-none motion-reduce:transition-none"
                                    >
                                        <svg
                                            aria-hidden="true"
                                            className="size-4.5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="1.8"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                        >
                                            {item.icon}
                                        </svg>
                                    </a>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-3 gap-x-4 gap-y-8 sm:gap-x-8 lg:contents">
                        <FooterLinks
                            title="Ontdek"
                            items={footerExploreItems}
                            currentPath={currentPath}
                        />
                        <FooterLinks
                            title="Dutch Drone Squad"
                            items={footerDdsItems}
                            currentPath={currentPath}
                        />
                        <FooterLinks
                            title="Praktisch"
                            items={footerPracticalItems}
                            currentPath={currentPath}
                        />
                    </div>
                </div>

                <div className="border-t border-white/10">
                    <div className="mx-auto flex w-full max-w-7xl flex-col gap-1 px-public-gutter py-4 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                        <p>© {new Date().getFullYear()} Dutch Drone Squad</p>
                        <p>Where racing brings pilots together.</p>
                    </div>
                </div>
            </footer>
        </div>
    );
}

type FooterLinksProps = {
    currentPath: string;
    items: FooterNavItem[];
    title: string;
};

function DesktopNavigationLink({
    currentPath,
    item,
}: {
    currentPath: string;
    item: PublicNavItem;
}) {
    const isActive =
        currentPath === item.activePath ||
        currentPath.startsWith(`${item.activePath}/`);

    return (
        <NavigationMenuItem>
            <NavigationMenuLink asChild active={isActive}>
                <Link
                    href={item.href}
                    prefetch
                    aria-current={isActive ? 'page' : undefined}
                    className={cn(
                        'rounded-sm border-b border-transparent px-0 py-2 text-[0.82rem] font-semibold tracking-[0.01em] text-white/72 transition-colors hover:border-flight-500 hover:bg-transparent hover:text-white focus:bg-white/10 focus:text-white focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:outline-none data-[active=true]:bg-transparent data-[active=true]:text-white motion-reduce:transition-none dark:focus-visible:ring-signal-400',
                        isActive && 'border-flight-400 text-white',
                    )}
                >
                    {item.title}
                </Link>
            </NavigationMenuLink>
        </NavigationMenuItem>
    );
}

function MobileNavigationLink({
    isActive,
    item,
    onNavigate,
}: {
    isActive: boolean;
    item: PublicNavItem;
    onNavigate: () => void;
}) {
    return (
        <Link
            href={item.href}
            prefetch
            aria-current={isActive ? 'page' : undefined}
            onClick={onNavigate}
            className={cn(
                'flex min-h-14 items-center justify-between border-b border-white/10 py-4 font-public-display text-xl font-semibold tracking-[-0.025em] text-white/72 transition-colors hover:text-white focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:outline-none dark:focus-visible:ring-signal-400',
                isActive && 'text-flight-400',
            )}
        >
            {item.title}
            <ArrowUpRight className="size-4 opacity-45" />
        </Link>
    );
}

function FooterLinks({ currentPath, items, title }: FooterLinksProps) {
    return (
        <div>
            <h2 className="text-xs font-semibold tracking-[0.12em] text-white/58 uppercase">
                {title}
            </h2>
            <div className="mt-4 flex flex-col items-start gap-3 text-sm text-white/62">
                {items.map((item) => (
                    <Link
                        key={item.title}
                        href={item.href}
                        prefetch
                        aria-current={
                            currentPath === item.activePath ||
                            currentPath.startsWith(`${item.activePath}/`)
                                ? 'page'
                                : undefined
                        }
                        className={cn(
                            'rounded-sm transition-colors hover:text-flight-400 focus-visible:ring-2 focus-visible:ring-signal-400 focus-visible:outline-none motion-reduce:transition-none',
                            (currentPath === item.activePath ||
                                currentPath.startsWith(
                                    `${item.activePath}/`,
                                )) &&
                                'font-semibold text-flight-400',
                        )}
                    >
                        {item.title}
                    </Link>
                ))}
            </div>
        </div>
    );
}
