import type { SVGAttributes } from 'react';

/**
 * A dress silhouette, used as the DressCost mark.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 1.5h1.5L12 3.5l1.5-2H15l-.6 4.2 3.9 4.6-2.1 1.2L20 22H4l3.8-10.5-2.1-1.2 3.9-4.6L9 1.5Z" />
        </svg>
    );
}
