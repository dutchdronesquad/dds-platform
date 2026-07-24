import type { LucideIcon } from 'lucide-react';
import {
    GitPullRequest,
    Github,
    ListVideo,
    Mic2,
    MonitorUp,
    RadioTower,
    SquareTerminal,
    Timer,
    Video,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import PublicExternalLink from '@/components/public/public-external-link';
import { Eyebrow, PublicHero } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { cn } from '@/lib/utils';
import type { PublicProject, SeoMetadata } from '@/types';

type Props = {
    projects: PublicProject[];
    seo: SeoMetadata;
};

const githubUrl = 'https://github.com/dutchdronesquad';

type ProjectFilter = 'flightcases' | 'rotorhazard';

const projectFilters: { label: string; value: ProjectFilter }[] = [
    { label: 'RotorHazard', value: 'rotorhazard' },
    { label: 'Flightcases', value: 'flightcases' },
];

export default function ProjectsIndex({ projects, seo }: Props) {
    const spotlightProject = projects.find((project) => project.featured);
    const gridProjects = projects;

    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                title="Projecten uit de praktijk."
                description="Voor races, trainingen en livestreams maken en verbeteren we software, hardware en hulpmiddelen. Een deel daarvan is open source of sluit aan op RotorHazard."
                actions={[
                    { label: 'Bekijk de projecten', href: '#projecten' },
                    {
                        label: 'Naar GitHub',
                        href: githubUrl,
                        external: true,
                    },
                ]}
                media={{
                    src: '/images/dds/racing/pilot-preparing-drone.jpg',
                    alt: 'DDS-piloot bereidt een FPV-drone voor naast de racebaan',
                    position: '58% center',
                }}
                separatorTone="air"
            />

            <DevelopmentIntro />
            {spotlightProject && (
                <SoftwareSpotlight project={spotlightProject} />
            )}
            <ProjectsGrid projects={gridProjects} />
            <CommunityBand />
        </>
    );
}

function DevelopmentIntro() {
    return (
        <section
            id="projecten"
            aria-labelledby="development-intro-heading"
            className="scroll-mt-20 bg-air py-14 text-deep-signal sm:py-16"
        >
            <div className="mx-auto w-full max-w-7xl px-public-gutter">
                <div className="grid gap-6 lg:grid-cols-[0.85fr_1.15fr] lg:items-end lg:gap-20">
                    <h2
                        id="development-intro-heading"
                        className="max-w-xl font-public-display text-3xl leading-[1.05] font-semibold tracking-[-0.045em] text-balance sm:text-4xl"
                    >
                        Van racecontrol tot baanopbouw.
                    </h2>
                    <p className="max-w-2xl text-base leading-7 text-signal-muted sm:text-lg sm:leading-8">
                        De projecten op deze pagina ontstonden vanuit wat we
                        tijdens races, trainingen en livestreams nodig hadden.
                        De ene keer was dat software, de andere keer hardware of
                        een bijdrage aan een bestaand open-sourceproject.
                    </p>
                </div>
            </div>
        </section>
    );
}

function SoftwareSpotlight({ project }: { project: PublicProject }) {
    const medium = project.media[1] ?? project.media[0];
    const prefersReducedMotion = usePrefersReducedMotion();
    const videoUrl = prefersReducedMotion ? null : project.videoUrl;
    const sourceLink = project.supportingLinks.find(
        (link) => link.label === 'Broncode',
    );

    return (
        <section
            aria-labelledby="software-spotlight-heading"
            data-testid="project-spotlight-trackdraw"
            className="overflow-hidden bg-deep-signal text-white"
        >
            <div
                data-testid="project-spotlight-container"
                className="mx-auto grid w-full max-w-7xl gap-10 px-public-gutter py-16 sm:py-20 lg:grid-cols-[0.72fr_1.28fr] lg:items-center lg:gap-16 lg:py-24"
            >
                <div className="max-w-xl">
                    <Eyebrow inverse line={false}>
                        Baanontwerp
                    </Eyebrow>
                    <h2
                        id="software-spotlight-heading"
                        className="mt-5 font-public-display text-5xl leading-[0.95] font-semibold tracking-[-0.055em] sm:text-6xl lg:text-7xl"
                    >
                        {project.title}
                    </h2>
                    <p className="mt-7 font-public-display text-2xl leading-[1.1] font-semibold tracking-[-0.04em] text-balance sm:text-3xl">
                        Een baanidee wordt pas echt goed als je het kunt zien,
                        testen en delen.
                    </p>
                    <p className="mt-5 text-base leading-7 text-white/68 sm:text-lg sm:leading-8">
                        {project.summary}
                    </p>
                    <div className="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3">
                        {project.primaryLink && (
                            <PublicExternalLink
                                href={project.primaryLink.url}
                                data-testid="project-external-link-trackdraw"
                                className="min-h-11 bg-dds-cyan px-5 py-3 text-sm font-semibold text-deep-signal transition-colors hover:bg-white dark:focus-visible:ring-offset-deep-signal"
                            >
                                {project.primaryLink.label}
                            </PublicExternalLink>
                        )}
                        {sourceLink && (
                            <PublicExternalLink
                                href={sourceLink.url}
                                className="min-h-11 text-sm font-semibold text-white/75 transition-colors hover:text-white"
                            >
                                Bekijk broncode
                            </PublicExternalLink>
                        )}
                    </div>
                </div>

                <figure
                    data-testid="project-spotlight-media-frame"
                    className="overflow-hidden rounded-sm border border-white/15 bg-night-950 shadow-[0_28px_70px_-36px_rgba(0,0,0,0.8)]"
                >
                    <div className="relative">
                        {videoUrl ? (
                            <video
                                src={videoUrl}
                                poster={medium?.src}
                                autoPlay
                                muted
                                loop
                                playsInline
                                aria-hidden="true"
                                data-testid="project-spotlight-video-trackdraw"
                                className="aspect-[1920/1044] w-full object-cover"
                            />
                        ) : (
                            medium && (
                                <img
                                    src={medium.src}
                                    alt={medium.alt}
                                    loading="lazy"
                                    data-testid="project-spotlight-image-trackdraw"
                                    className="aspect-[1920/1044] w-full object-cover"
                                />
                            )
                        )}
                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-0 ring-1 ring-white/8 ring-inset"
                        />
                    </div>
                    <figcaption className="flex flex-col gap-1 border-t border-white/10 px-5 py-4 text-xs leading-5 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                        <span className="font-semibold tracking-[0.08em] text-white/78 uppercase">
                            TrackDraw-editor
                        </span>
                        <span className="text-white/48">
                            2D-ontwerp · 3D-controle van de racelijn
                        </span>
                    </figcaption>
                </figure>
            </div>
        </section>
    );
}

function ProjectsGrid({ projects }: { projects: PublicProject[] }) {
    const [activeFilter, setActiveFilter] = useState<ProjectFilter | null>(
        null,
    );

    const visibleProjects =
        activeFilter === null
            ? projects
            : projects.filter((project) =>
                  matchesProjectFilter(project, activeFilter),
              );

    return (
        <section
            aria-labelledby="projects-grid-heading"
            className="bg-air py-public-section text-deep-signal dark:bg-night-900 dark:text-white"
        >
            <div className="mx-auto w-full max-w-7xl px-public-gutter">
                <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                    <div className="max-w-2xl">
                        <Eyebrow line={false}>Alle projecten</Eyebrow>
                        <h2
                            id="projects-grid-heading"
                            className="mt-5 font-public-display text-4xl leading-[1] font-semibold tracking-[-0.05em] text-balance sm:text-5xl lg:text-6xl"
                        >
                            Ontwerpen, timen en livestreamen.
                        </h2>
                        <p className="mt-5 max-w-xl text-base leading-7 text-signal-muted sm:text-lg sm:leading-8 dark:text-night-400">
                            Van TrackDraw en RotorHazard-plugins tot flightcases
                            voor timing en livestreams.
                        </p>
                    </div>

                    <div
                        role="group"
                        aria-label="Filter projecten op type"
                        className="flex flex-wrap gap-2"
                    >
                        <FilterButton
                            label="Alle projecten"
                            isActive={activeFilter === null}
                            onClick={() => setActiveFilter(null)}
                        />
                        {projectFilters.map((filter) => (
                            <FilterButton
                                key={filter.value}
                                label={filter.label}
                                isActive={activeFilter === filter.value}
                                onClick={() => setActiveFilter(filter.value)}
                            />
                        ))}
                    </div>
                </div>

                <p
                    aria-live="polite"
                    data-testid="projects-grid-status"
                    className="sr-only"
                >
                    {visibleProjects.length}{' '}
                    {visibleProjects.length === 1 ? 'project' : 'projecten'}{' '}
                    zichtbaar
                </p>
                <div
                    id="projects-grid-results"
                    className="mt-10 grid gap-4 sm:grid-cols-2 lg:mt-12 lg:grid-cols-3"
                >
                    {visibleProjects.map((project) => (
                        <ProjectCard key={project.slug} project={project} />
                    ))}
                </div>
            </div>
        </section>
    );
}

function matchesProjectFilter(
    project: PublicProject,
    filter: ProjectFilter,
): boolean {
    if (filter === 'flightcases') {
        return project.type.value === 'hardware_build';
    }

    return [
        'open_source_contribution',
        'race_tooling',
        'rotorhazard_plugin',
    ].includes(project.type.value);
}

function FilterButton({
    isActive,
    label,
    onClick,
}: {
    isActive: boolean;
    label: string;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={isActive}
            aria-controls="projects-grid-results"
            className={cn(
                'inline-flex min-h-10 items-center rounded-sm border px-4 py-2 text-sm font-semibold transition-colors focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-2 focus-visible:outline-none dark:focus-visible:ring-offset-night-900',
                isActive
                    ? 'border-deep-signal bg-deep-signal text-white dark:border-dds-cyan dark:bg-dds-cyan dark:text-deep-signal'
                    : 'dark:text-night-300 border-paddock-rule bg-white text-signal-muted hover:border-dds-blue hover:text-deep-signal dark:border-white/15 dark:bg-night-950 dark:hover:border-dds-cyan dark:hover:text-white',
            )}
        >
            {label}
        </button>
    );
}

function ProjectCard({ project }: { project: PublicProject }) {
    const Icon =
        projectIcons[project.slug] ??
        typeIcons[project.type.value] ??
        MonitorUp;
    const medium = project.media[0];
    const isMark = medium?.src.endsWith('.svg') ?? false;
    const hasPrimaryLink = project.primaryLink !== null;
    const imageClassName = isMark
        ? 'h-full w-full object-contain p-10'
        : cn(
              'h-full w-full object-cover',
              hasPrimaryLink &&
                  'transition duration-500 group-hover:scale-[1.02] motion-reduce:transform-none motion-reduce:transition-none',
          );

    return (
        <article
            data-testid={`project-card-${project.slug}`}
            className={cn(
                'group flex flex-col overflow-hidden border border-paddock-rule bg-white dark:border-white/12 dark:bg-night-950',
                hasPrimaryLink &&
                    'transition-colors hover:border-dds-blue/50 dark:hover:border-dds-cyan/50',
            )}
        >
            {medium && (
                <div
                    data-testid={`project-media-${project.slug}`}
                    className={cn(
                        'relative aspect-16/10 overflow-hidden',
                        isMark
                            ? 'bg-paddock dark:bg-night-900'
                            : 'bg-deep-signal/10 dark:bg-night-900',
                    )}
                >
                    <img
                        data-testid={`project-media-image-${project.slug}`}
                        src={medium.src}
                        alt={isMark ? '' : medium.alt}
                        loading="lazy"
                        className={cn(
                            imageClassName,
                            medium.darkSrc && 'dark:hidden',
                        )}
                    />
                    {medium.darkSrc && (
                        <img
                            data-testid={`project-media-dark-image-${project.slug}`}
                            src={medium.darkSrc}
                            alt=""
                            loading="lazy"
                            className={cn(imageClassName, 'hidden dark:block')}
                        />
                    )}
                </div>
            )}

            <div className="flex flex-1 flex-col p-6 sm:p-7">
                <span className="flex size-10 items-center justify-center border border-paddock-rule bg-air text-dds-blue dark:border-white/15 dark:bg-white/6 dark:text-dds-cyan">
                    <Icon aria-hidden="true" className="size-5" />
                </span>
                <p className="mt-4 text-[0.68rem] leading-5 font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                    {project.type.label}
                </p>
                <h3 className="mt-2 font-public-display text-2xl leading-[1.08] font-semibold tracking-[-0.035em]">
                    {project.title}
                </h3>
                <p className="mt-3 text-sm leading-6 text-signal-muted dark:text-night-400">
                    {project.summary}
                </p>
                {project.primaryLink && (
                    <PublicExternalLink
                        href={project.primaryLink.url}
                        data-testid={`project-external-link-${project.slug}`}
                        className="mt-auto min-h-11 self-start pt-5 text-sm font-semibold text-dds-blue transition-colors hover:text-deep-signal dark:text-dds-cyan dark:hover:text-white"
                    >
                        {project.primaryLink.label}
                    </PublicExternalLink>
                )}
            </div>
        </article>
    );
}

function usePrefersReducedMotion(): boolean {
    const [prefersReducedMotion, setPrefersReducedMotion] = useState(true);

    useEffect(() => {
        const mediaQuery = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        );
        const updatePreference = () => {
            setPrefersReducedMotion(mediaQuery.matches);
        };

        updatePreference();
        mediaQuery.addEventListener('change', updatePreference);

        return () => {
            mediaQuery.removeEventListener('change', updatePreference);
        };
    }, []);

    return prefersReducedMotion;
}

const projectIcons: Record<string, LucideIcon> = {
    'timer-dotfiles': SquareTerminal,
    'rotorhazard-contributions': GitPullRequest,
    'rh-stream-overlays': MonitorUp,
    'rh-race-voice': Mic2,
    'rh-youtube-chapters': ListVideo,
    'live-feed-flightcase': RadioTower,
    'event-livestream-flightcase': Video,
    'timing-flightcase': Timer,
};

const typeIcons: Record<string, LucideIcon> = {
    hardware_build: RadioTower,
    open_source_contribution: GitPullRequest,
    race_tooling: SquareTerminal,
    rotorhazard_plugin: MonitorUp,
};

function CommunityBand() {
    return (
        <section
            data-testid="projects-community-band"
            className="border-t border-deep-signal/10 bg-dds-orange text-deep-signal"
        >
            <div className="mx-auto grid w-full max-w-7xl items-center gap-7 px-public-gutter py-12 sm:py-14 lg:grid-cols-[1fr_auto]">
                <div className="max-w-3xl">
                    <h2 className="font-public-display text-4xl leading-[1] font-semibold tracking-[-0.05em] text-balance sm:text-5xl">
                        Benieuwd wat er achter onze races draait?
                    </h2>
                    <p className="mt-3 text-sm leading-6 font-medium text-deep-signal sm:text-base">
                        Bekijk de code, download releases en volg de
                        ontwikkeling van onze openbare projecten op GitHub.
                    </p>
                </div>
                <PublicExternalLink
                    href={githubUrl}
                    className="min-h-11 self-start bg-deep-signal px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-deep-signal/90 focus-visible:ring-dds-cyan focus-visible:ring-offset-dds-orange lg:self-center dark:focus-visible:ring-offset-dds-orange"
                >
                    <Github aria-hidden="true" className="size-4" />
                    Ontdek de projecten
                </PublicExternalLink>
            </div>
        </section>
    );
}
