import { usePage, router, useForm } from '@inertiajs/react';
import {
    ArrowRight,
    ArrowLeft,
    FileText,
    Clipboard,
    Calendar,
    Users,
    Clock,
} from 'lucide-react';
import { useState } from 'react';
import { AgreementStatusBadge } from '@/components/AgreementStatusBadge';
import { AgreementTypeBadge } from '@/components/AgreementTypeBadge';
import { EmptyState } from '@/components/EmptyState';
import { WorkflowTimeline } from '@/components/WorkflowTimeline';
import AdminLayout from '@/layouts/AdminLayout';

export default function AgreementDetails() {
    const {
        agreement,
        versions = [],
        workflowHistory = [],
        auth,
        partnerUsers = [],
        currentWorkflowStatus: propCurrentWorkflowStatus,
        currentWorkflowRole: propCurrentWorkflowRole,
        nextWorkflowStatus: propNextWorkflowStatus,
        nextWorkflowRole: propNextWorkflowRole,
    } = usePage().props as any;

    const userRole = (auth?.user?.role_normalized || auth?.user?.role || '')
        .toString()
        .toLowerCase()
        .replace(/\s+/g, '_');

    const workflowStatus =
        propCurrentWorkflowStatus || agreement.workflow_status;
    const stageRoleMap: Record<string, string> = {
        legal_assistant_ii: 'legal_assistant_ii',
        legal_assistant_iii: 'legal_assistant_iii',
        attorney_review: 'attorney',
        administrative_aid: 'administrative_aid',
        attorney_initials: 'attorney',
        president_approval: 'president',
        active_agreement: 'authorized_personnel',
    };

    const WORKFLOW_STAGES = [
        'legal_assistant_ii',
        'legal_assistant_iii',
        'attorney_review',
        'administrative_aid',
        'attorney_initials',
        'president_approval',
    ];

    function getNextStage(stage: string): string | null {
        const index = WORKFLOW_STAGES.indexOf(stage);

        if (index === -1 || index >= WORKFLOW_STAGES.length - 1) {
            return null;
        }

        return WORKFLOW_STAGES[index + 1];
    }

    const isSubmitter = auth?.user?.id === agreement.submitted_by;
    const coordinatorStage = auth?.user?.coordinator_stage || null;
    const uploadedToStage = coordinatorStage ? getNextStage(coordinatorStage) : 'legal_assistant_ii';

    const WORKFLOW_FORWARD_ACTIONS = [
        {
            status: 'legal_assistant_ii',
            buttonLabel: 'Forward to Legal Assistant III',
            nextStatus: 'legal_assistant_iii',
            remarks: 'Reviewed by Legal Assistant II',
        },
        {
            status: 'legal_assistant_iii',
            buttonLabel: 'Forward to Attorney',
            nextStatus: 'attorney_review',
            remarks: 'Reviewed by Legal Assistant III',
        },
        {
            status: 'attorney_review',
            buttonLabel: 'Send to Administrative Aid',
            nextStatus: 'administrative_aid',
            remarks: 'Attorney review completed',
        },
        {
            status: 'administrative_aid',
            buttonLabel: 'Send to Attorney',
            nextStatus: 'attorney_initials',
            remarks: 'Agreement logged and ready for Attorney Initials',
        },
        {
            status: 'attorney_initials',
            buttonLabel: 'Send to President',
            nextStatus: 'president_approval',
            remarks: 'Attorney initials completed',
        },
        {
            status: 'president_approval',
            buttonLabel: 'Approve — Mark Active',
            nextStatus: 'active_agreement',
            remarks: 'Agreement approved by President and is now active',
        },
    ];

    // Determine an effective workflow status: if the current workflow_status is missing,
    // fall back to the most recent workflow history entry that targeted the current user's role.
    const effectiveWorkflowStatus = (() => {
        if (workflowStatus) {
            return workflowStatus;
        }

        for (let i = workflowHistory.length - 1; i >= 0; i--) {
            const h = workflowHistory[i];
            const mappedRole = stageRoleMap[h.to_status];

            if (mappedRole && mappedRole === userRole) {
                return h.to_status;
            }
        }

        return agreement.workflow_status;
    })();

    const partnerCoordinatorRoles = [
        'authorized_personnel',
        'legal_assistant_ii',
        'legal_assistant_iii',
        'attorney',
        'administrative_aid',
        'president',
        'coordinator',
    ];

    const { data, setData, processing, put } = useForm({
        _method: 'PUT',
        status: agreement.status || '',
        document: null as File | null,
    });

    const [subscribed, setSubscribed] = useState<boolean>(
        agreement?.isSubscribed || false,
    );
    const [formError, setFormError] = useState<string>('');
    const [fileInputKey, setFileInputKey] = useState<number>(0);
    const [confirmModal, setConfirmModal] = useState<{
        type: 'upload' | 'return' | 'save-draft' | 'forward' | null;
        visible: boolean;
        message: string;
        target?: string;
        targetUserId?: number;
        targetRole?: string;
        nextStatus?: string;
        remarks?: string;
    }>({ type: null, visible: false, message: '' });
    const [returnRemarks, setReturnRemarks] = useState<string>('');

    const submitDraftForReview = () => {
        if (!agreement || agreement.status !== 'draft') {
            return;
        }

        setData('status', 'for_review');

        window.requestAnimationFrame(() => {
            put(`/agreements/${agreement.id}`, {
                forceFormData: true,
            });
        });
    };

    const handleConfirmUpload = async () => {
        setFormError('');

        if (!data.document) {
            return;
        }

        const selectedTargetUserId =
            confirmModal.targetUserId ??
            (partnerUsers.length === 1 ? partnerUsers[0].id : undefined);

        if (partnerUsers.length > 1 && !selectedTargetUserId) {
            setFormError('Please select a target user before confirming.');

            return;
        }

        try {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('status', agreement.status || '');
            formData.append('document', data.document);

            const uploadRes = await fetch(`/agreements/${agreement.id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
                body: formData,
            });

            if (!uploadRes.ok) {
                let errorMessage = 'Upload failed. Please try again.';

                try {
                    const errorData = await uploadRes.json();

                    if (errorData.message) {
                        errorMessage = errorData.message;
                    } else if (errorData.errors) {
                        const firstError = Object.values(errorData.errors)[0];

                        if (Array.isArray(firstError)) {
                            errorMessage = firstError[0] as string;
                        } else {
                            errorMessage = String(firstError);
                        }
                    }
                } catch {
                    // JSON parse failed, use default message
                }

                setFormError(errorMessage);

                return;
            }

            setFileInputKey((prev) => prev + 1);
            setData('document', null);
            setConfirmModal({ type: null, visible: false, message: '' });

            const next = nextWorkflowStatus;

            if (next) {
                forwardAgreement(
                    next,
                    'Uploaded revised PDF',
                    selectedTargetUserId,
                    () => {
                        router.visit('/agreements');
                    },
                );
            } else {
                router.visit('/agreements');
            }
        } catch {
            setFormError('An error occurred during upload. Please try again.');
        }
    };

    const handleConfirmForward = async () => {
        setFormError('');
        setConfirmModal({ type: null, visible: false, message: '' });

        if (data.document) {
            try {
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('status', agreement.status || '');
                formData.append('document', data.document);

                const uploadRes = await fetch(`/agreements/${agreement.id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN':
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content') || '',
                    },
                    body: formData,
                });

                if (!uploadRes.ok) {
                    let errorMessage = 'Upload failed. Please try again.';

                    try {
                        const errorData = await uploadRes.json();

                        if (errorData.message) {
                            errorMessage = errorData.message;
                        } else if (errorData.errors) {
                            const firstError = Object.values(errorData.errors)[0];
                            errorMessage = Array.isArray(firstError) ? firstError[0] as string : String(firstError);
                        }
                    } catch {
                        // JSON parse failed, use default error message
                    }

                    setFormError(errorMessage);

                    return;
                }

                setFileInputKey((prev) => prev + 1);
                setData('document', null);
            } catch {
                setFormError('An error occurred during upload. Please try again.');

                return;
            }
        }

        const next = confirmModal.nextStatus;
        const remarks = confirmModal.remarks || 'Forwarded';

        if (next) {
            forwardAgreement(next, remarks);
        }
    };

    const handleReturnConfirm = async () => {
        setConfirmModal({ type: null, visible: false, message: '' });

        if (!returnRemarks.trim()) {
            setFormError('Please enter a reason for returning the agreement.');

            return;
        }

        setReturnRemarks('');

        const returnPayload: Record<string, string> = {
            remarks: returnRemarks,
        };

        if (currentWorkflowStatus === 'administrative_aid') {
            returnPayload.return_to = 'attorney_initials';
        } else if (confirmModal.target) {
            returnPayload.return_to = confirmModal.target;
        }

        router.post(`/agreements/${agreement.id}/return`, returnPayload);
    };

    const saveDraftFromDetails = async () => {
        setFormError('');

        const formData = new FormData();

        try {
            formData.append('_method', 'PUT');
            formData.append('status', 'draft');

            if (data.document) {
                formData.append('document', data.document);
            }

            const res = await fetch(`/agreements/${agreement.id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
                body: formData,
            });

            if (res.ok) {
                setFileInputKey((prev) => prev + 1);
                setData('document', null);
                router.visit('/agreements');
            } else {
                let errorMessage = 'Failed to save draft. Please try again.';

                try {
                    const errorData = await res.json();

                    if (errorData.message) {
                        errorMessage = errorData.message;
                    } else if (errorData.errors) {
                        // Extract first validation error
                        const firstError = Object.values(errorData.errors)[0];

                        if (Array.isArray(firstError)) {
                            errorMessage = firstError[0] as string;
                        } else {
                            errorMessage = String(firstError);
                        }
                    }
                } catch {
                    // JSON parse failed, use default message
                }

                setFormError(errorMessage);
            }
        } catch {
            setFormError('An error occurred while saving. Please try again.');
        }
    };

    const nextStatusForRole: Record<string, string> = {
        legal_assistant_ii: 'legal_assistant_iii',
        legal_assistant_iii: 'attorney_review',
        attorney_review: 'administrative_aid',
        administrative_aid: 'attorney_initials',
        attorney_initials: 'president_approval',
        president_approval: 'active_agreement',
    };

    const returnTargets = (() => {
        const set = new Set<string>();
        set.add('authorized_personnel');

        Object.keys(nextStatusForRole).forEach((k) => set.add(k));

        return Array.from(set);
    })();

    const humanize = (s: string | undefined) =>
        (s || '').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());

    const currentWorkflowStatus =
        propCurrentWorkflowStatus ||
        agreement.workflow_status ||
        effectiveWorkflowStatus;
    const displayWorkflowStatus = currentWorkflowStatus;
    const currentWorkflowRole =
        propCurrentWorkflowRole || stageRoleMap[currentWorkflowStatus];
    const currentWorkflowRoleNormalized = currentWorkflowRole
        ? currentWorkflowRole.toString().toLowerCase().replace(/\s+/g, '_')
        : '';
    const nextWorkflowStatus =
        propNextWorkflowStatus || nextStatusForRole[currentWorkflowStatus];
    const nextWorkflowRole =
        propNextWorkflowRole || stageRoleMap[nextWorkflowStatus || ''];
    const nextWorkflowRoleNormalized = nextWorkflowRole
        ? nextWorkflowRole.toString().toLowerCase().replace(/\s+/g, '_')
        : '';
    const nextWorkflowRoleLabel = humanize(nextWorkflowRoleNormalized);
    const canUploadRevisedPdf = Boolean(
        nextWorkflowStatus && userRole === currentWorkflowRoleNormalized,
    );
    const getUploadConfirmButtonText = () => {
        const selected = partnerUsers.find(
            (u: any) => u.id === confirmModal.targetUserId,
        );

        if (selected) {
            return `Yes, upload to ${selected.name}`;
        }

        if (partnerUsers.length === 1) {
            return `Yes, upload to ${partnerUsers[0].name}`;
        }

        if (nextWorkflowRoleLabel) {
            return `Yes, upload to ${nextWorkflowRoleLabel}`;
        }

        return 'Yes, upload file';
    };

    /*
    |--------------------------------------------------------------------------
    | FORWARD AGREEMENT
    |--------------------------------------------------------------------------
    */
    const forwardAgreement = (
        nextStatus: string,
        remarks: string,
        targetUserId?: number,
        onSuccess?: () => void,
    ) => {
        router.post(
            `/agreements/${agreement.id}/forward`,
            {
                next_status: nextStatus,
                remarks: remarks,
                target_user_id: targetUserId,
            },
            {
                onSuccess: () => {
                    if (onSuccess) {
                        onSuccess();
                    }
                },
            },
        );
    };

    /*
    |--------------------------------------------------------------------------
    | RETURN AGREEMENT
    |--------------------------------------------------------------------------
    */
    const returnAgreement = () => {
        const humanize = (s: string | undefined) =>
            (s || '')
                .replaceAll('_', ' ')
                .replace(/\b\w/g, (c) => c.toUpperCase());

        let message: string;

        if (currentWorkflowStatus === 'legal_assistant_ii') {
            message = 'Return this agreement to the sender for revision?';
        } else {
            let prev: string | null = null;

            for (const k in nextStatusForRole) {
                if (nextStatusForRole[k] === currentWorkflowStatus) {
                    prev = k;
                    break;
                }
            }

            message = prev
                ? `Return agreement to ${humanize(prev)}?`
                : 'Return agreement?';
        }

        setReturnRemarks('');
        setConfirmModal({
            type: 'return',
            visible: true,
            message,
        });
    };

    

    return (
        <AdminLayout>
            <div className="space-y-8">
                {/* PAGE HEADER */}
                <div className="rounded-2xl bg-white p-8 shadow">
                    <div className="flex items-start justify-between">
                        <div className="flex items-start gap-4">
                            <div>
                                <h1 className="text-4xl font-bold text-red-700">
                                    {agreement.title}
                                </h1>

                                <p className="mt-2 text-lg text-gray-500">
                                    {agreement.partner_organization}
                                </p>

                                <div className="mt-3 flex items-center gap-2">
                                    <AgreementTypeBadge
                                        type={agreement.type}
                                    />
                                </div>
                            </div>
                        </div>

                        <AgreementStatusBadge
                            status={displayWorkflowStatus}
                            className="px-4 py-2 text-sm"
                        />
                    </div>

                    {/* DETAILS */}
                    <div className="mt-8 space-y-8">
                        {/* IDENTIFICATION */}
                        <div>
                            <div className="mb-4 flex items-center gap-2 border-b pb-2">
                                <FileText className="h-5 w-5 text-red-600" />
                                <h3 className="text-lg font-semibold text-gray-800">
                                    Agreement Information
                                </h3>
                            </div>
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">
                                        Agreement Type
                                    </p>
                                    <p className="mt-0.5 font-medium text-gray-900">
                                        {agreement.type ?? 'N/A'}
                                    </p>
                                </div>

                                <div>
                                    <p className="text-sm font-medium text-gray-500">
                                        Current Handler
                                    </p>
                                    <p className="mt-0.5 font-medium text-gray-900">
                                        {agreement.current_handler || 'N/A'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* DATES */}
                        <div>
                            <div className="mb-4 flex items-center gap-2 border-b pb-2">
                                <Calendar className="h-5 w-5 text-red-600" />
                                <h3 className="text-lg font-semibold text-gray-800">
                                    Key Dates
                                </h3>
                            </div>
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">
                                        Expiration Date
                                    </p>
                                    <p className="mt-0.5 font-medium text-gray-900">
                                        {agreement.expires_at || 'N/A'}
                                    </p>
                                </div>

                                <div>
                                    <p className="text-sm font-medium text-gray-500">
                                        Signed Date
                                    </p>
                                    <p className="mt-0.5 font-medium text-gray-900">
                                        {agreement.signed_at || 'Not Yet Signed'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* SUBMISSION */}
                        <div>
                            <div className="mb-4 flex items-center gap-2 border-b pb-2">
                                <Users className="h-5 w-5 text-red-600" />
                                <h3 className="text-lg font-semibold text-gray-800">
                                    Submission
                                </h3>
                            </div>
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">
                                        Submitted By
                                    </p>
                                    <p className="mt-0.5 font-medium text-gray-900">
                                        {agreement.submitted_by || 'N/A'}
                                    </p>
                                </div>

                                <div>
                                    <p className="text-sm font-medium text-gray-500">
                                        Agreement Status
                                    </p>
                                    <div className="mt-0.5">
                                        <AgreementStatusBadge
                                            status={agreement.status}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* CURRENT DOCUMENT */}
                <div className="rounded-2xl bg-white p-8 shadow">
                    <h2 className="mb-6 text-2xl font-bold text-red-700">
                        Agreement Document
                    </h2>

                    {agreement.document ? (
                        <>
                            <div className="flex flex-wrap gap-4">
                                {/* SUBSCRIBE / UNSUBSCRIBE */}
                                {auth?.user && (
                                    <button
                                        onClick={() => {
                                            if (subscribed) {
                                                router.delete(
                                                    `/agreements/${agreement.id}/subscribe`,
                                                );
                                                setSubscribed(false);
                                            } else {
                                                router.post(
                                                    `/agreements/${agreement.id}/subscribe`,
                                                );
                                                setSubscribed(true);
                                            }
                                        }}
                                        className={`rounded-xl px-5 py-3 text-white ${subscribed ? 'bg-gray-600 hover:bg-gray-700' : 'bg-indigo-600 hover:bg-indigo-700'}`}
                                    >
                                        {subscribed
                                            ? 'Unsubscribe from reminders'
                                            : 'Subscribe to reminders'}
                                    </button>
                                )}

                                <button
                                    type="button"
                                    onClick={() =>
                                        window.open(
                                            `/agreements/${agreement.id}/view`,
                                            '_blank',
                                        )
                                    }
                                    className="rounded-xl bg-red-700 px-5 py-3 text-white hover:bg-red-800"
                                >
                                    View PDF
                                </button>

                                <button
                                    type="button"
                                    onClick={() => {
                                        window.location.href = `/agreements/${agreement.id}/download`;
                                    }}
                                    className="rounded-xl bg-red-700 px-5 py-3 text-white hover:bg-red-800"
                                >
                                    Download PDF
                                </button>
                            </div>

                            <p className="mt-4 text-sm text-gray-500">
                                Current file:{' '}
                                {agreement.document
                                    ? agreement.document.split('/').pop()
                                    : 'N/A'}
                            </p>
                        </>
                    ) : (
                        <p className="text-gray-500">No uploaded document.</p>
                    )}
                </div>

                {/* WORKFLOW TIMELINE */}
                <div className="rounded-2xl bg-white p-8 shadow">
                    <h2 className="mb-6 text-2xl font-bold text-red-700">
                        Workflow Timeline
                    </h2>

                    <WorkflowTimeline
                        currentStatus={effectiveWorkflowStatus || agreement.workflow_status}
                    />
                </div>

                {/* WORKFLOW ACTIONS */}
                <div className="rounded-2xl bg-white p-8 shadow">
                    <h2 className="mb-6 text-2xl font-bold text-red-700">
                        Workflow Actions
                    </h2>

                    <div className="flex flex-wrap gap-4">
                        {/* SUBMIT DRAFT */}
                        {userRole === 'authorized_personnel' &&
                            agreement.status === 'draft' &&
                            agreement.submitted_by === auth?.user?.id && (
                                <button
                                    type="button"
                                    onClick={submitDraftForReview}
                                    disabled={processing}
                                    className="rounded-xl bg-red-700 px-5 py-3 text-white transition-colors hover:bg-red-800 disabled:bg-red-900"
                                    aria-label="Submit draft agreement for Legal Assistant II review"
                                >
                                    {processing
                                        ? 'Submitting...'
                                        : 'Submit Draft to Legal Assistant II'}
                                </button>
                            )}

                        {/* PARTNER COORDINATOR WORKSPACE */}
                        {partnerCoordinatorRoles.includes(userRole) && (
                            <div className="w-full rounded-xl border bg-blue-50 p-4">
                                <p className="text-lg font-bold">
                                    Partner Coordinator Workspace
                                </p>
                                <p className="mt-2 text-sm text-gray-600">
                                    Upload revised document or submit to next stage.
                                </p>

                                {formError && (
                                    <div
                                        className="mt-4 rounded-lg border border-red-400 bg-red-100 p-3 text-sm text-red-700"
                                        role="alert"
                                    >
                                        {formError}
                                    </div>
                                )}

                                {canUploadRevisedPdf && (
                                    <div className="mt-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                                        <p className="mb-2 text-sm font-medium text-gray-700">
                                            Upload Revised Document
                                        </p>
                                        <input
                                            key={fileInputKey}
                                            type="file"
                                            accept="application/pdf"
                                            onChange={(e) => {
                                                const file = e.target.files?.[0];

                                                if (file) {
                                                    setData('document', file);
                                                }
                                            }}
                                            className="w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-white file:hover:bg-indigo-700"
                                        />
                                        <p className="mt-2 text-xs text-gray-500">
                                            PDF only. The file will be uploaded when you forward.
                                        </p>
                                    </div>
                                )}

                                <div className="mt-4 flex flex-wrap gap-3">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setConfirmModal({
                                                type: 'save-draft',
                                                visible: true,
                                                message:
                                                    'Save this agreement as draft?',
                                            })
                                        }
                                        disabled={processing}
                                        className="rounded-xl bg-gray-300 px-4 py-2 text-black transition-colors hover:bg-gray-400 disabled:bg-gray-400"
                                        aria-label="Save current agreement as draft"
                                    >
                                        {processing
                                            ? 'Saving...'
                                            : 'Save Draft'}
                                    </button>

                                    <button
                                        type="button"
                                        onClick={returnAgreement}
                                        disabled={processing}
                                        className="rounded-xl bg-yellow-500 px-4 py-2 text-white transition-colors hover:bg-yellow-600 disabled:bg-yellow-700"
                                        aria-label={
                                            currentWorkflowStatus === 'administrative_aid'
                                                ? 'Send agreement to Attorney for initials'
                                                : 'Return agreement to previous stage'
                                        }
                                    >
                                        {processing
                                            ? 'Returning...'
                                            : currentWorkflowStatus === 'administrative_aid'
                                              ? 'Send to Attorney (Logged)'
                                              : 'Return Agreement'}
                                    </button>
                                </div>
                            </div>
                        )}

                        {WORKFLOW_FORWARD_ACTIONS.map((action) => {
                            const coordinatorStage = auth?.user?.coordinator_stage || '';
                            const coordinatorStageStatuses: Record<string, string[]> = {
                                legal_assistant_ii: ['legal_assistant_ii'],
                                legal_assistant_iii: ['legal_assistant_iii'],
                                attorney: ['attorney_review', 'attorney_initials'],
                                administrative_aid: ['administrative_aid'],
                                president_approval: ['president_approval'],
                            };
                            const isCoordinatorAtStage =
                                coordinatorStageStatuses[coordinatorStage]?.includes(action.status) ?? false;
                            const isAdminMatch =
                                userRole === stageRoleMap[action.status];
                            const isUploaderAtNextStage =
                                isSubmitter &&
                                coordinatorStage &&
                                currentWorkflowStatus === uploadedToStage &&
                                currentWorkflowStatus === action.status;
                            const isMatch =
                                currentWorkflowStatus === action.status &&
                                (isCoordinatorAtStage || isAdminMatch || isUploaderAtNextStage);

                            if (!isMatch) {
                                return null;
                            }

                            return (
                                <button
                                    key={action.status}
                                    onClick={() => {
                                        if (data.document) {
                                            setConfirmModal({
                                                type: 'forward',
                                                visible: true,
                                                message: `Upload revised document and send to ${humanize(action.nextStatus.replace('_', ' '))}?`,
                                                nextStatus: action.nextStatus,
                                                remarks: action.remarks,
                                            });
                                        } else {
                                            forwardAgreement(
                                                action.nextStatus,
                                                action.remarks,
                                            );
                                        }
                                    }}
                                    className="rounded-xl bg-red-700 px-5 py-3 text-white hover:bg-red-800"
                                >
                                    {action.buttonLabel}
                                </button>
                            );
                        })}

                        {/* RETURN handled in Partner Coordinator Workspace */}
                    </div>
                </div>

                {/* WORKFLOW HISTORY */}
                <div className="rounded-2xl bg-white p-8 shadow">
                    <div className="mb-6 flex items-center gap-2">
                        <Clock className="h-6 w-6 text-red-600" />
                        <h2 className="text-2xl font-bold text-red-700">
                            Workflow History
                        </h2>
                    </div>

                    {workflowHistory.length === 0 ? (
                        <EmptyState
                            icon={Clipboard}
                            title="No workflow history yet"
                            description="Workflow events will appear here as the agreement progresses through review stages."
                        />
                    ) : (
                        <div className="space-y-4">
                            {workflowHistory.map((history: any) => {
                                const isForward =
                                    history.action === 'Forwarded' ||
                                    history.action === 'Submitted' ||
                                    history.action === 'Approved';
                                const ActionIcon = isForward
                                    ? ArrowRight
                                    : ArrowLeft;
                                const accentColor = isForward
                                    ? 'border-green-500 bg-green-50'
                                    : 'border-amber-500 bg-amber-50';

                                return (
                                    <div
                                        key={history.id}
                                        className={`rounded-xl border-l-4 p-5 ${accentColor}`}
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex items-center gap-2">
                                                <ActionIcon className="mt-0.5 h-4 w-4 text-gray-500" />
                                                <p className="text-base font-bold text-gray-800">
                                                    {history.action}
                                                </p>
                                            </div>
                                            {history.created_at && (
                                                <p className="text-xs text-gray-400">
                                                    {new Date(
                                                        history.created_at,
                                                    ).toLocaleString(
                                                        'en-US',
                                                        {
                                                            month: 'short',
                                                            day: 'numeric',
                                                            year: 'numeric',
                                                            hour: '2-digit',
                                                            minute: '2-digit',
                                                        },
                                                    )}
                                                </p>
                                            )}
                                        </div>

                                        <p className="mt-2 flex items-center gap-1.5 text-sm text-gray-600">
                                            <span className="font-medium capitalize">
                                                {humanize(
                                                    history.from_status,
                                                )}
                                            </span>
                                            <span className="text-gray-400">
                                                →
                                            </span>
                                            <span className="font-medium capitalize">
                                                {humanize(
                                                    history.to_status,
                                                )}
                                            </span>
                                        </p>

                                        <p className="mt-2 text-xs text-gray-500">
                                            By{' '}
                                            <span className="font-medium text-gray-600">
                                                {history.performed_by}
                                            </span>
                                        </p>

                                        {history.remarks && (
                                            <p className="mt-1 text-xs italic text-gray-500">
                                                "{history.remarks}"
                                            </p>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>

                {/* VERSION HISTORY */}
                <div className="rounded-2xl bg-white p-8 shadow">
                    <div className="mb-6 flex items-center gap-2">
                        <FileText className="h-6 w-6 text-red-600" />
                        <h2 className="text-2xl font-bold text-red-700">
                            Document Versions
                        </h2>
                    </div>

                    {versions.length === 0 ? (
                        <EmptyState
                            icon={FileText}
                            title="No document versions yet"
                            description="When a document is uploaded or revised, it will appear here as a new version."
                        />
                    ) : (
                        <div className="space-y-4">
                            {versions.map((version: any) => (
                                <div
                                    key={version.id}
                                    className="flex items-center justify-between rounded-xl border p-5"
                                >
                                    <div className="flex items-start gap-4">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50">
                                            <span className="text-sm font-bold text-red-700">
                                                v
                                                {version.version}
                                            </span>
                                        </div>

                                        <div>
                                            <div className="flex items-center gap-2">
                                                <p className="font-semibold text-gray-900">
                                                    Version {version.version}
                                                </p>
                                                {version.created_at && (
                                                    <p className="text-xs text-gray-400">
                                                        {new Date(
                                                            version.created_at,
                                                        ).toLocaleDateString(
                                                            'en-US',
                                                            {
                                                                month: 'short',
                                                                day: 'numeric',
                                                                year: 'numeric',
                                                            },
                                                        )}
                                                    </p>
                                                )}
                                            </div>

                                            <p className="mt-1 flex items-center gap-1 text-sm text-gray-500">
                                                <span className="font-medium">
                                                    File:
                                                </span>{' '}
                                                {version.document
                                                    ? version.document
                                                          .split('/')
                                                          .pop()
                                                    : 'Unknown file'}
                                            </p>

                                            <p className="mt-0.5 flex items-center gap-1 text-sm text-gray-500">
                                                <span className="font-medium">
                                                    By:
                                                </span>{' '}
                                                {typeof version.uploaded_by ===
                                                'string'
                                                    ? version.uploaded_by
                                                    : version.uploaded_by
                                                          ?.name ??
                                                      version.uploadedBy
                                                          ?.name ??
                                                      'Unknown'}
                                            </p>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() =>
                                            window.open(
                                                `/agreements/${agreement.id}/versions/${version.id}/view`,
                                                '_blank',
                                            )
                                        }
                                        className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                                    >
                                        View
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Confirmation Modal (Upload / Return) */}
            {confirmModal.visible && (
                <div className="fixed inset-0 z-50 flex items-center justify-center">
                    <div
                        className="absolute inset-0 bg-black opacity-50"
                        onClick={() =>
                            setConfirmModal({
                                type: null,
                                visible: false,
                                message: '',
                            })
                        }
                    />

                    <div
                        className="relative z-10 w-full max-w-lg rounded-xl bg-white p-6 shadow-lg"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="confirm-modal-title"
                        aria-describedby="confirm-modal-description"
                    >
                        <h3
                            id="confirm-modal-title"
                            className="text-lg font-bold text-red-700"
                        >
                            Confirmation
                        </h3>
                        <p
                            id="confirm-modal-description"
                            className="mt-2 text-sm text-gray-700"
                        >
                            {confirmModal.message}
                        </p>

                        {confirmModal.type === 'upload' && (
                            <div className="mt-4">
                                <p className="text-sm text-gray-600">
                                    Selected file:{' '}
                                    {data.document
                                        ? data.document.name
                                        : 'No file selected'}
                                </p>

                                {partnerUsers.length > 1 ? (
                                    <div className="mt-3">
                                        <p className="mb-2 text-sm font-medium">
                                            Upload revised PDF to:
                                        </p>
                                        <div className="grid max-h-48 grid-cols-1 gap-2 overflow-auto">
                                            {partnerUsers.map((u: any) => (
                                                <button
                                                    key={u.id}
                                                    type="button"
                                                    onClick={() =>
                                                        setConfirmModal({
                                                            ...confirmModal,
                                                            targetUserId: u.id,
                                                            targetRole:
                                                                u.role_normalized,
                                                            message: `Are you sure you want to upload this PDF to ${u.name} (${humanize(u.role_normalized)}) for review?`,
                                                        })
                                                    }
                                                    className={`w-full rounded px-3 py-2 text-left ${confirmModal.targetUserId === u.id ? 'border border-indigo-200 bg-indigo-50' : 'bg-gray-50 hover:bg-gray-100'}`}
                                                >
                                                    <div className="font-medium">
                                                        {u.name}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {(
                                                            u.role || ''
                                                        ).replaceAll('_', ' ')}
                                                    </div>
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                ) : partnerUsers.length === 1 ? (
                                    <div className="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                        <p className="text-sm">
                                            This file will be uploaded to:
                                        </p>
                                        <p className="mt-1 font-medium">
                                            {partnerUsers[0].name} (
                                            {humanize(
                                                partnerUsers[0].role_normalized,
                                            )}
                                            )
                                        </p>
                                    </div>
                                ) : (
                                    <div className="mt-3 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                                        No specific user found for the next
                                        stage. Upload will proceed to the next
                                        role in the workflow.
                                    </div>
                                )}

                                <div className="mt-4 flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setConfirmModal({
                                                type: null,
                                                visible: false,
                                                message: '',
                                            })
                                        }
                                        className="rounded-xl bg-gray-200 px-4 py-2 hover:bg-gray-300"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="button"
                                        onClick={handleConfirmUpload}
                                        disabled={
                                            processing ||
                                            (partnerUsers.length > 1 &&
                                                !confirmModal.targetUserId)
                                        }
                                        className="rounded-xl bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                                    >
                                        {processing
                                            ? 'Uploading...'
                                            : getUploadConfirmButtonText()}
                                    </button>
                                </div>
                            </div>
                        )}

                        {confirmModal.type === 'forward' && (
                            <div className="mt-4">
                                <p className="text-sm text-gray-600">
                                    Selected file:{' '}
                                    {data.document
                                        ? data.document.name
                                        : 'No file selected'}
                                </p>
                                <p className="mt-3 text-sm text-gray-700">
                                    This document will be uploaded and the agreement forwarded to the next stage.
                                </p>
                                <div className="mt-4 flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setConfirmModal({
                                                type: null,
                                                visible: false,
                                                message: '',
                                            })
                                        }
                                        className="rounded-xl bg-gray-200 px-4 py-2 hover:bg-gray-300"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="button"
                                        onClick={handleConfirmForward}
                                        disabled={processing}
                                        className="rounded-xl bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                                    >
                                        {processing
                                            ? 'Processing...'
                                            : 'Confirm & Send'}
                                    </button>
                                </div>
                            </div>
                        )}

                        {confirmModal.type === 'return' && (
                            <div className="mt-4 space-y-3">
                                <label className="block text-sm font-medium">
                                    Return To
                                </label>
                                <select
                                    value={confirmModal.target || ''}
                                    onChange={(e) =>
                                        setConfirmModal({
                                            ...confirmModal,
                                            target: e.target.value,
                                        })
                                    }
                                    className="w-full rounded-md border px-3 py-2"
                                >
                                    <option value="">Select target</option>
                                    {returnTargets.map((t) => (
                                        <option key={t} value={t}>
                                            {humanize(t)}
                                        </option>
                                    ))}
                                </select>

                                <label className="block text-sm font-medium">
                                    Remarks
                                </label>
                                <textarea
                                    value={returnRemarks}
                                    onChange={(e) =>
                                        setReturnRemarks(e.target.value)
                                    }
                                    className="w-full rounded-md border px-3 py-2"
                                    rows={4}
                                />

                                <div className="flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setConfirmModal({
                                                type: null,
                                                visible: false,
                                                message: '',
                                            })
                                        }
                                        className="rounded-xl bg-gray-200 px-4 py-2 hover:bg-gray-300"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="button"
                                        onClick={handleReturnConfirm}
                                        disabled={processing}
                                        className="rounded-xl bg-yellow-500 px-4 py-2 text-white hover:bg-yellow-600"
                                    >
                                        {processing
                                            ? 'Returning...'
                                            : 'Confirm Return'}
                                    </button>
                                </div>
                            </div>
                        )}
                        {confirmModal.type === 'save-draft' && (
                            <div className="mt-4">
                                <p className="text-sm text-gray-700">
                                    {confirmModal.message}
                                </p>
                                <div className="mt-4 flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setConfirmModal({
                                                type: null,
                                                visible: false,
                                                message: '',
                                            })
                                        }
                                        className="rounded-xl bg-gray-200 px-4 py-2 hover:bg-gray-300"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="button"
                                        onClick={() => {
                                            setConfirmModal({
                                                type: null,
                                                visible: false,
                                                message: '',
                                            });
                                            saveDraftFromDetails();
                                        }}
                                        disabled={processing}
                                        className="rounded-xl bg-gray-700 px-4 py-2 text-white hover:bg-gray-800"
                                    >
                                        {processing
                                            ? 'Saving...'
                                            : 'Confirm Save Draft'}
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
