import type { ReactNode } from 'react';

export const publicSocials: { href: string; icon: ReactNode; title: string }[] =
    [
        {
            title: 'Instagram',
            href: 'https://www.instagram.com/dutchdronesquad/',
            icon: (
                <>
                    <rect x="3" y="3" width="18" height="18" rx="5" />
                    <circle cx="12" cy="12" r="4" />
                    <circle
                        cx="17.5"
                        cy="6.5"
                        r="1"
                        fill="currentColor"
                        stroke="none"
                    />
                </>
            ),
        },
        {
            title: 'Facebook',
            href: 'https://www.facebook.com/dutchdronesquad',
            icon: (
                <path d="M10 21v-8H7V9h3V7.5C10 4.5 11.9 3 15 3h2v4h-1.5C14.4 7 14 7.6 14 8.5V9h3l-.7 4H14v8" />
            ),
        },
        {
            title: 'YouTube',
            href: 'https://www.youtube.com/@dutchdronesquad',
            icon: (
                <>
                    <rect x="2.5" y="6" width="19" height="12" rx="4" />
                    <path
                        d="m10 9 5 3-5 3Z"
                        fill="currentColor"
                        stroke="none"
                    />
                </>
            ),
        },
        {
            title: 'Twitch',
            href: 'https://www.twitch.tv/dutchdronesquad',
            icon: (
                <>
                    <path d="M5 3h15v11l-5 5h-4l-3 3v-3H5Z" />
                    <path d="M10 8v5M15 8v5" />
                </>
            ),
        },
    ];
