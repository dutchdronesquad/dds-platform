import { Form, Link } from '@inertiajs/react';
import {
    destroy,
    update,
} from '@/actions/App/Http/Controllers/Admin/EventTrackController';
import { index as connectionSettings } from '@/actions/App/Http/Controllers/Admin/TrackDrawConnectionController';
import EventTrackViewer from '@/components/public/event-track-viewer';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { EditableEvent } from './types';

export function EventTrackForm({ event }: { event: EditableEvent }) {
    return (
        <section
            className="mx-auto mt-8 w-full space-y-5 rounded-xl border p-6"
            aria-labelledby="event-track-heading"
        >
            <div>
                <h2 id="event-track-heading" className="text-lg font-semibold">
                    TrackDraw-baan
                </h2>
                <p className="mt-2 text-sm text-muted-foreground">
                    Kies een opgeslagen TrackDraw-koppeling en het project. De
                    opgeslagen baan verandert alleen als je deze opnieuw
                    ophaalt. Op een gepubliceerd event is de baan direct
                    zichtbaar.
                </p>
            </div>
            {event.track.connections.length === 0 && (
                <p className="text-sm text-muted-foreground">
                    Laat een beheerder eerst een TrackDraw-koppeling toevoegen.
                    Opgeslagen banen blijven zichtbaar.
                </p>
            )}
            {event.track.canManageConnection && (
                <Link href={connectionSettings()} className="text-sm underline">
                    TrackDraw-koppelingen beheren
                </Link>
            )}
            <Form
                {...update.form(event.id)}
                setDefaultsOnSuccess
                className="grid gap-4"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="track-connection">
                                TrackDraw-koppeling
                            </Label>
                            <Select
                                name="connection_id"
                                defaultValue={String(
                                    event.track.connectionId ??
                                        (event.track.connections.length === 1
                                            ? event.track.connections[0].id
                                            : ''),
                                )}
                                required
                            >
                                <SelectTrigger
                                    id="track-connection"
                                    className="w-full"
                                    aria-invalid={Boolean(errors.connection_id)}
                                    aria-describedby="track-connection-error"
                                >
                                    <SelectValue placeholder="Kies een koppeling" />
                                </SelectTrigger>
                                <SelectContent>
                                    {event.track.connections.map(
                                        (connection) => (
                                            <SelectItem
                                                key={connection.id}
                                                value={String(connection.id)}
                                            >
                                                {connection.name}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                            <InputError
                                id="track-connection-error"
                                message={errors.connection_id}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="track-project">Project-ID</Label>
                            <Input
                                id="track-project"
                                name="project_id"
                                defaultValue={event.track.projectId ?? ''}
                                required
                                maxLength={255}
                                aria-invalid={Boolean(errors.project_id)}
                                aria-describedby="track-project-error"
                            />
                            <p className="text-sm text-muted-foreground">
                                Het project-ID uit de URL van je
                                TrackDraw-project.
                            </p>
                            <InputError
                                id="track-project-error"
                                message={errors.project_id}
                            />
                        </div>
                        <Button
                            type="submit"
                            disabled={
                                processing ||
                                event.track.connections.length === 0
                            }
                            className="w-fit"
                        >
                            {processing
                                ? 'Baan ophalen…'
                                : event.track.title
                                  ? 'Baan verversen'
                                  : 'Baan koppelen'}
                        </Button>
                    </>
                )}
            </Form>
            {event.track.title && (
                <>
                    <p className="text-sm text-muted-foreground">
                        Opgehaald:{' '}
                        {event.track.syncedAt
                            ? new Date(event.track.syncedAt).toLocaleString(
                                  'nl-NL',
                              )
                            : 'onbekend'}
                    </p>
                    <EventTrackViewer
                        slug={event.slug}
                        title={event.track.title}
                        revision={event.track.syncedAt ?? undefined}
                    />
                    <Form {...destroy.form(event.id)}>
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={processing}
                            >
                                Baan loskoppelen
                            </Button>
                        )}
                    </Form>
                </>
            )}
        </section>
    );
}
