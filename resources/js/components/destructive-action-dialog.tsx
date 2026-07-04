import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ComponentProps, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { RouteFormDefinition } from '@/wayfinder';

type DestructiveActionDialogProps = {
    form: RouteFormDefinition<'delete' | 'patch' | 'post'>;
    title: string;
    description: string;
    confirmLabel: string;
    children: ReactNode;
    confirmVariant?: ComponentProps<typeof Button>['variant'];
};

export default function DestructiveActionDialog({
    form,
    title,
    description,
    confirmLabel,
    children,
    confirmVariant = 'destructive',
}: DestructiveActionDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>

                <Form
                    {...form}
                    options={{
                        preserveScroll: true,
                    }}
                    onSuccess={() => setOpen(false)}
                >
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    Cancel
                                </Button>
                            </DialogClose>

                            <Button
                                variant={confirmVariant}
                                disabled={processing}
                                asChild
                            >
                                <button type="submit">{confirmLabel}</button>
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
