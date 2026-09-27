import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type EventTab = 'general' | 'registration' | 'page' | 'track';

const tabs: { value: EventTab; label: string }[] = [
    { value: 'general', label: 'Algemeen' },
    { value: 'registration', label: 'Inschrijving' },
    { value: 'page', label: 'Publieke pagina' },
    { value: 'track', label: 'Track' },
];

export function EventTabs({
    value,
    onChange,
}: {
    value: EventTab;
    onChange: (tab: EventTab) => void;
}) {
    return (
        <div
            role="tablist"
            aria-label="Eventinstellingen"
            className="mx-auto flex w-full max-w-[103rem] gap-6 overflow-x-auto px-4 sm:px-6"
        >
            {tabs.map((tab, index) => (
                <button
                    key={tab.value}
                    type="button"
                    role="tab"
                    id={`event-tab-${tab.value}`}
                    aria-controls={`event-panel-${tab.value}`}
                    aria-selected={value === tab.value}
                    tabIndex={value === tab.value ? 0 : -1}
                    className={cn(
                        'shrink-0 border-b-2 border-transparent px-1 py-3 text-sm font-medium text-muted-foreground outline-none hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset',
                        value === tab.value &&
                            'border-signal-600 text-signal-700 dark:border-signal-400 dark:text-signal-300',
                    )}
                    onClick={() => onChange(tab.value)}
                    onKeyDown={(event) => {
                        const nextIndex =
                            event.key === 'ArrowRight'
                                ? (index + 1) % tabs.length
                                : event.key === 'ArrowLeft'
                                  ? (index + tabs.length - 1) % tabs.length
                                  : event.key === 'Home'
                                    ? 0
                                    : event.key === 'End'
                                      ? tabs.length - 1
                                      : null;
                        if (nextIndex === null) {
                            return;
                        }
                        event.preventDefault();
                        onChange(tabs[nextIndex].value);
                        document
                            .getElementById(
                                `event-tab-${tabs[nextIndex].value}`,
                            )
                            ?.focus();
                    }}
                >
                    {tab.label}
                </button>
            ))}
        </div>
    );
}

export function EventTabPanel({
    tab,
    activeTab,
    children,
}: {
    tab: EventTab;
    activeTab?: EventTab;
    children: ReactNode;
}) {
    if (activeTab === undefined) {
        return <>{children}</>;
    }

    return (
        <div
            id={`event-panel-${tab}`}
            role="tabpanel"
            tabIndex={0}
            aria-labelledby={`event-tab-${tab}`}
            data-event-tab={tab}
            hidden={activeTab !== tab}
            className="min-w-0"
        >
            {children}
        </div>
    );
}
