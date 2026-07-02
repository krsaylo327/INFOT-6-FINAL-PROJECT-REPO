import { cn } from '@/lib/utils';

interface AgreementTypeBadgeProps {
    type: string | null | undefined;
    className?: string;
}

const TYPE_CLASSES: Record<string, string> = {
    moa: 'bg-blue-100 text-blue-700 border-blue-200',
    mou: 'bg-emerald-100 text-emerald-700 border-emerald-200',
};

export function AgreementTypeBadge({
    type,
    className,
}: AgreementTypeBadgeProps) {
    const normalized = (type ?? '').toLowerCase();
    const classes = TYPE_CLASSES[normalized] ?? 'bg-gray-100 text-gray-700 border-gray-200';
    const label = normalized.toUpperCase() || 'N/A';

    return (
        <span
            className={cn(
                'inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-xs font-bold uppercase tracking-wider',
                classes,
                className,
            )}
        >
            {label}
        </span>
    );
}