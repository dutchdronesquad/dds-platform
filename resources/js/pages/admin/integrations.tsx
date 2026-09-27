import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Route } from 'lucide-react';
import IntegrationController from '@/actions/App/Http/Controllers/Admin/IntegrationController';
import { index as trackDrawSettings } from '@/actions/App/Http/Controllers/Admin/TrackDrawConnectionController';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

export default function Integrations({
    trackDrawConnectionCount,
}: {
    trackDrawConnectionCount: number;
}) {
    return (
        <>
            <Head title="Integraties" />
            <AdminResourcePage
                eyebrow="Instellingen"
                title="Integraties"
                description="Verbind externe diensten met het platform en beheer de accounts die je gebruikt."
            >
                <article className="max-w-xl rounded-xl border border-sidebar-border/70 bg-white p-6 shadow-xs dark:border-sidebar-border dark:bg-neutral-950">
                    <div className="flex items-start justify-between gap-4">
                        <span className="flex size-12 items-center justify-center rounded-xl bg-signal-50 text-signal-700 dark:bg-signal-500/10 dark:text-signal-300">
                            <Route className="size-6" />
                        </span>
                        <Badge variant="outline">Eventbanen</Badge>
                    </div>
                    <h2 className="mt-5 text-lg font-semibold">TrackDraw</h2>
                    <p className="mt-2 text-sm leading-6 text-muted-foreground">
                        Haal banen uit TrackDraw op en laat bezoekers je
                        eventbaan in 2D en 3D verkennen.
                    </p>
                    <div className="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-sidebar-border/70 pt-4">
                        <span className="text-sm text-muted-foreground">
                            {trackDrawConnectionCount === 0
                                ? 'Nog geen accounts gekoppeld'
                                : `${trackDrawConnectionCount} ${trackDrawConnectionCount === 1 ? 'koppeling' : 'koppelingen'}`}
                        </span>
                        <Button asChild variant="outline">
                            <Link href={trackDrawSettings()}>
                                {trackDrawConnectionCount === 0
                                    ? 'TrackDraw instellen'
                                    : 'Koppelingen beheren'}
                                <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                </article>
            </AdminResourcePage>
        </>
    );
}

Integrations.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Integraties', href: IntegrationController() },
    ],
};
