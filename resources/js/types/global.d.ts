import type { Auth } from '@/types/auth';
import type { LocaleProps } from '@/types/localization';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            locale: LocaleProps;
            auth: Auth;
            management: {
                canViewRedirects: boolean;
            } | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
