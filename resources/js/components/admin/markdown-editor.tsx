import { useHttp } from '@inertiajs/react';
import { Eye, LoaderCircle, Pencil } from 'lucide-react';
import { useState } from 'react';
import MarkdownPreviewController from '@/actions/App/Http/Controllers/Admin/MarkdownPreviewController';
import MarkdownContent from '@/components/public/markdown-content';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type MarkdownPreviewResponse = {
    html: string | null;
};

type MarkdownEditorProps = {
    'aria-describedby'?: string;
    'aria-invalid'?: boolean;
    defaultValue?: string;
    id: string;
    maxLength: number;
    name: string;
    placeholder?: string;
    rows?: number;
};

export function MarkdownEditor({
    'aria-describedby': ariaDescribedBy,
    'aria-invalid': ariaInvalid,
    defaultValue = '',
    id,
    maxLength,
    name,
    placeholder,
    rows = 8,
}: MarkdownEditorProps) {
    const [mode, setMode] = useState<'edit' | 'preview'>('edit');
    const preview = useHttp<{ markdown: string }, MarkdownPreviewResponse>(
        MarkdownPreviewController(),
        {
            markdown: defaultValue,
        },
    );
    const previewId = `${id}-preview`;

    function showPreview(): void {
        setMode('preview');
        void preview.submit();
    }

    return (
        <div
            data-invalid={ariaInvalid || undefined}
            className="overflow-hidden rounded-md border border-input bg-background shadow-xs focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50 data-[invalid=true]:border-destructive data-[invalid=true]:ring-destructive/20 dark:bg-input/30"
        >
            <div
                role="group"
                aria-label="Markdownweergave"
                className="flex gap-1 border-b border-border bg-muted/40 p-1"
            >
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    aria-controls={`${id}-editor`}
                    aria-pressed={mode === 'edit'}
                    onClick={() => setMode('edit')}
                    className={cn(
                        'h-8',
                        mode === 'edit' && 'bg-background shadow-xs',
                    )}
                >
                    <Pencil />
                    Schrijven
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    aria-controls={previewId}
                    aria-pressed={mode === 'preview'}
                    onClick={showPreview}
                    className={cn(
                        'h-8',
                        mode === 'preview' && 'bg-background shadow-xs',
                    )}
                >
                    {preview.processing ? (
                        <LoaderCircle className="animate-spin" />
                    ) : (
                        <Eye />
                    )}
                    Voorbeeld
                </Button>
            </div>
            <div
                id={`${id}-editor`}
                className={mode === 'edit' ? undefined : 'hidden'}
            >
                <textarea
                    id={id}
                    name={name}
                    value={preview.data.markdown}
                    onChange={(event) =>
                        preview.setData('markdown', event.target.value)
                    }
                    rows={rows}
                    maxLength={maxLength}
                    placeholder={placeholder}
                    aria-invalid={ariaInvalid}
                    aria-describedby={ariaDescribedBy}
                    className="min-h-36 w-full resize-y border-0 bg-transparent px-3 py-3 text-sm outline-none placeholder:text-muted-foreground focus-visible:ring-0"
                />
            </div>
            <div
                id={previewId}
                aria-live="polite"
                className={cn(
                    'min-h-36 px-4 py-3',
                    mode === 'edit' && 'hidden',
                )}
            >
                {preview.processing ? (
                    <p className="text-sm text-muted-foreground">
                        Voorbeeld laden…
                    </p>
                ) : preview.errors.markdown ? (
                    <p className="text-sm text-destructive">
                        {preview.errors.markdown}
                    </p>
                ) : (
                    <MarkdownContent
                        html={preview.response?.html ?? null}
                        fallback="Vul een omschrijving in om het voorbeeld te bekijken."
                        className="text-sm leading-6 sm:text-sm"
                    />
                )}
            </div>
        </div>
    );
}
