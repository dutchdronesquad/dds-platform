export type SupportedLocale = {
    name: string;
    native_name: string;
};

export type LocaleProps = {
    active: string;
    supported: Record<string, SupportedLocale>;
    default: string;
    fallback: string;
    usesRoutePrefixes: boolean;
};
