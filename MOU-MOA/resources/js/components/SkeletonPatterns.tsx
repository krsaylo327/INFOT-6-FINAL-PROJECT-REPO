import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

interface SkeletonCardProps {
    className?: string;
    showVersion?: boolean;
    showBadge?: boolean;
}

export function SkeletonCard({
    className,
    showVersion,
    showBadge,
}: SkeletonCardProps) {
    return (
        <div
            className={cn(
                'space-y-4 rounded-xl border bg-white p-5',
                className,
            )}
        >
            {/* Header */}
            <div className="flex items-start justify-between">
                <div className="flex-1 space-y-2">
                    <Skeleton className="h-5 w-3/4" />
                    <Skeleton className="h-4 w-1/2" />
                </div>
                {showBadge && <Skeleton className="h-6 w-20 rounded-full" />}
            </div>

            {/* Content */}
            <div className="space-y-2">
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-5/6" />
            </div>

            {/* Footer */}
            <div className="flex items-center justify-between pt-2">
                <Skeleton className="h-4 w-24" />
                <Skeleton className="h-8 w-20 rounded-lg" />
            </div>

            {/* Version indicator */}
            {showVersion && (
                <div className="border-t pt-2">
                    <Skeleton className="h-3 w-32" />
                </div>
            )}
        </div>
    );
}

interface SkeletonStatsProps {
    className?: string;
}

export function SkeletonStats({ className }: SkeletonStatsProps) {
    return (
        <div className={cn('grid grid-cols-2 gap-4 md:grid-cols-4', className)}>
            {[...Array(4)].map((_, i) => (
                <div
                    key={i}
                    className="space-y-3 rounded-xl border bg-white p-5"
                >
                    <Skeleton className="h-4 w-20" />
                    <Skeleton className="h-8 w-16" />
                </div>
            ))}
        </div>
    );
}

interface SkeletonTableProps {
    rows?: number;
    className?: string;
}

export function SkeletonTable({ rows = 5, className }: SkeletonTableProps) {
    return (
        <div
            className={cn(
                'overflow-hidden rounded-xl border bg-white',
                className,
            )}
        >
            {/* Header */}
            <div className="border-b px-5 py-4">
                <div className="flex gap-4">
                    <Skeleton className="h-4 w-1/4" />
                    <Skeleton className="h-4 w-1/4" />
                    <Skeleton className="h-4 w-1/4" />
                    <Skeleton className="h-4 w-1/4" />
                </div>
            </div>

            {/* Rows */}
            {[...Array(rows)].map((_, i) => (
                <div key={i} className="border-b px-5 py-4 last:border-b-0">
                    <div className="flex items-center gap-4">
                        <Skeleton className="h-4 w-1/4" />
                        <Skeleton className="h-4 w-1/4" />
                        <Skeleton className="h-4 w-1/4" />
                        <Skeleton className="h-4 w-1/4" />
                    </div>
                </div>
            ))}
        </div>
    );
}

interface SkeletonTimelineProps {
    steps?: number;
    className?: string;
}

export function SkeletonTimeline({
    steps = 5,
    className,
}: SkeletonTimelineProps) {
    return (
        <div className={cn('rounded-xl border bg-white p-8', className)}>
            <Skeleton className="mb-8 h-6 w-40" />
            <div className="flex gap-2">
                {[...Array(steps)].map((_, i) => (
                    <div key={i} className="flex-1 space-y-2">
                        <Skeleton className="mx-auto h-12 w-12 rounded-full" />
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="mx-auto h-3 w-3/4" />
                    </div>
                ))}
            </div>
        </div>
    );
}
