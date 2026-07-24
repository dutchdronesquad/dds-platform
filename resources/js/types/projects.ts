export type ProjectLink = {
    label: string;
    url: string;
};

export type ProjectMedium = {
    alt: string;
    darkSrc?: string;
    src: string;
};

export type PublicProject = {
    audience: string;
    credits: string[];
    featured: boolean;
    media: ProjectMedium[];
    primaryLink: ProjectLink;
    slug: string;
    summary: string;
    supportingLinks: ProjectLink[];
    title: string;
    type: {
        label: string;
        value: string;
    };
    videoUrl: string | null;
};
