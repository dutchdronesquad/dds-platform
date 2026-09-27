import { Link } from '@inertiajs/react';
import { Flag, RefreshCw, Unplug } from 'lucide-react';
import { useEffect, useState } from 'react';
import EventTrackProjectController from '@/actions/App/Http/Controllers/Admin/EventTrackProjectController';
import { index as connectionSettings } from '@/actions/App/Http/Controllers/Admin/TrackDrawConnectionController';
import { AdminFormSection } from '@/components/admin/admin-form';
import EventTrackViewer from '@/components/public/event-track-viewer';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { EditableEvent } from './types';

type CloudProject = { id: string; title: string };

export function EventTrackForm({
    event,
    resetKey,
    active,
    errors,
    processing,
}: {
    event: EditableEvent;
    resetKey: number;
    active: boolean;
    errors: Record<string, string>;
    processing: boolean;
}) {
    const initialConnection = String(
        event.track.connectionId ??
            (event.track.connections.length === 1
                ? event.track.connections[0].id
                : ''),
    );
    const [connectionId, setConnectionId] = useState(initialConnection);
    const [projectId, setProjectId] = useState(event.track.projectId ?? '');
    const [action, setAction] = useState<'keep' | 'replace' | 'remove'>('keep');
    const [projects, setProjects] = useState<CloudProject[]>([]);
    const [loadedRequest, setLoadedRequest] = useState<string | null>(null);
    const [loadError, setLoadError] = useState<string | null>(null);
    const [reload, setReload] = useState(0);
    const revision = `${resetKey}:${event.track.connectionId}:${event.track.projectId}:${event.track.syncedAt}`;
    const [savedRevision, setSavedRevision] = useState(revision);
    if (savedRevision !== revision) {
        setSavedRevision(revision);
        setConnectionId(initialConnection);
        setProjectId(event.track.projectId ?? '');
        setAction('keep');
    }

    const requestKey = `${connectionId}:${reload}`;
    const loading = Boolean(connectionId) && loadedRequest !== requestKey;

    useEffect(() => {
        if (!active || !connectionId || loadedRequest === requestKey) {
            return;
        }
        const controller = new AbortController();

        async function loadProjects() {
            try {
                const allProjects = new Map<string, CloudProject>();
                const cursors = new Set<string>();
                let cursor: string | null = null;
                do {
                    const response = await fetch(
                        EventTrackProjectController.url(event.id, {
                            query: { connection_id: connectionId, cursor },
                        }),
                        {
                            headers: { Accept: 'application/json' },
                            signal: controller.signal,
                        },
                    );
                    if (!response.ok) {
                        throw new Error('Projects unavailable');
                    }
                    const page: {
                        projects: CloudProject[];
                        nextCursor: string | null;
                    } = await response.json();
                    page.projects.forEach((project) =>
                        allProjects.set(project.id, project),
                    );
                    cursor = page.nextCursor;
                    if (cursor && cursors.has(cursor)) {
                        throw new Error('Repeated cursor');
                    }
                    if (cursor) {
                        cursors.add(cursor);
                    }
                } while (cursor);
                if (!controller.signal.aborted) {
                    setProjects(
                        [...allProjects.values()].sort((a, b) =>
                            a.title.localeCompare(b.title, 'nl'),
                        ),
                    );
                }
            } catch {
                if (!controller.signal.aborted) {
                    setLoadError(
                        'De cloudprojecten konden niet worden opgehaald. Controleer de koppeling en probeer opnieuw.',
                    );
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoadedRequest(requestKey);
                }
            }
        }
        void loadProjects();
        return () => controller.abort();
    }, [active, connectionId, event.id, loadedRequest, requestKey]);

    const projectAvailable = projects.some(
        (project) => project.id === projectId,
    );

    return (
        <>
            <input type="hidden" name="track_action" value={action} />
            <input
                type="hidden"
                name="track_connection_id"
                value={action === 'replace' ? connectionId : ''}
            />
            <input
                type="hidden"
                name="track_project_id"
                value={action === 'replace' ? projectId : ''}
            />
            <AdminFormSection
                id="event-track"
                icon={Flag}
                title="Track"
                description="Kies een cloudproject uit TrackDraw. Je keuze wordt samen met de overige eventinstellingen opgeslagen."
            >
                <div className="grid gap-5 @min-[40rem]/event-main:grid-cols-2">
                    <div className="grid content-start gap-2">
                        <Label htmlFor="track-connection">
                            TrackDraw-koppeling
                        </Label>
                        <Select
                            value={connectionId}
                            onValueChange={(value) => {
                                setConnectionId(value);
                                setProjectId('');
                                setProjects([]);
                                setLoadedRequest(null);
                                setLoadError(null);
                                setAction('replace');
                            }}
                            disabled={
                                processing ||
                                event.track.connections.length === 0
                            }
                        >
                            <SelectTrigger
                                id="track-connection"
                                className="w-full"
                                aria-invalid={Boolean(
                                    errors.track_connection_id,
                                )}
                                aria-describedby="track-connection-error"
                            >
                                <SelectValue placeholder="Kies een koppeling" />
                            </SelectTrigger>
                            <SelectContent>
                                {event.track.connections.map((connection) => (
                                    <SelectItem
                                        key={connection.id}
                                        value={String(connection.id)}
                                    >
                                        {connection.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError
                            id="track-connection-error"
                            message={errors.track_connection_id}
                        />
                        {event.track.canManageConnection && (
                            <Link
                                href={connectionSettings()}
                                className="w-fit text-sm text-signal-700 hover:underline dark:text-signal-300"
                            >
                                Koppelingen beheren
                            </Link>
                        )}
                    </div>
                    <div className="grid content-start gap-2">
                        <Label htmlFor="track-project">Cloudproject</Label>
                        <Select
                            value={projectId}
                            onValueChange={(value) => {
                                setProjectId(value);
                                setAction(
                                    connectionId ===
                                        String(event.track.connectionId) &&
                                        value === event.track.projectId
                                        ? 'keep'
                                        : 'replace',
                                );
                            }}
                            disabled={
                                processing ||
                                loading ||
                                !connectionId ||
                                Boolean(loadError) ||
                                projects.length === 0
                            }
                        >
                            <SelectTrigger
                                id="track-project"
                                className="w-full"
                                aria-invalid={Boolean(errors.track_project_id)}
                                aria-describedby="track-project-status track-project-error"
                            >
                                <SelectValue
                                    placeholder={
                                        loading
                                            ? 'Projecten ophalen…'
                                            : 'Kies een project'
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {projectId && !projectAvailable && (
                                    <SelectItem value={projectId} disabled>
                                        {event.track.title ??
                                            'Project niet beschikbaar'}
                                    </SelectItem>
                                )}
                                {projects.map((project) => (
                                    <SelectItem
                                        key={project.id}
                                        value={project.id}
                                    >
                                        {project.title}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError
                            id="track-project-error"
                            message={errors.track_project_id}
                        />
                        <div
                            id="track-project-status"
                            aria-live="polite"
                            className="text-sm text-muted-foreground"
                        >
                            {loading ? (
                                'Cloudprojecten ophalen…'
                            ) : loadError ? (
                                <span role="alert">{loadError}</span>
                            ) : !connectionId ? (
                                'Kies eerst een koppeling.'
                            ) : projects.length === 0 ? (
                                'Geen cloudprojecten gevonden. Sla je track eerst op in TrackDraw.'
                            ) : projectId && !projectAvailable ? (
                                'Dit project is niet meer beschikbaar. De opgeslagen track blijft behouden.'
                            ) : (
                                `${projects.length} ${projects.length === 1 ? 'cloudproject' : 'cloudprojecten'} beschikbaar.`
                            )}
                        </div>
                        {connectionId && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="-ml-3 w-fit"
                                disabled={loading || processing}
                                onClick={() => {
                                    setLoadError(null);
                                    setReload((value) => value + 1);
                                }}
                            >
                                <RefreshCw />
                                {loadError
                                    ? 'Opnieuw proberen'
                                    : 'Projecten vernieuwen'}
                            </Button>
                        )}
                    </div>
                </div>
                <div className="grid max-w-sm gap-2">
                    <Label htmlFor="track-default-view">
                        Standaardweergave
                    </Label>
                    <Select
                        name="trackdraw_default_view"
                        defaultValue={event.track.defaultView}
                        disabled={processing}
                    >
                        <SelectTrigger
                            id="track-default-view"
                            className="w-full"
                            aria-describedby="track-default-view-hint track-default-view-error"
                            aria-invalid={Boolean(
                                errors.trackdraw_default_view,
                            )}
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="2d">
                                2D — bovenaanzicht
                            </SelectItem>
                            <SelectItem value="3d">3D — perspectief</SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        id="track-default-view-hint"
                        className="text-sm text-muted-foreground"
                    >
                        Hiermee opent de track voor bezoekers. Ze kunnen zelf
                        wisselen tussen 2D en 3D.
                    </p>
                    <InputError
                        id="track-default-view-error"
                        message={errors.trackdraw_default_view}
                    />
                </div>
                {event.track.connections.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        Laat een beheerder een TrackDraw-koppeling toevoegen om
                        een cloudproject te kiezen.
                    </p>
                )}
                {action !== 'keep' && (
                    <div
                        role="status"
                        className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                    >
                        <p>
                            {action === 'remove'
                                ? 'De track wordt losgekoppeld wanneer je het event opslaat.'
                                : 'De gekozen track wordt opgehaald wanneer je het event opslaat.'}
                        </p>
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            disabled={processing}
                            onClick={() => {
                                setConnectionId(initialConnection);
                                setProjectId(event.track.projectId ?? '');
                                setAction('keep');
                            }}
                        >
                            Ongedaan maken
                        </Button>
                    </div>
                )}
            </AdminFormSection>
            <AdminFormSection
                title="Opgeslagen track"
                description={
                    event.track.title
                        ? 'Dit is de versie die bezoekers bij het event zien.'
                        : 'Kies hierboven een project en sla het event op om een track toe te voegen.'
                }
                compact
            >
                {event.track.title ? (
                    <>
                        {active && (
                            <EventTrackViewer
                                slug={event.slug}
                                initialView={event.track.defaultView}
                                title={event.track.title}
                                revision={event.track.syncedAt ?? undefined}
                            />
                        )}
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <p className="text-sm text-muted-foreground">
                                Laatst opgehaald:{' '}
                                {event.track.syncedAt
                                    ? new Date(
                                          event.track.syncedAt,
                                      ).toLocaleString('nl-NL')
                                    : 'onbekend'}
                            </p>
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    disabled={
                                        processing || !event.track.connectionId
                                    }
                                    onClick={() => {
                                        setConnectionId(
                                            String(event.track.connectionId),
                                        );
                                        setProjectId(
                                            event.track.projectId ?? '',
                                        );
                                        setAction('replace');
                                    }}
                                >
                                    <RefreshCw />
                                    Track verversen
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    disabled={processing || action === 'remove'}
                                    onClick={() => setAction('remove')}
                                >
                                    <Unplug />
                                    Loskoppelen
                                </Button>
                            </div>
                        </div>
                    </>
                ) : (
                    <div className="flex items-center gap-3 rounded-xl border border-dashed p-5 text-sm text-muted-foreground">
                        <Flag className="size-5" />
                        <span>Nog geen track gekoppeld aan dit event.</span>
                    </div>
                )}
            </AdminFormSection>
        </>
    );
}
