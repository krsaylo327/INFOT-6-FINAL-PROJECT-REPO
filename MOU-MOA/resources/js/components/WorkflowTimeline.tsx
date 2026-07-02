import { Check } from 'lucide-react';
import {
    WORKFLOW_STAGES,
    getStageIndex,
    isStageCompleted,
    isStageCurrent,
    isStagePending,
} from '@/lib/agreement';
import { cn } from '@/lib/utils';

interface WorkflowTimelineProps {
    currentStatus: string | null | undefined;
    onStageClick?: (stageId: string) => void;
    className?: string;
}

export function WorkflowTimeline({
    currentStatus,
    onStageClick,
    className,
}: WorkflowTimelineProps) {
    const currentIndex = getStageIndex(currentStatus);

    return (
        <div className={cn('w-full overflow-x-auto pb-4', className)}>
            <div className="relative flex min-w-max items-start justify-between gap-0">
                {WORKFLOW_STAGES.map((stage, index) => {
                    const completed = isStageCompleted(stage.id, currentStatus);
                    const current = isStageCurrent(stage.id, currentStatus);
                    const pending = isStagePending(stage.id, currentStatus);
                    const isLast = index === WORKFLOW_STAGES.length - 1;

                    return (
                        <div
                            key={stage.id}
                            className="flex flex-col items-center"
                        >
                            {/* Step circle */}
                            <button
                                type="button"
                                onClick={() => onStageClick?.(stage.id)}
                                disabled={!current && !completed}
                                className={cn(
                                    'relative z-10 flex h-12 w-12 items-center justify-center rounded-full border-2 transition-all duration-300',
                                    completed &&
                                        'cursor-default border-red-600 bg-red-600 text-white',
                                    current &&
                                        'border-red-600 bg-white text-red-600 ring-4 ring-red-100',
                                    pending &&
                                        'cursor-default border-gray-200 bg-white text-gray-400',
                                    (current || completed) &&
                                        onStageClick &&
                                        'cursor-pointer hover:scale-110',
                                )}
                            >
                                {completed ? (
                                    <Check className="h-5 w-5" />
                                ) : current ? (
                                    <div className="h-3 w-3 rounded-full bg-red-600" />
                                ) : (
                                    <span className="text-xs font-bold">
                                        {index + 1}
                                    </span>
                                )}
                            </button>

                            {/* Connector line to next stage */}
                            {!isLast && (
                                <div
                                    className={cn(
                                        'absolute top-6 h-0.5 w-full',
                                        completed
                                            ? 'bg-red-600'
                                            : 'bg-gray-200',
                                    )}
                                    style={{
                                        left: '3rem',
                                        width: 'calc(100% - 1.5rem)',
                                    }}
                                />
                            )}

                            {/* Label */}
                            <div
                                className={cn(
                                    'mt-3 max-w-[100px] text-center transition-colors duration-300',
                                    current && 'font-semibold text-red-600',
                                    completed && 'font-medium text-gray-700',
                                    pending && 'text-gray-400',
                                )}
                            >
                                <p className="text-sm leading-tight font-bold">
                                    {stage.label}
                                </p>
                                <p className="mt-0.5 text-xs opacity-70">
                                    {stage.handler}
                                </p>
                            </div>

                            {/* Description on hover for current stage */}
                            {current && (
                                <div className="mt-2 text-center text-xs text-gray-500">
                                    {stage.description}
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Mobile-friendly: show current stage prominently */}
            <div className="mt-6 md:hidden">
                <div className="rounded-xl border border-red-200 bg-red-50 p-4">
                    <p className="text-sm font-semibold text-red-600">
                        Current Stage: {WORKFLOW_STAGES[currentIndex]?.label}
                    </p>
                    <p className="mt-1 text-xs text-red-500">
                        {WORKFLOW_STAGES[currentIndex]?.description}
                    </p>
                </div>
            </div>
        </div>
    );
}

interface WorkflowTimelineSimpleProps {
    currentStatus: string | null | undefined;
    className?: string;
}

export function WorkflowTimelineSimple({
    currentStatus,
    className,
}: WorkflowTimelineSimpleProps) {
    return (
        <div className={cn('', className)}>
            <WorkflowTimeline currentStatus={currentStatus} />
        </div>
    );
}
