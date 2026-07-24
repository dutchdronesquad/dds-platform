import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarClock,
    Mail,
    MessageSquareText,
} from 'lucide-react';
import { index } from '@/actions/App/Http/Controllers/Admin/ContactController';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import type { ContactSubmissionDetail } from './types';

type Props = {
    contactSubmission: ContactSubmissionDetail;
};

const dateFormatter = new Intl.DateTimeFormat('nl-NL', {
    dateStyle: 'long',
    timeStyle: 'short',
});

export default function ContactShow({ contactSubmission }: Props) {
    const needsFollowUp = ['failed', 'not_configured'].includes(
        contactSubmission.deliveryStatus,
    );

    return (
        <>
            <Head title={`Contactaanvraag van ${contactSubmission.name}`} />
            <AdminResourcePage
                eyebrow="Contactaanvraag"
                title={contactSubmission.name}
                description={`${contactSubmission.topicLabel} · ontvangen ${dateFormatter.format(new Date(contactSubmission.createdAt))}`}
                actions={
                    <Button asChild variant="outline">
                        <Link href={index()}>
                            <ArrowLeft /> Terug naar overzicht
                        </Link>
                    </Button>
                }
            >
                <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                    <article className="rounded-xl border border-sidebar-border/70 bg-white p-5 shadow-xs sm:p-7 dark:border-sidebar-border dark:bg-neutral-950">
                        <div className="flex items-center gap-3">
                            <span className="flex size-10 items-center justify-center rounded-lg bg-flight-50 text-flight-700 dark:bg-flight-500/10 dark:text-flight-300">
                                <MessageSquareText className="size-5" />
                            </span>
                            <div>
                                <p className="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                    Bericht
                                </p>
                                <h2 className="font-semibold text-neutral-950 dark:text-white">
                                    {contactSubmission.topicLabel}
                                </h2>
                            </div>
                        </div>
                        <p className="mt-6 text-sm leading-7 whitespace-pre-wrap text-neutral-700 sm:text-base dark:text-neutral-300">
                            {contactSubmission.message}
                        </p>
                        <Button asChild className="mt-7">
                            <a href={`mailto:${contactSubmission.email}`}>
                                <Mail /> Beantwoord per e-mail
                            </a>
                        </Button>
                    </article>

                    <aside className="grid content-start gap-4">
                        <section className="rounded-xl border border-sidebar-border/70 bg-white p-5 shadow-xs dark:border-sidebar-border dark:bg-neutral-950">
                            <h2 className="font-semibold text-neutral-950 dark:text-white">
                                Contactgegevens
                            </h2>
                            <dl className="mt-4 grid gap-4 text-sm">
                                <div>
                                    <dt className="text-neutral-500 dark:text-neutral-400">
                                        Naam
                                    </dt>
                                    <dd className="mt-1 font-medium text-neutral-950 dark:text-white">
                                        {contactSubmission.name}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-neutral-500 dark:text-neutral-400">
                                        E-mail
                                    </dt>
                                    <dd className="mt-1 break-all">
                                        <a
                                            href={`mailto:${contactSubmission.email}`}
                                            className="font-medium text-flight-700 hover:underline dark:text-flight-300"
                                        >
                                            {contactSubmission.email}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-neutral-500 dark:text-neutral-400">
                                        Bron
                                    </dt>
                                    <dd className="mt-1 text-neutral-700 dark:text-neutral-300">
                                        {contactSubmission.sourceContext ??
                                            'Direct contactformulier'}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section className="rounded-xl border border-sidebar-border/70 bg-white p-5 shadow-xs dark:border-sidebar-border dark:bg-neutral-950">
                            <div className="flex items-center gap-2">
                                <CalendarClock className="size-4 text-neutral-500" />
                                <h2 className="font-semibold text-neutral-950 dark:text-white">
                                    Verwerking
                                </h2>
                            </div>
                            <div className="mt-4">
                                <Badge
                                    variant={
                                        needsFollowUp
                                            ? 'destructive'
                                            : 'secondary'
                                    }
                                >
                                    {contactSubmission.deliveryStatusLabel}
                                </Badge>
                            </div>
                            {contactSubmission.deliveryError && (
                                <p className="mt-4 rounded-lg bg-amber-50 p-3 text-sm leading-6 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                                    {contactSubmission.deliveryError}
                                </p>
                            )}
                            <dl className="mt-4 grid gap-3 text-sm">
                                <div>
                                    <dt className="text-neutral-500 dark:text-neutral-400">
                                        Toestemming gegeven
                                    </dt>
                                    <dd className="mt-1 text-neutral-700 dark:text-neutral-300">
                                        {dateFormatter.format(
                                            new Date(
                                                contactSubmission.consentedAt,
                                            ),
                                        )}
                                    </dd>
                                </div>
                                {contactSubmission.deliveredAt && (
                                    <div>
                                        <dt className="text-neutral-500 dark:text-neutral-400">
                                            E-mail verzonden
                                        </dt>
                                        <dd className="mt-1 text-neutral-700 dark:text-neutral-300">
                                            {dateFormatter.format(
                                                new Date(
                                                    contactSubmission.deliveredAt,
                                                ),
                                            )}
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </section>
                    </aside>
                </div>
            </AdminResourcePage>
        </>
    );
}

ContactShow.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Contactaanvragen', href: index() },
        { title: 'Contactaanvraag', href: index() },
    ],
};
