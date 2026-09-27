import { useEffect, useRef, useState } from 'react';
import { track } from '@/routes/events';

export default function EventTrackViewer({
    slug,
    title,
    revision,
    initialView = '2d',
}: {
    slug: string;
    title: string;
    revision?: string;
    initialView?: '2d' | '3d';
}) {
    const container = useRef<HTMLDivElement>(null);
    const [status, setStatus] = useState<'loading' | 'ready' | 'error'>(
        'loading',
    );

    useEffect(() => {
        const controller = new AbortController();
        let dispose: (() => void) | undefined;
        setStatus('loading');
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
                    response,
                ] = await Promise.all([
                    import('@trackdraw/viewer/mount'),
                    import('@trackdraw/viewer/snapshot/api'),
                    import('@trackdraw/viewer/snapshot/version'),
                    import('@trackdraw/viewer/snapshot/identity'),
                    fetch(track.url({ event: slug }), {
                        signal: controller.signal,
                        credentials: 'same-origin',
                    }),
                    import('@trackdraw/viewer/static/trackdraw-viewer.css'),
                ]);
                if (!response.ok) throw new Error('Unavailable');
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
                if (controller.signal.aborted || !container.current) return;
                const viewer = createTrackDrawViewer(container.current, {
                    design: snapshot.design,
                    theme: 'light',
                    initialView,
                    labels: {
                        viewerPanZoom:
                            'Sleep om te bewegen, scroll om te zoomen',
                        fitToWindow: 'Baan passend maken',
                        grid: (label) => `Raster: ${label}`,
                    },
                });
                dispose = () => viewer.destroy();
                setStatus('ready');
            } catch {
                if (!controller.signal.aborted) setStatus('error');
            }
        }
        void load();
        return () => {
            controller.abort();
            dispose?.();
        };
    }, [slug, revision, initialView]);

    return (
        <section
            aria-label={`Track: ${title}`}
            className="dds-track-viewer min-w-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
        >
            <div className="border-b border-neutral-200 px-5 py-4 sm:px-6 dark:border-neutral-800">
                <h3 className="text-lg font-semibold text-neutral-950 dark:text-white">
                    {title}
                </h3>
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
            <p className="border-t border-neutral-200 px-5 py-3 text-xs leading-5 text-neutral-500 sm:px-6 dark:border-neutral-800 dark:text-neutral-400">
                Sleep om de track te verkennen. Scroll of knijp met twee vingers
                om te zoomen.
            </p>
        </section>
    );
}
