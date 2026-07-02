import { Link, useForm, usePage } from '@inertiajs/react';
import {
    FileText,
    Calendar,
    Upload,
    Send,
    ArrowRight,
    LayoutDashboard,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useRef } from 'react';
import InputError from '@/components/input-error';
import AdminLayout from '@/layouts/AdminLayout';

export default function AddAgreement() {
    const { data, setData, post, processing, errors } = useForm<{
        title: string;
        type: string;
        partner_organization: string;
        description: string;
        signed_at: string;
        expires_at: string;
        status: string;
        document: File | null;
    }>({
        title: '',
        type: 'MOA',
        partner_organization: '',
        description: '',
        signed_at: '',
        expires_at: '',
        status: 'draft',
        document: null,
    });

    const WORKFLOW_STAGES = [
        'legal_assistant_ii',
        'legal_assistant_iii',
        'attorney_review',
        'administrative_aid',
        'attorney_initials',
        'president_approval',
    ];

    const STAGE_LABELS: Record<string, string> = {
        legal_assistant_ii: 'Legal Assistant II',
        legal_assistant_iii: 'Legal Assistant III',
        attorney_review: 'Attorney',
        administrative_aid: 'Administrative Aid',
        attorney_initials: 'Attorney',
        president_approval: 'President',
    };

    function getNextStage(coordinatorStage: string | null): { stage: string; label: string } {
        if (coordinatorStage === null) {
            return { stage: 'legal_assistant_ii', label: 'Legal Assistant II' };
        }

        const currentIndex = WORKFLOW_STAGES.indexOf(coordinatorStage);

        if (currentIndex === -1 || currentIndex >= WORKFLOW_STAGES.length - 1) {
            return { stage: 'legal_assistant_ii', label: 'Legal Assistant II' };
        }

        const nextStage = WORKFLOW_STAGES[currentIndex + 1];

        return { stage: nextStage, label: STAGE_LABELS[nextStage] || nextStage };
    }

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();

        post('/agreements', {
            forceFormData: true,
        });
    }

    function handleSaveDraft() {
        setData('status', 'draft');
        window.requestAnimationFrame(() => {
            post('/agreements', {
                forceFormData: true,
            });
        });
    }

    function handleSubmitForReview() {
        setData('status', 'for_review');
        window.requestAnimationFrame(() => {
            post('/agreements', {
                forceFormData: true,
            });
        });
    }

    const props = usePage().props as any;
    const uploadInputRef = useRef<HTMLInputElement | null>(null);
    const createMode =
        typeof window !== 'undefined'
            ? new URLSearchParams(window.location.search).get('mode') ||
              'create'
            : 'create';
    const isUploadMode = createMode === 'upload';
    const roleNormalized = (props.auth?.user?.role_normalized || '')
        .toString()
        .toLowerCase()
        .replace(/\s+/g, '_');
    const isSystemAdminFallback = Boolean(
        (props.auth?.user?.name &&
            props.auth.user.name
                .toString()
                .toLowerCase()
                .includes('system admin')) ||
        (props.auth?.user?.role &&
            props.auth.user.role.toString().toLowerCase().includes('system')),
    );
    const isSystemAdmin =
        roleNormalized === 'system_admin' || isSystemAdminFallback;
    const showAgreementsNav =
        !isSystemAdmin &&
        (typeof props.showAgreementsNav !== 'undefined'
            ? props.showAgreementsNav
            : true);

    const coordinatorStage = props.auth?.user?.coordinator_stage || null;
    const nextStageInfo = getNextStage(coordinatorStage);

    useEffect(() => {
        if (isUploadMode) {
            uploadInputRef.current?.focus();
        }
    }, [isUploadMode]);

    return (
        <AdminLayout>
            {/* HEADER */}
            <div className="mb-8 flex items-center justify-between">
                <div>
                    <h1 className="text-4xl font-bold text-red-800">
                        Add Agreement
                    </h1>

                    <p className="mt-2 text-gray-600">
                        {isUploadMode
                            ? 'Create the agreement details, then attach the draft PDF below.'
                            : 'Create a new MOA/MOU agreement'}
                    </p>
                </div>

                {showAgreementsNav && (
                    <Link
                        href="/agreements"
                        className="rounded-xl bg-yellow-400 px-6 py-3 font-semibold text-black hover:bg-yellow-500"
                    >
                        Back
                    </Link>
                )}
            </div>

            <div
                className={`mb-6 rounded-2xl border p-5 ${isUploadMode ? 'border-blue-200 bg-blue-50' : 'border-red-100 bg-red-50'}`}
            >
                <p
                    className={`font-semibold ${isUploadMode ? 'text-blue-800' : 'text-red-800'}`}
                >
                    {isUploadMode ? 'Draft upload mode' : 'New agreement mode'}
                </p>

                <p className="mt-1 text-sm text-gray-600">
                    {isUploadMode
                        ? 'Use this screen to add the agreement metadata and immediately attach the draft PDF.'
                        : 'Use this screen to start a fresh agreement record.'}
                </p>
            </div>

            {/* FORM */}
            <div className="rounded-2xl bg-white p-8 shadow">
                <form onSubmit={submit} className="space-y-8">
                    {/* REQUIRED FIELDS LEGEND */}
                    <p className="text-sm text-gray-500">
                        <span className="text-red-500">*</span> Required
                        fields
                    </p>

                    {/* BASIC INFORMATION */}
                    <div className="space-y-4">
                        <div className="flex items-center gap-2 border-b pb-2">
                            <FileText className="h-5 w-5 text-red-600" />
                            <h2 className="text-lg font-semibold text-gray-800">
                                Basic Information
                            </h2>
                        </div>

                        {/* TITLE */}
                        <div>
                            <label className="mb-2 block text-sm font-medium text-gray-700">
                                Agreement Title{' '}
                                <span className="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                value={data.title}
                                onChange={(e) =>
                                    setData('title', e.target.value)
                                }
                                placeholder="e.g., Memorandum of Agreement with [Organization]"
                                className="w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                            />

                            <InputError
                                message={errors.title}
                                className="mt-1"
                            />
                        </div>

                        {/* TYPE + PARTNER */}
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700">
                                    Agreement Type{' '}
                                    <span className="text-red-500">*</span>
                                </label>

                                <select
                                    value={data.type}
                                    onChange={(e) =>
                                        setData('type', e.target.value)
                                    }
                                    className="w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                >
                                    <option value="MOA">MOA (Memorandum of Agreement)</option>
                                    <option value="MOU">MOU (Memorandum of Understanding)</option>
                                </select>

                                <InputError
                                    message={errors.type}
                                    className="mt-1"
                                />
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700">
                                    Partner Organization{' '}
                                    <span className="text-red-500">*</span>
                                </label>

                                <input
                                    type="text"
                                    value={data.partner_organization}
                                    onChange={(e) =>
                                        setData(
                                            'partner_organization',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g., Massachusetts Institute of Technology"
                                    className="w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                />

                                <InputError
                                    message={errors.partner_organization}
                                    className="mt-1"
                                />
                            </div>
                        </div>

                        {/* DESCRIPTION */}
                        <div>
                            <label className="mb-2 block text-sm font-medium text-gray-700">
                                Description
                            </label>

                            <textarea
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                placeholder="Brief description of the agreement's purpose and scope..."
                                className="h-28 w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                            />

                            <InputError
                                message={errors.description}
                                className="mt-1"
                            />
                        </div>
                    </div>

                    {/* KEY DATES */}
                    <div className="space-y-4">
                        <div className="flex items-center gap-2 border-b pb-2">
                            <Calendar className="h-5 w-5 text-red-600" />
                            <h2 className="text-lg font-semibold text-gray-800">
                                Key Dates
                            </h2>
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700">
                                    Signed Date
                                    <span className="ml-1.5 text-xs text-gray-400">
                                        (optional for draft)
                                    </span>
                                </label>

                                <input
                                    type="date"
                                    value={data.signed_at}
                                    onChange={(e) =>
                                        setData('signed_at', e.target.value)
                                    }
                                    className="w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                />

                                <InputError
                                    message={errors.signed_at}
                                    className="mt-1"
                                />
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700">
                                    Expiration Date
                                    <span className="ml-1.5 text-xs text-gray-400">
                                        (optional for draft)
                                    </span>
                                </label>

                                <input
                                    type="date"
                                    value={data.expires_at}
                                    onChange={(e) =>
                                        setData('expires_at', e.target.value)
                                    }
                                    className="w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                />

                                <InputError
                                    message={errors.expires_at}
                                    className="mt-1"
                                />
                            </div>
                        </div>
                    </div>

                    {/* WORKFLOW ROUTING */}
                    <div className="space-y-4">
                        <div className="flex items-center gap-2 border-b pb-2">
                            <ArrowRight className="h-5 w-5 text-red-600" />
                            <h2 className="text-lg font-semibold text-gray-800">
                                Workflow Routing
                            </h2>
                        </div>

                        <div className="rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <p className="font-semibold text-blue-700">
                                This agreement will be submitted to:
                            </p>

                            <div className="mt-3 flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                                    <LayoutDashboard className="h-5 w-5 text-blue-600" />
                                </div>
                                <div>
                                    <p className="text-lg font-bold text-gray-900">
                                        {nextStageInfo.label}
                                    </p>
                                    <p className="text-sm text-gray-500">
                                        {coordinatorStage
                                            ? `Next stage after your role (${STAGE_LABELS[coordinatorStage] || coordinatorStage})`
                                            : 'First review stage'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* DOCUMENT */}
                    <div className="space-y-4">
                        <div className="flex items-center gap-2 border-b pb-2">
                            <Upload className="h-5 w-5 text-red-600" />
                            <h2 className="text-lg font-semibold text-gray-800">
                                Document
                            </h2>
                        </div>

                        <div className="rounded-xl border-2 border-dashed border-gray-200 p-6 text-center transition hover:border-gray-300">
                            <Upload className="mx-auto mb-3 h-8 w-8 text-gray-400" />
                            <p className="mb-2 text-sm font-medium text-gray-700">
                                Upload draft PDF
                            </p>
                            <p className="mb-4 text-xs text-gray-500">
                                PDF only, max 10MB
                            </p>

                            <input
                                ref={uploadInputRef}
                                type="file"
                                accept=".pdf"
                                onChange={(e) =>
                                    setData(
                                        'document',
                                        e.target.files?.[0] || null,
                                    )
                                }
                                className="w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-red-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-red-700 hover:file:bg-red-100"
                            />
                        </div>

                        <InputError
                            message={errors.document}
                            className="mt-1"
                        />

                        <p className="text-sm text-gray-500">
                            {isUploadMode
                                ? 'This draft will be attached to the agreement on save.'
                                : 'Optional, but recommended for new records.'}
                        </p>
                    </div>

                    {/* BUTTONS: Save Draft or Submit for Review */}
                    <div className="flex items-center justify-end gap-4 border-t pt-6">
                        <button
                            type="button"
                            disabled={processing}
                            onClick={handleSaveDraft}
                            className="rounded-xl border border-gray-200 bg-white px-6 py-3 font-semibold text-gray-600 transition hover:bg-gray-50"
                        >
                            {processing ? 'Saving...' : 'Save as Draft'}
                        </button>

                        <button
                            type="button"
                            disabled={processing}
                            onClick={handleSubmitForReview}
                            className="flex items-center gap-2 rounded-xl bg-red-700 px-8 py-3 font-semibold text-white shadow-lg transition hover:bg-red-800"
                        >
                            {processing ? (
                                'Submitting...'
                            ) : (
                                <>
                                    Submit for Review
                                    <Send className="h-4 w-4" />
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
