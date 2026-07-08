import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-red-600 text-white shadow-sm dark:bg-red-500 dark:text-neutral-950">
                <AppLogoIcon className="size-5 fill-current" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    Dutch Drone Squad
                </span>
                <span className="truncate text-xs leading-tight text-neutral-500 dark:text-neutral-400">
                    Platform
                </span>
            </div>
        </>
    );
}
