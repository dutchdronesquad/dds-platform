import { Form } from '@inertiajs/react';
import {
    CheckCircle2,
    Handshake,
    Mail,
    MessageCircle,
    Newspaper,
    Phone,
    Send,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import { store } from '@/actions/App/Http/Controllers/Public/ContactController';
import InputError from '@/components/input-error';
import { PublicHero } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { SeoMetadata } from '@/types';

type ContactPage = {
    actions: {
        external?: boolean;
        href: string;
        label: string;
    }[];
    description: string;
    title: string;
    visual: {
        alt: string;
        position?: string;
        src: string;
    };
};

type TopicOption = {
    label: string;
    value: string;
};

type Props = {
    page: ContactPage;
    seo: SeoMetadata;
    sourceContext: string | null;
    topics: TopicOption[];
};

const messageMinLength = 20;
const messageMaxLength = 5000;

const labelClassName = 'text-sm font-medium text-deep-signal dark:text-white';

const controlClassName =
    'h-11 w-full rounded-sm border border-paddock-rule bg-white px-3 text-sm text-deep-signal shadow-sm outline-none focus-visible:border-dds-blue focus-visible:ring-3 focus-visible:ring-dds-blue/20 dark:border-white/15 dark:bg-night-950 dark:text-white dark:focus-visible:border-dds-cyan dark:focus-visible:ring-dds-cyan/20';

const submitButtonClassName =
    'group mt-7 inline-flex min-h-11 min-w-44 items-center justify-center gap-2 rounded-sm bg-deep-signal px-5 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-dds-blue focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:ring-offset-white focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60 dark:focus-visible:ring-offset-night-950';

type ContactChannel = {
    body: string;
    external?: boolean;
    href: string;
    icon: LucideIcon;
    title: string;
};

const directContactChannels: ContactChannel[] = [
    {
        title: 'Bel of app ons',
        body: '+31 6 38 23 54 09',
        icon: Phone,
        href: 'tel:+31638235409',
    },
    {
        title: 'WhatsApp',
        body: 'Stuur direct een bericht via WhatsApp.',
        icon: MessageCircle,
        href: 'https://wa.me/31638235409',
        external: true,
    },
    {
        title: 'Algemene vragen',
        body: 'info@dutchdronesquad.nl',
        icon: Mail,
        href: 'mailto:info@dutchdronesquad.nl',
    },
    {
        title: 'Pers en media',
        body: 'media@dutchdronesquad.nl',
        icon: Newspaper,
        href: 'mailto:media@dutchdronesquad.nl',
    },
    {
        title: 'Samenwerken en sponsoring',
        body: 'partners@dutchdronesquad.nl',
        icon: Handshake,
        href: 'mailto:partners@dutchdronesquad.nl',
    },
];

export default function Contact({ page, seo, sourceContext, topics }: Props) {
    const [topic, setTopic] = useState('');
    const [message, setMessage] = useState('');
    const messageTooShort = message.length < messageMinLength;

    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                title={page.title}
                description={page.description}
                actions={page.actions}
                media={page.visual}
                separatorTone="air"
            />

            <section
                id="formulier"
                aria-labelledby="contact-form-heading"
                className="scroll-mt-20 overflow-hidden bg-air py-14 text-deep-signal sm:py-20 dark:bg-night-900 dark:text-white"
            >
                <div className="mx-auto w-full max-w-7xl px-public-gutter">
                    <div className="grid gap-12 lg:grid-cols-[0.72fr_1.28fr] lg:gap-16">
                        <div className="max-w-xl">
                            <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                                Stuur ons een bericht
                            </p>
                            <h2
                                id="contact-form-heading"
                                className="mt-5 font-public-display text-4xl leading-[1.02] font-semibold tracking-[-0.05em] text-balance sm:text-5xl"
                            >
                                Vertel waarmee we je kunnen helpen.
                            </h2>
                            <p className="mt-5 text-base leading-7 text-signal-muted sm:text-lg sm:leading-8 dark:text-night-400">
                                We lezen elk bericht persoonlijk en reageren zo
                                snel mogelijk.
                            </p>

                            <ul className="mt-10 border-t border-deep-signal/18 dark:border-white/15">
                                {directContactChannels.map((channel) => (
                                    <li
                                        key={channel.title}
                                        className="border-b border-deep-signal/18 py-5 dark:border-white/15"
                                    >
                                        <a
                                            href={channel.href}
                                            target={
                                                channel.external
                                                    ? '_blank'
                                                    : undefined
                                            }
                                            rel={
                                                channel.external
                                                    ? 'noopener noreferrer'
                                                    : undefined
                                            }
                                            className="group flex items-center gap-4"
                                        >
                                            <span className="flex size-10 shrink-0 items-center justify-center border border-deep-signal/15 bg-white text-dds-blue transition-colors group-hover:border-dds-blue dark:border-white/15 dark:bg-white/6 dark:text-dds-cyan dark:group-hover:border-dds-cyan">
                                                <channel.icon
                                                    aria-hidden="true"
                                                    className="size-4"
                                                />
                                            </span>
                                            <span>
                                                <span className="block font-public-display text-lg font-semibold tracking-[-0.02em] transition-colors group-hover:text-dds-blue dark:group-hover:text-dds-cyan">
                                                    {channel.title}
                                                </span>
                                                <span className="mt-0.5 block text-sm leading-6 text-signal-muted dark:text-night-400">
                                                    {channel.body}
                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <Form
                            {...store.form()}
                            resetOnSuccess
                            options={{ preserveScroll: true }}
                            onSuccess={() => {
                                setTopic('');
                                setMessage('');
                            }}
                        >
                            {({ errors, processing, recentlySuccessful }) => (
                                <div className="relative overflow-hidden border border-paddock-rule bg-white p-6 sm:p-8 dark:border-white/12 dark:bg-night-950">
                                    <span
                                        aria-hidden="true"
                                        className="absolute top-0 right-0 h-1.5 w-1/3 bg-dds-orange"
                                    />
                                    <span
                                        aria-hidden="true"
                                        className="absolute top-0 left-0 h-1.5 w-1/4 bg-dds-cyan"
                                    />
                                    {recentlySuccessful && (
                                        <div
                                            role="status"
                                            className="mb-6 flex gap-3 border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200"
                                        >
                                            <CheckCircle2 className="mt-0.5 size-5 shrink-0" />
                                            Je bericht is opgeslagen. We nemen
                                            zo snel mogelijk contact met je op.
                                        </div>
                                    )}

                                    <div className="grid gap-5 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <label
                                                htmlFor="contact-name"
                                                className={labelClassName}
                                            >
                                                Naam
                                            </label>
                                            <input
                                                id="contact-name"
                                                name="name"
                                                autoComplete="name"
                                                className={controlClassName}
                                                aria-invalid={Boolean(
                                                    errors.name,
                                                )}
                                                aria-describedby={
                                                    errors.name
                                                        ? 'contact-name-error'
                                                        : undefined
                                                }
                                            />
                                            <InputError
                                                id="contact-name-error"
                                                message={errors.name}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <label
                                                htmlFor="contact-email"
                                                className={labelClassName}
                                            >
                                                E-mailadres
                                            </label>
                                            <input
                                                id="contact-email"
                                                name="email"
                                                type="email"
                                                autoComplete="email"
                                                className={controlClassName}
                                                aria-invalid={Boolean(
                                                    errors.email,
                                                )}
                                                aria-describedby={
                                                    errors.email
                                                        ? 'contact-email-error'
                                                        : undefined
                                                }
                                            />
                                            <InputError
                                                id="contact-email-error"
                                                message={errors.email}
                                            />
                                        </div>
                                    </div>

                                    <div className="mt-5 grid gap-2">
                                        <label
                                            htmlFor="contact-topic"
                                            className={labelClassName}
                                        >
                                            Onderwerp
                                        </label>
                                        <input
                                            type="hidden"
                                            name="topic"
                                            value={topic}
                                        />
                                        <Select
                                            value={topic || undefined}
                                            onValueChange={setTopic}
                                        >
                                            <SelectTrigger
                                                id="contact-topic"
                                                aria-invalid={Boolean(
                                                    errors.topic,
                                                )}
                                                aria-describedby={
                                                    errors.topic
                                                        ? 'contact-topic-error'
                                                        : undefined
                                                }
                                                className="h-11 w-full rounded-sm border-paddock-rule bg-white text-sm text-deep-signal shadow-sm dark:border-white/15 dark:bg-night-950 dark:text-white"
                                            >
                                                <SelectValue placeholder="Kies een onderwerp" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {topics.map((topicOption) => (
                                                    <SelectItem
                                                        key={topicOption.value}
                                                        value={
                                                            topicOption.value
                                                        }
                                                    >
                                                        {topicOption.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            id="contact-topic-error"
                                            message={errors.topic}
                                        />
                                    </div>

                                    <div className="mt-5 grid gap-2">
                                        <label
                                            htmlFor="contact-message"
                                            className={labelClassName}
                                        >
                                            Bericht
                                        </label>
                                        <textarea
                                            id="contact-message"
                                            name="message"
                                            rows={7}
                                            maxLength={messageMaxLength}
                                            value={message}
                                            onChange={(event) =>
                                                setMessage(event.target.value)
                                            }
                                            className="w-full resize-y rounded-sm border border-paddock-rule bg-white px-3 py-3 text-sm leading-6 text-deep-signal shadow-sm outline-none focus-visible:border-dds-blue focus-visible:ring-3 focus-visible:ring-dds-blue/20 dark:border-white/15 dark:bg-night-950 dark:text-white dark:focus-visible:border-dds-cyan dark:focus-visible:ring-dds-cyan/20"
                                            aria-invalid={Boolean(
                                                errors.message,
                                            )}
                                            aria-describedby={
                                                errors.message
                                                    ? 'contact-message-error'
                                                    : 'contact-message-help'
                                            }
                                        />
                                        <p
                                            id="contact-message-help"
                                            className="text-xs text-signal-muted dark:text-night-400"
                                        >
                                            {message.length} /{' '}
                                            {messageMaxLength} tekens
                                            {messageTooShort &&
                                                ` · nog minimaal ${messageMinLength - message.length} nodig`}
                                        </p>
                                        <InputError
                                            id="contact-message-error"
                                            message={errors.message}
                                        />
                                    </div>

                                    <div
                                        aria-hidden="true"
                                        className="absolute -left-[10000px] size-px overflow-hidden"
                                    >
                                        <label htmlFor="contact-website">
                                            Website
                                        </label>
                                        <input
                                            id="contact-website"
                                            name="website"
                                            type="text"
                                            tabIndex={-1}
                                            autoComplete="off"
                                        />
                                    </div>

                                    {sourceContext && (
                                        <input
                                            type="hidden"
                                            name="source_context"
                                            value={sourceContext}
                                        />
                                    )}

                                    <div className="mt-6">
                                        <label className="dark:text-night-300 flex items-start gap-3 text-sm leading-6 text-signal-muted">
                                            <input
                                                type="checkbox"
                                                name="consent"
                                                value="1"
                                                className="mt-1 size-4 shrink-0 rounded-sm border-paddock-rule text-dds-blue focus:ring-dds-blue/30 dark:border-white/20 dark:bg-night-950"
                                                aria-invalid={Boolean(
                                                    errors.consent,
                                                )}
                                                aria-describedby={
                                                    errors.consent
                                                        ? 'contact-consent-error'
                                                        : undefined
                                                }
                                            />
                                            <span>
                                                Ik geef Dutch Drone Squad
                                                toestemming om mijn gegevens te
                                                gebruiken om op dit bericht te
                                                reageren.
                                            </span>
                                        </label>
                                        <InputError
                                            id="contact-consent-error"
                                            className="mt-2"
                                            message={errors.consent}
                                        />
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={processing || messageTooShort}
                                        className={submitButtonClassName}
                                    >
                                        <Send className="size-4" />
                                        {processing
                                            ? 'Bericht versturen…'
                                            : 'Bericht versturen'}
                                    </button>
                                </div>
                            )}
                        </Form>
                    </div>
                </div>
            </section>
        </>
    );
}
