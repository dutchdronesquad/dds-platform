import { useEffect, useRef, useState } from 'react';
import { track } from '@/routes/events';

export default function EventTrackViewer({
    slug,
    title,
    revision,
}: {
    slug: string;
    title: string;
    revision?: string;
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
                    initialView: '2d',
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
    }, [slug, revision]);

    return (
        <section aria-label={`Baan: ${title}`} className="min-w-0 space-y-3">
            <h2 className="text-2xl font-semibold">{title}</h2>
            <p className="text-sm text-neutral-500">
                Verken de baan in 2D of 3D.
            </p>
            {status === 'loading' && <p role="status">Baan laden…</p>}
            {status === 'error' && (
                <p role="alert">
                    De baan kan momenteel niet worden weergegeven. Probeer de
                    pagina opnieuw te laden.
                </p>
            )}
            <div
                ref={container}
                className="h-[420px] w-full overflow-hidden rounded-xl border bg-white sm:h-[540px]"
            />
        </section>
    );
}
