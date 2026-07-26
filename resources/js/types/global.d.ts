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
                canManageSeasons: boolean;
                canViewArticles: boolean;
                canViewContact: boolean;
                canViewEvents: boolean;
                canViewLocations: boolean;
                canViewMedia: boolean;
                canViewRedirects: boolean;
                canViewRoles: boolean;
                canViewUsers: boolean;
                contactFollowUpCount: number;
            } | null;
            ui: {
                authPhotoRotationInterval: number;
            };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
