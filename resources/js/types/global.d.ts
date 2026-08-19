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
                articles: { canView: boolean; count: number };
                contact: { canView: boolean; followUpCount: number };
                events: { canView: boolean; count: number };
                locations: { canView: boolean };
                media: { canView: boolean };
                redirects: { canView: boolean };
                roles: { canView: boolean };
                seasons: { canManage: boolean };
                users: { canView: boolean; count: number };
            } | null;
            ui: {
                authPhotoRotationInterval: number;
            };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
