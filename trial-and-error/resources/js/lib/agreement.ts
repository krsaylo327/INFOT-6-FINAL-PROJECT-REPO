type AgreementLike = {
    title?: string | null;
    partner_organization?: string | null;
    type?: string | null;
    status?: string | null;
    description?: string | null;
    workflow_status?: string | null;
    submitted_by?: number | null;
    expires_at?: string | null;
};

type UserLike = {
    id?: number | null;
    role?: string | null;
};

const STATUS_LABELS: Record<string, string> = {
    draft: 'Draft',
    for_review: 'For Review',
    active: 'Active',
    renewed: 'Renewed',
    expired: 'Expired',
    terminated: 'Terminated',
    disabled: 'Disabled',
};

const STATUS_CLASSES: Record<string, string> = {
    draft: 'bg-gray-500 text-white',
    for_review: 'bg-yellow-500 text-white',
    active: 'bg-green-500 text-white',
    renewed: 'bg-blue-600 text-white',
    expired: 'bg-red-700 text-white',
    terminated: 'bg-black text-white',
    disabled: 'bg-red-300 text-red-900',
};

export interface WorkflowStage {
    id: string;
    label: string;
    handler: string;
    description: string;
}

export const WORKFLOW_STAGES: WorkflowStage[] = [
    {
        id: 'draft',
        label: 'Draft',
        handler: 'Sender',
        description: 'Agreement created as draft',
    },
    {
        id: 'legal_assistant_ii',
        label: 'Legal Assistant II',
        handler: 'LA II',
        description: 'Initial review',
    },
    {
        id: 'legal_assistant_iii',
        label: 'Legal Assistant III',
        handler: 'LA III',
        description: 'Further review',
    },
    {
        id: 'attorney_review',
        label: 'Attorney Review',
        handler: 'Attorney',
        description: 'Legal review',
    },
    {
        id: 'administrative_aid',
        label: 'Administrative Aid',
        handler: 'Admin Aid',
        description: 'Logging & timestamps',
    },
    {
        id: 'attorney_initials',
        label: 'Attorney Initials',
        handler: 'Attorney',
        description: 'Initial approval',
    },
    {
        id: 'president_approval',
        label: 'President Approval',
        handler: 'President',
        description: 'Final approval',
    },
    {
        id: 'active_agreement',
        label: 'Active',
        handler: 'Active',
        description: 'Live agreement record',
    },
];

export const WORKFLOW_STAGE_COLORS: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 border-gray-300',
    legal_assistant_ii: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    legal_assistant_iii: 'bg-orange-50 text-orange-700 border-orange-200',
    attorney_review: 'bg-purple-50 text-purple-700 border-purple-200',
    administrative_aid: 'bg-blue-50 text-blue-700 border-blue-200',
    attorney_initials: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    president_approval: 'bg-pink-50 text-pink-700 border-pink-200',
    active_agreement: 'bg-green-50 text-green-700 border-green-200',
};

export const WORKFLOW_STAGE_ICONS: Record<string, string> = {
    draft: 'FileText',
    legal_assistant_ii: 'FileSearch',
    legal_assistant_iii: 'FileCheck',
    attorney_review: 'Scale',
    administrative_aid: 'ClipboardList',
    attorney_initials: 'PenLine',
    president_approval: 'Stamp',
    active_agreement: 'CheckCircle',
};

export function getStageIndex(status: string | null | undefined): number {
    if (!status) {
return 0;
}

    const index = WORKFLOW_STAGES.findIndex((s) => s.id === status);

    return index === -1 ? 0 : index;
}

export function getStageColor(status: string | null | undefined): string {
    if (!status) {
return WORKFLOW_STAGE_COLORS['draft'];
}

    return (
        WORKFLOW_STAGE_COLORS[status] ??
        'bg-gray-100 text-gray-700 border-gray-300'
    );
}

export function getNextStage(status: string | null | undefined): string | null {
    if (!status) {
return WORKFLOW_STAGES[1]?.id ?? null;
}

    const currentIndex = getStageIndex(status);

    return WORKFLOW_STAGES[currentIndex + 1]?.id ?? null;
}

export function getStageById(id: string): WorkflowStage | undefined {
    return WORKFLOW_STAGES.find((s) => s.id === id);
}

export function isStageCompleted(
    stageId: string,
    currentStatus: string | null | undefined,
): boolean {
    if (!currentStatus) {
return false;
}

    return getStageIndex(stageId) < getStageIndex(currentStatus);
}

export function isStageCurrent(
    stageId: string,
    currentStatus: string | null | undefined,
): boolean {
    if (!currentStatus) {
return false;
}

    return stageId === currentStatus;
}

export function isStagePending(
    stageId: string,
    currentStatus: string | null | undefined,
): boolean {
    if (!currentStatus) {
return stageId !== 'draft';
}

    return getStageIndex(stageId) > getStageIndex(currentStatus);
}

function normalize(value: string | null | undefined): string {
    return (value ?? '').toString().toLowerCase().trim();
}

export function buildAgreementSearchText(agreement: AgreementLike): string {
    return [
        agreement.title,
        agreement.partner_organization,
        agreement.type,
        agreement.status,
        agreement.description,
        agreement.workflow_status,
    ]
        .filter(Boolean)
        .map((value) => normalize(value))
        .join(' ');
}

export function matchesAgreementSearch(
    agreement: AgreementLike,
    search: string,
): boolean {
    const query = normalize(search);

    if (!query) {
        return true;
    }

    return buildAgreementSearchText(agreement).includes(query);
}

export function matchesAgreementQuickFilter(
    agreement: AgreementLike,
    user: UserLike,
    quickFilter: string,
): boolean {
    const normalizedQuickFilter = normalize(quickFilter);
    const role = normalize(user.role);

    if (!normalizedQuickFilter) {
        return true;
    }

    if (normalizedQuickFilter === 'submitted') {
        return agreement.submitted_by === user.id;
    }

    if (normalizedQuickFilter === 'assigned') {
        if (role === 'attorney') {
            return [
                'attorney_review',
                'attorney_initials',
            ].includes(normalize(agreement.workflow_status));
        }

        if (role === 'authorized_personnel') {
            return agreement.submitted_by === user.id;
        }

        return normalize(agreement.workflow_status) === role;
    }

    if (normalizedQuickFilter === 'pending') {
        return normalize(agreement.status) === 'for_review';
    }

    if (normalizedQuickFilter === 'approved') {
        return (
            normalize(agreement.workflow_status) === 'active_agreement' ||
            ['active', 'renewed'].includes(normalize(agreement.status))
        );
    }

    if (normalizedQuickFilter === 'drafts') {
        return normalize(agreement.status) === 'draft';
    }

    if (normalizedQuickFilter === 'expiring') {
        const expiresAt = agreement.expires_at
            ? new Date(agreement.expires_at)
            : null;

        if (!expiresAt || Number.isNaN(expiresAt.getTime())) {
            return false;
        }

        const now = new Date();
        const inThirtyDays = new Date();
        inThirtyDays.setDate(now.getDate() + 30);

        return expiresAt >= now && expiresAt <= inThirtyDays;
    }

    return normalizedQuickFilter === normalize(agreement.status);
}

export function getAgreementStatusLabel(status?: string | null): string {
    return STATUS_LABELS[normalize(status)] ?? status ?? 'Unknown';
}

export function getAgreementStatusColor(status?: string | null): string {
    return STATUS_CLASSES[normalize(status)] ?? 'bg-gray-100 text-gray-700';
}
