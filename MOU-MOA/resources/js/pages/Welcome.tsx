import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import PublicLayout from '@/layouts/PublicLayout';

export default function Welcome() {
    const { auth } = usePage().props as any;
    const [imgAvailable, setImgAvailable] = useState(true);

    return (
        <PublicLayout>
            <Head title="MOA/MOU Tracking" />

            <div className="relative text-center">
                {/* Foreground flower and title (SVG accent) */}
                <div className="mb-6 flex items-center justify-center">
                    <h1 className="mb-0 text-4xl font-semibold text-foreground">
                        MOA/MOU TRACKING AND INSTITUTIONAL PARTNERSHIP
                        MANAGEMENT SYSTEM
                    </h1>
                </div>
            </div>
        </PublicLayout>
    );
}

function ImageOrSvg({
    className = '',
    imgClassName = '',
    svgProps = { color: '#d97706', fill: '#fffbeb' },
}: {
    className?: string;
    imgClassName?: string;
    svgProps?: { color?: string; fill?: string };
}) {
    const [ok, setOk] = useState(true);

    if (ok) {
        return (
            <div className={className}>
                <img
                    src="/images/flower.png"
                    alt="flower"
                    className={imgClassName}
                    onError={() => setOk(false)}
                    onLoad={() => setOk(true)}
                />
            </div>
        );
    }

    return (
        <div className={className}>
            <FlowerSVG
                className={imgClassName}
                color={svgProps.color}
                fill={svgProps.fill}
            />
        </div>
    );
}
