import { cn } from '@/lib/utils';

const STATUS_CLASSES: Record<string, string> = {
    draft: 'bg-gray-500 text-white',
    for_review: 'bg-yellow-500 text-white',
    active: 'bg-green-500 text-white',
    renewed: 'bg-blue-600 text-white',
    expired: 'bg-red-700 text-white',
    terminated: 'bg-zinc-800 text-white',
    disabled: 'bg-red-100 text-red-700',
};

interface AgreementStatusBadgeProps {
    status: string | null | undefined;
    className?: string;
}

export function AgreementStatusBadge({
    status,
    className,
}: AgreementStatusBadgeProps) {
    const normalized = (status ?? 'unknown').toLowerCase();
    const classes = STATUS_CLASSES[normalized] ?? 'bg-gray-100 text-gray-700';
    const label = humanizeStatus(status);

    return (
        <span
            className={cn(
                'inline-flex items-center justify-center rounded-full px-3 py-1 text-xs font-semibold capitalize',
                classes,
                className,
            )}
        >
            {label}
        </span>
    );
}

function humanizeStatus(status: string | null | undefined): string {
    if (!status) {
return 'Unknown';
}

    return status.replaceAll('_', ' ');
}