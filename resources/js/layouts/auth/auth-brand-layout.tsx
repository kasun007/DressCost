import { Link, usePage } from '@inertiajs/react';
import type { CSSProperties } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/**
 * A woven twill texture: fine diagonal threads crossing at two weights,
 * like the surface of indigo-dyed cloth.
 */
const weave: CSSProperties = {
    backgroundImage: [
        'repeating-linear-gradient(45deg, rgb(255 255 255 / 0.05) 0 1px, transparent 1px 7px)',
        'repeating-linear-gradient(-45deg, rgb(255 255 255 / 0.025) 0 1px, transparent 1px 7px)',
        'radial-gradient(ellipse at 20% 0%, rgb(217 174 74 / 0.18), transparent 55%)',
    ].join(', '),
};

/** A row of gold running stitches, the seam that ties the page together. */
function Seam({ className = '' }: { className?: string }) {
    return (
        <div
            aria-hidden
            className={`h-px w-full ${className}`}
            style={{
                backgroundImage:
                    'repeating-linear-gradient(90deg, var(--sidebar-primary) 0 10px, transparent 10px 18px)',
            }}
        />
    );
}

/**
 * The layout for login, registration and the other sign-in pages: an indigo
 * woven panel with the DressCost mark beside a clean form card.
 */
export default function AuthBrandLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="grid min-h-svh grid-rows-[auto_1fr] lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:grid-rows-1">
            <aside
                className="relative flex flex-col justify-between gap-10 overflow-hidden bg-sidebar px-6 py-8 text-sidebar-foreground sm:px-10 lg:px-14 lg:py-12"
                style={weave}
            >
                <Link
                    href={home()}
                    className="flex items-center gap-3 self-start"
                >
                    <span className="flex size-11 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground shadow-lg shadow-black/20">
                        <AppLogoIcon className="size-7 fill-current" />
                    </span>
                    <span className="text-xl font-semibold tracking-tight text-white">
                        {name}
                    </span>
                </Link>

                <div className="hidden max-w-md space-y-6 lg:block">
                    <Seam className="max-w-40" />
                    <p className="text-4xl leading-tight font-semibold tracking-tight text-balance text-white">
                        Know what every dress costs before you cut the fabric.
                    </p>
                    <p className="text-lg text-sidebar-foreground/80">
                        Cost your materials and labour, set your margin, and see
                        the price of every piece in every size.
                    </p>
                </div>

                <p className="hidden text-sm text-sidebar-foreground/60 lg:block">
                    Workspaces, projects and items, costed in one place.
                </p>

                {/* A large, faint dress silhouette sitting in the cloth. */}
                <AppLogoIcon
                    aria-hidden
                    className="pointer-events-none absolute -right-16 -bottom-24 hidden size-[34rem] fill-white/[0.035] lg:block"
                />
            </aside>

            <main className="flex items-center justify-center bg-background px-6 py-10 sm:px-10">
                <div className="w-full max-w-sm space-y-8">
                    <div className="space-y-2">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                    {children}
                </div>
            </main>
        </div>
    );
}
