import { Form, Link } from '@inertiajs/react';
import {
    ArrowUpRight,
    ExternalLink,
    FileText,
    Save,
    Send,
    Trash2,
    TriangleAlert,
} from 'lucide-react';
import { useState } from 'react';
import {
    destroy,
    index,
} from '@/actions/App/Http/Controllers/Admin/ArticleController';
import { AdminActivityMetadata } from '@/components/admin/admin-activity-metadata';
import { AdminConfirmationDialog } from '@/components/admin/admin-confirmation-dialog';
import {
    AdminFormActions,
    AdminFormErrorSummary,
    AdminFormLayout,
    AdminFormNavigationGuard,
    AdminFormOutline,
    AdminFormSection,
} from '@/components/admin/admin-form';
import { MediaAssetPicker } from '@/components/admin/media-asset-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DateTimePicker } from '@/components/ui/date-time-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { createSlug } from '@/lib/create-slug';
import { show as publicArticleShow } from '@/routes/news';
import type { ArticleFormOptions, EditableArticle, SelectOption } from './types';

type MutationForm = {
    action: string;
    method: 'post';
};

const articleFormOutlineItems = [
    {
        description: 'Titel, slug, categorie en auteur',
        icon: FileText,
        id: 'article-basics',
        title: 'Basisinformatie',
    },
    {
        description: 'De volledige artikeltekst',
        icon: FileText,
        id: 'article-content',
        title: 'Inhoud',
    },
    {
        description: 'Status, publicatiedatum en omslag',
        icon: Send,
        id: 'article-publishing',
        title: 'Publicatie',
    },
];

export function ArticleForm({
    article,
    defaultAuthorId,
    form,
    options,
}: {
    article?: EditableArticle;
    defaultAuthorId?: number;
    form: MutationForm;
    options: ArticleFormOptions;
}) {
    const [title, setTitle] = useState(article?.title ?? '');
    const [slug, setSlug] = useState(article?.slug ?? '');
    const [slugManuallyEdited, setSlugManuallyEdited] = useState(
        Boolean(article?.slug),
    );
    const [coverImage, setCoverImage] = useState(article?.coverImage ?? null);
    const [status, setStatus] = useState<string>(article?.status ?? 'draft');

    return (
        <Form
            {...form}
            className="grid gap-0"
            options={{ preserveScroll: true }}
        >
            {({ errors, isDirty, processing, recentlySuccessful }) => (
                <>
                    <AdminFormNavigationGuard isDirty={isDirty} />
                    <AdminFormActions
                        context={
                            title.trim() ||
                            (article ? 'Artikel bewerken' : 'Nieuw artikel')
                        }
                        isDirty={isDirty}
                        isNew={!article}
                        processing={processing}
                        recentlySuccessful={recentlySuccessful}
                    >
                        <Button asChild type="button" variant="outline">
                            <Link href={index()}>Annuleren</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save />
                            {processing ? (
                                'Opslaan…'
                            ) : article ? (
                                <>
                                    <span className="sm:hidden">Opslaan</span>
                                    <span className="hidden sm:inline">
                                        Wijzigingen opslaan
                                    </span>
                                </>
                            ) : (
                                'Artikel aanmaken'
                            )}
                        </Button>
                    </AdminFormActions>

                    <AdminFormLayout
                        asideFirstOnSmallScreens={false}
                        asideLayoutClassName="@min-[56rem]/admin-page:grid-cols-[minmax(0,1fr)_18.5rem] @min-[84rem]/admin-page:grid-cols-[minmax(0,1fr)_21.5rem]"
                        className="mx-auto w-full"
                        contentClassName="@container/article-main"
                        aside={<ArticleFormAside article={article} />}
                    >
                        <AdminFormErrorSummary errors={errors} />

                        <AdminFormSection
                            id="article-basics"
                            className="@container/fields"
                            icon={FileText}
                            title="Basisinformatie"
                            description="De titel, URL-slug, categorie en auteur van het artikel."
                        >
                            <div className="grid grid-cols-1 gap-5 @min-[46rem]/fields:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                                <FormField
                                    id="title"
                                    label="Titel"
                                    error={errors.title}
                                    reserveSupportingTextSpace
                                >
                                    <Input
                                        id="title"
                                        name="title"
                                        value={title}
                                        onChange={(inputEvent) => {
                                            const nextTitle =
                                                inputEvent.target.value;

                                            setTitle(nextTitle);

                                            if (!slugManuallyEdited) {
                                                setSlug(createSlug(nextTitle));
                                            }
                                        }}
                                        required
                                        maxLength={255}
                                        autoFocus={!article}
                                        autoComplete="off"
                                        placeholder="Bijv. Nieuw seizoen van start"
                                        aria-invalid={Boolean(errors.title)}
                                        aria-describedby={fieldDescription(
                                            'title',
                                            errors.title,
                                        )}
                                    />
                                </FormField>
                                <FormField
                                    id="slug"
                                    label="URL-slug (optioneel)"
                                    error={errors.slug}
                                    hint={
                                        slug
                                            ? `Publieke URL: /news/${slug}`
                                            : undefined
                                    }
                                    reserveSupportingTextSpace
                                >
                                    <Input
                                        id="slug"
                                        name="slug"
                                        value={slug}
                                        onChange={(inputEvent) => {
                                            const nextSlug =
                                                inputEvent.target.value;

                                            setSlug(nextSlug);
                                            setSlugManuallyEdited(
                                                nextSlug !== '' &&
                                                    nextSlug !==
                                                        createSlug(title),
                                            );
                                        }}
                                        maxLength={255}
                                        placeholder="Automatisch uit titel"
                                        autoComplete="off"
                                        autoCapitalize="none"
                                        spellCheck={false}
                                        aria-invalid={Boolean(errors.slug)}
                                        aria-describedby={fieldDescription(
                                            'slug',
                                            errors.slug,
                                        )}
                                    />
                                </FormField>
                            </div>
                            <div className="grid gap-5 @min-[46rem]/fields:grid-cols-2">
                                <FormField
                                    id="category"
                                    label="Categorie"
                                    error={errors.category}
                                >
                                    <FormSelect
                                        id="category"
                                        name="category"
                                        defaultValue={
                                            article?.category ?? 'news'
                                        }
                                        options={options.categories}
                                        required
                                        invalid={Boolean(errors.category)}
                                        describedBy={fieldDescription(
                                            'category',
                                            errors.category,
                                        )}
                                    />
                                </FormField>
                                <FormField
                                    id="author_id"
                                    label="Auteur (optioneel)"
                                    error={errors.author_id}
                                >
                                    <FormSelect
                                        id="author_id"
                                        name="author_id"
                                        defaultValue={
                                            article
                                                ? article.authorId
                                                    ? String(article.authorId)
                                                    : ''
                                                : defaultAuthorId
                                                  ? String(defaultAuthorId)
                                                  : ''
                                        }
                                        options={options.authors.map(
                                            (author) => ({
                                                value: String(author.id),
                                                label: author.label,
                                            }),
                                        )}
                                        placeholder="Geen auteur"
                                        invalid={Boolean(errors.author_id)}
                                        describedBy={fieldDescription(
                                            'author_id',
                                            errors.author_id,
                                        )}
                                    />
                                </FormField>
                            </div>
                        </AdminFormSection>

                        <AdminFormSection
                            id="article-content"
                            className="@container/fields"
                            icon={FileText}
                            title="Inhoud"
                            description="De volledige tekst van het artikel."
                        >
                            <FormField
                                id="content"
                                label="Inhoud"
                                error={errors.content}
                            >
                                <textarea
                                    id="content"
                                    name="content"
                                    defaultValue={article?.content ?? ''}
                                    required
                                    rows={12}
                                    maxLength={50000}
                                    placeholder="Schrijf hier het volledige artikel."
                                    aria-invalid={Boolean(errors.content)}
                                    aria-describedby={fieldDescription(
                                        'content',
                                        errors.content,
                                    )}
                                    className="min-h-64 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:bg-input/30"
                                />
                            </FormField>
                        </AdminFormSection>

                        <AdminFormSection
                            id="article-publishing"
                            className="@container/fields"
                            icon={Send}
                            title="Publicatie"
                            description="Status, publicatiedatum en omslagafbeelding."
                        >
                            <div className="grid gap-5 @min-[46rem]/fields:grid-cols-2">
                                <FormField
                                    id="status"
                                    label="Status"
                                    error={errors.status}
                                    reserveSupportingTextSpace
                                >
                                    <FormSelect
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            article?.status ?? 'draft'
                                        }
                                        options={options.statuses}
                                        required
                                        invalid={Boolean(errors.status)}
                                        describedBy={fieldDescription(
                                            'status',
                                            errors.status,
                                        )}
                                        onValueChange={setStatus}
                                    />
                                </FormField>
                                <FormField
                                    id="published_at"
                                    label="Publicatiedatum (optioneel)"
                                    error={errors.published_at}
                                    hint={
                                        status === 'published'
                                            ? 'Leeg laten publiceert het artikel meteen.'
                                            : undefined
                                    }
                                    reserveSupportingTextSpace
                                    className="max-w-[28rem] @min-[44rem]/fields:max-w-none"
                                >
                                    <DateTimePicker
                                        id="published_at"
                                        name="published_at"
                                        label="Publicatiedatum"
                                        defaultValue={
                                            article?.publishedAt ?? ''
                                        }
                                        aria-invalid={Boolean(
                                            errors.published_at,
                                        )}
                                        aria-describedby={fieldDescription(
                                            'published_at',
                                            errors.published_at,
                                        )}
                                    />
                                </FormField>
                            </div>
                            <FormField
                                id="cover_image_id"
                                label="Omslagafbeelding (optioneel)"
                                error={errors.cover_image_id}
                            >
                                <MediaAssetPicker
                                    id="cover_image_id"
                                    name="cover_image_id"
                                    selected={coverImage}
                                    onChange={setCoverImage}
                                    invalid={Boolean(errors.cover_image_id)}
                                    describedBy={fieldDescription(
                                        'cover_image_id',
                                        errors.cover_image_id,
                                    )}
                                />
                            </FormField>
                        </AdminFormSection>

                        {article?.capabilities.delete && (
                            <AdminFormSection
                                id="article-danger-zone"
                                icon={TriangleAlert}
                                tone="danger"
                                title="Gevarenzone"
                                description="Het verwijderen van een artikel kan niet ongedaan worden gemaakt."
                            >
                                <div>
                                    <AdminConfirmationDialog
                                        form={destroy.form(article.id)}
                                        intent="delete"
                                        subject={article.title}
                                        trigger={
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                            >
                                                <Trash2 />
                                                Artikel verwijderen
                                            </Button>
                                        }
                                    />
                                </div>
                            </AdminFormSection>
                        )}
                    </AdminFormLayout>
                </>
            )}
        </Form>
    );
}

function fieldDescription(id: string, error?: string): string | undefined {
    return error ? `${id}-error` : undefined;
}

function ArticleFormAside({ article }: { article?: EditableArticle }) {
    return (
        <div className="overflow-clip rounded-2xl border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-950">
            <section className="p-5 @min-[84rem]/admin-page:p-6">
                <p className="text-xs font-semibold tracking-[0.14em] text-neutral-500 uppercase">
                    Status
                </p>
                <p className="mt-1 text-sm font-semibold text-neutral-950 dark:text-white">
                    {article
                        ? `${article.status === 'published' ? 'Gepubliceerd' : article.status === 'archived' ? 'Gearchiveerd' : 'Concept'}`
                        : 'Nog niet aangemaakt'}
                </p>
                {article && article.status === 'published' && (
                    <Button
                        asChild
                        variant="outline"
                        className="border-signal-200 text-signal-800 hover:text-signal-900 dark:text-signal-200 mt-4 h-11 w-full justify-start rounded-xl bg-signal-50/70 px-3 shadow-none hover:border-signal-300 hover:bg-signal-100 focus-visible:ring-signal-500/30 dark:border-signal-500/25 dark:bg-signal-500/10 dark:hover:border-signal-500/40 dark:hover:bg-signal-500/15 dark:hover:text-signal-100"
                    >
                        <Link
                            data-sidebar-action="public"
                            href={publicArticleShow(article.slug)}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <ExternalLink />
                            Publieke pagina
                            <ArrowUpRight className="ml-auto size-3.5 opacity-60" />
                            <span className="sr-only">
                                {' '}
                                (opent in een nieuw tabblad)
                            </span>
                        </Link>
                    </Button>
                )}
            </section>
            {article && <AdminActivityMetadata activity={article.activity} />}
            <AdminFormOutline
                description="Spring direct naar een onderdeel van het formulier."
                items={articleFormOutlineItems}
            />
        </div>
    );
}

function FormField({
    children,
    className,
    error,
    hint,
    id,
    label,
    reserveSupportingTextSpace = false,
}: {
    children: React.ReactNode;
    className?: string;
    error?: string;
    hint?: string;
    id: string;
    label: string;
    reserveSupportingTextSpace?: boolean;
}) {
    return (
        <div
            data-field={id}
            className={className ? `grid gap-2 ${className}` : 'grid gap-2'}
        >
            <Label htmlFor={id}>{label}</Label>
            {children}
            {(hint || error || reserveSupportingTextSpace) && (
                <div
                    className={
                        reserveSupportingTextSpace ? 'min-h-10' : 'min-h-5'
                    }
                >
                    {hint && !error && (
                        <p className="text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            {hint}
                        </p>
                    )}
                    <InputError id={`${id}-error`} message={error} />
                </div>
            )}
        </div>
    );
}

const emptySelectValue = '__empty__';

function FormSelect({
    defaultValue,
    describedBy,
    id,
    invalid,
    name,
    onValueChange,
    options,
    placeholder,
    required,
}: {
    defaultValue: string;
    describedBy?: string;
    id: string;
    invalid: boolean;
    name: string;
    onValueChange?: (value: string) => void;
    options: SelectOption[];
    placeholder?: string;
    required?: boolean;
}) {
    const [value, setValue] = useState(defaultValue);
    const selectedValue =
        value === '' ? (required ? undefined : emptySelectValue) : value;

    return (
        <>
            <input type="hidden" name={name} value={value} />
            <Select
                value={selectedValue}
                onValueChange={(nextValue) => {
                    const resolvedValue =
                        nextValue === emptySelectValue ? '' : nextValue;

                    setValue(resolvedValue);
                    onValueChange?.(resolvedValue);
                }}
                required={required}
            >
                <SelectTrigger
                    id={id}
                    aria-invalid={invalid}
                    aria-describedby={describedBy}
                    className="w-full"
                >
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent align="start">
                    {placeholder && !required && (
                        <SelectItem value={emptySelectValue}>
                            {placeholder}
                        </SelectItem>
                    )}
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </>
    );
}
