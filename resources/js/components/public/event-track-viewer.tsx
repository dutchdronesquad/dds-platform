import type { ViewerView, ViewerViewState } from '@trackdraw/viewer';
import { useEffect, useRef, useState } from 'react';
import { track } from '@/routes/events';

type Props = {
    slug: string;
    title: string;
    revision?: string;
    initialView?: ViewerView;
};

export default function EventTrackViewer(props: Props) {
    return (
        <EventTrackViewerContent
            key={JSON.stringify([
                props.slug,
                props.revision,
                props.initialView,
            ])}
            {...props}
        />
    );
}

function EventTrackViewerContent({
    slug,
    title,
    revision,
    initialView = '2d',
}: Props) {
    const container = useRef<HTMLDivElement>(null);
    const changeView = useRef<((view: ViewerView) => void) | undefined>(
        undefined,
    );
    const [viewState, setViewState] = useState<ViewerViewState>({
        view: initialView,
        available3D: false,
    });
    const [status, setStatus] = useState<'loading' | 'ready' | 'error'>(
        'loading',
    );

    useEffect(() => {
        const controller = new AbortController();
        let dispose: (() => void) | undefined;
        async function load() {
            try {
                const [
                    { createTrackDrawViewer },
                    { viewerSnapshotFromApi },
                    {
                        isViewerCompatible,
                        RENDERER_VERSION,
                        RENDERER_CAPABILITIES,
                    },
                    { getViewerSnapshotId },
                    { createAssetResolver, OBSTACLE_ASSETS_URL },
                    response,
                ] = await Promise.all([
                    import('@trackdraw/viewer/mount'),
                    import('@trackdraw/viewer/snapshot/api'),
                    import('@trackdraw/viewer/snapshot/version'),
                    import('@trackdraw/viewer/snapshot/identity'),
                    import('@trackdraw/viewer/assets/asset-url'),
                    fetch(track.url({ event: slug }), {
                        signal: controller.signal,
                        credentials: 'same-origin',
                    }),
                ]);
                if (!response.ok) {
                    throw new Error('Unavailable');
                }
                const snapshot = viewerSnapshotFromApi(await response.json());
                if (
                    !isViewerCompatible(snapshot.requiredViewer, {
                        rendererVersion: RENDERER_VERSION,
                        capabilities: new Set(RENDERER_CAPABILITIES),
                    }) ||
                    snapshot.snapshotId !== getViewerSnapshotId(snapshot)
                ) {
                    throw new Error('Unsupported or invalid course');
                }
                if (controller.signal.aborted || !container.current) {
                    return;
                }
                const resolveAsset = createAssetResolver();
                const options: Parameters<typeof createTrackDrawViewer>[1] = {
                    design: snapshot.design,
                    assetResolver: (path) =>
                        resolveAsset(path).replace(
                            `${OBSTACLE_ASSETS_URL}/`,
                            'https://assets.trackdraw.app/',
                        ),
                    theme: 'light',
                    initialView,
                    showViewControls: false,
                    onViewStateChange: setViewState,
                    labels: {
                        viewerPanZoom:
                            'Sleep om te bewegen, scroll om te zoomen',
                        fitToWindow: 'Baan passend maken',
                        grid: (label) => `Raster: ${label}`,
                    },
                };
                const viewer = createTrackDrawViewer(
                    container.current,
                    options,
                );
                changeView.current = (view) =>
                    viewer.update({ ...options, view });
                dispose = () => viewer.destroy();
                setStatus('ready');
            } catch {
                if (!controller.signal.aborted) {
                    setStatus('error');
                }
            }
        }
        void load();
        return () => {
            changeView.current = undefined;
            controller.abort();
            dispose?.();
        };
    }, [slug, revision, initialView]);

    return (
        <section
            aria-label={`Track: ${title}`}
            className="dds-track-viewer min-w-0 overflow-hidden rounded-[0.25rem] border border-paddock-rule bg-white dark:border-white/12 dark:bg-night-900"
        >
            <div className="flex flex-wrap items-center justify-between gap-4 border-b border-paddock-rule bg-paddock px-5 py-3 sm:px-6 dark:border-white/12 dark:bg-night-900">
                <h3 className="text-lg font-semibold text-ink dark:text-white">
                    {title}
                </h3>
                {status === 'ready' && viewState.available3D && (
                    <div
                        role="group"
                        aria-label="Trackweergave"
                        className="flex shrink-0 gap-0.5 rounded-[0.25rem] border border-paddock-rule bg-white/60 p-0.5 dark:border-white/20 dark:bg-night-950"
                    >
                        {(['2d', '3d'] as const).map((view) => (
                            <button
                                key={view}
                                type="button"
                                aria-pressed={viewState.view === view}
                                onClick={() => changeView.current?.(view)}
                                className="min-h-8 min-w-10 rounded-[0.125rem] px-3 py-1 text-xs font-semibold text-ink transition-colors hover:bg-paddock focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-signal-500 aria-pressed:bg-flight-500 aria-pressed:text-ink dark:text-white dark:hover:bg-night-800 dark:aria-pressed:text-ink"
                            >
                                {view.toUpperCase()}
                            </button>
                        ))}
                    </div>
                )}
            </div>
            {status === 'loading' && (
                <p role="status" className="px-5 py-4 text-sm text-neutral-500">
                    Track laden…
                </p>
            )}
            {status === 'error' && (
                <p role="alert" className="px-5 py-4 text-sm">
                    De baan kan momenteel niet worden weergegeven. Probeer de
                    pagina opnieuw te laden.
                </p>
            )}
            <div
                ref={container}
                className="h-[380px] w-full overflow-hidden bg-white sm:h-[540px]"
            />
            <p className="dark:text-night-300 border-t border-paddock-rule px-5 py-3 text-xs leading-5 text-signal-muted sm:px-6 dark:border-white/12">
                Sleep om de track te verkennen. Scroll of knijp met twee vingers
                om te zoomen.
            </p>
        </section>
    );
}
