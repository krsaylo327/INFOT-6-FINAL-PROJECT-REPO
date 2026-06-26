import React from 'react';

export default function FlowerSVG({
    className = '',
    petalColor = '#ea580c',
    accentColor = '#16a34a',
    centerColor = '#f59e0b',
}: {
    className?: string;
    petalColor?: string;
    accentColor?: string;
    centerColor?: string;
}) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 200 200"
            className={className}
            role="img"
            aria-hidden="true"
        >
            <defs>
                <linearGradient id="petalGrad" x1="0%" x2="100%">
                    <stop
                        offset="0%"
                        stopColor={petalColor}
                        stopOpacity="0.95"
                    />
                    <stop
                        offset="100%"
                        stopColor="#ffedd5"
                        stopOpacity="0.85"
                    />
                </linearGradient>

                <radialGradient id="centerGrad" cx="50%" cy="40%" r="60%">
                    <stop offset="0%" stopColor="#fff7ed" />
                    <stop offset="50%" stopColor={centerColor} />
                    <stop offset="100%" stopColor="#b45309" />
                </radialGradient>

                <filter id="soft" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="2.2" />
                </filter>
            </defs>

            <g transform="translate(100,100)">
                {[0, 40, 80, 120, 160, 200, 240, 280].map((r, i) => (
                    <path
                        key={r}
                        d={`M0 0 C ${-18} ${-36} ${-44} ${-72} 0 -88 C ${44} ${-72} ${18} ${-36} 0 0 Z`}
                        transform={`rotate(${r})`}
                        fill="url(#petalGrad)"
                        stroke={accentColor}
                        strokeWidth={i % 2 === 0 ? 0.6 : 0.4}
                        opacity={0.95}
                    />
                ))}

                {/* small accents (green) between petals */}
                {[20, 60, 100, 140, 180, 220, 260, 300].map((r) => (
                    <ellipse
                        key={r}
                        rx="6"
                        ry="14"
                        transform={`rotate(${r}) translate(0,-42)`}
                        fill={accentColor}
                        opacity={0.85}
                    />
                ))}

                {/* center */}
                <circle
                    r="20"
                    fill="url(#centerGrad)"
                    stroke="#92400e"
                    strokeWidth="1.5"
                    filter="url(#soft)"
                />

                {/* subtle dotted inner ring */}
                <g transform="scale(0.6)">
                    {[...Array(12).keys()].map((i) => {
                        const angle = (i / 12) * 360;

                        return (
                            <circle
                                key={i}
                                r="1.8"
                                cx={Math.cos((angle * Math.PI) / 180) * 26}
                                cy={Math.sin((angle * Math.PI) / 180) * 26}
                                fill="#f7aa2a"
                                opacity={0.9}
                            />
                        );
                    })}
                </g>
            </g>
        </svg>
    );
}
