import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

type MutationForm = {
    action: string;
    method: 'delete' | 'patch' | 'post' | 'put';
};

type ConfirmationIntent =
    | 'archive'
    | 'block'
    | 'cancel'
    | 'delete'
    | 'publish'
    | 'unblock'
    | 'unpublish';

const confirmationCopy: Record<
    ConfirmationIntent,
    {
        confirmLabel: string;
        description: (subject: string) => string;
        title: string;
        variant: 'default' | 'destructive';
    }
> = {
    archive: {
        confirmLabel: 'Archiveren',
        description: (subject) =>
            `${subject} verdwijnt uit de actieve overzichten, maar blijft bewaard.`,
        title: 'Archiveren?',
        variant: 'destructive',
    },
    block: {
        confirmLabel: 'Account blokkeren',
        description: (subject) =>
            `${subject} wordt direct uitgelogd en kan niet meer aanmelden totdat het account wordt gedeblokkeerd.`,
        title: 'Account blokkeren?',
        variant: 'destructive',
    },
    cancel: {
        confirmLabel: 'Event annuleren',
        description: (subject) =>
            `${subject} blijft zichtbaar als geannuleerd event. Bezoekers kunnen zich niet meer inschrijven.`,
        title: 'Event annuleren?',
        variant: 'destructive',
    },
    delete: {
        confirmLabel: 'Definitief verwijderen',
        description: (subject) =>
            `${subject} wordt definitief verwijderd. Dit kan niet ongedaan worden gemaakt.`,
        title: 'Definitief verwijderen?',
        variant: 'destructive',
    },
    publish: {
        confirmLabel: 'Publiceren',
        description: (subject) =>
            `${subject} wordt zichtbaar op de publieke website.`,
        title: 'Publiceren?',
        variant: 'default',
    },
    unblock: {
        confirmLabel: 'Account deblokkeren',
        description: (subject) =>
            `${subject} kan na het deblokkeren direct weer aanmelden.`,
        title: 'Account deblokkeren?',
        variant: 'default',
    },
    unpublish: {
        confirmLabel: 'Publicatie intrekken',
        description: (subject) =>
            `${subject} is daarna niet meer zichtbaar op de publieke website.`,
        title: 'Publicatie intrekken?',
        variant: 'destructive',
    },
};

type AdminConfirmationDialogProps = {
    description?: string;
    form: MutationForm;
    intent: ConfirmationIntent;
    onOpenChange?: (open: boolean) => void;
    open?: boolean;
    subject: string;
    trigger?: ReactNode;
};

export function AdminConfirmationDialog({
    description,
    form,
    intent,
    onOpenChange,
    open: controlledOpen,
    subject,
    trigger,
}: AdminConfirmationDialogProps) {
    const [uncontrolledOpen, setUncontrolledOpen] = useState(false);
    const copy = confirmationCopy[intent];
    const open = controlledOpen ?? uncontrolledOpen;

    function setOpen(nextOpen: boolean): void {
        if (controlledOpen === undefined) {
            setUncontrolledOpen(nextOpen);
        }

        onOpenChange?.(nextOpen);
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            {trigger && <DialogTrigger asChild>{trigger}</DialogTrigger>}
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{copy.title}</DialogTitle>
                    <DialogDescription>
                        {description ?? copy.description(subject)}
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <DialogClose asChild>
                        <Button type="button" variant="outline">
                            Annuleren
                        </Button>
                    </DialogClose>
                    <Form
                        {...form}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setOpen(false)}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant={copy.variant}
                                disabled={processing}
                            >
                                {processing ? 'Bezig…' : copy.confirmLabel}
                            </Button>
                        )}
                    </Form>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
