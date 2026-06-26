import { Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import { WorkflowTimeline } from '@/components/WorkflowTimeline';
import {
    getAgreementStatusColor,
    getAgreementStatusLabel,
} from '@/lib/agreement';

export default function UserAgreements() {
    const { user, agreements = [] } = usePage().props as any;

    return (
        <AdminLayout>
            <div>
                <div className="mb-6">
                    <h1 className="text-4xl font-bold text-red-800">
                        {user.name}'s Agreements
                    </h1>
                    <p className="mt-2 text-gray-600">
                        Agreements this user submitted, uploaded, or received.
                    </p>
                </div>

                <div className="rounded-2xl bg-white p-6 shadow">
                    {agreements.length === 0 ? (
                        <p className="text-gray-500">
                            No agreements found for this user.
                        </p>
                    ) : (
                        <div className="space-y-4">
                            {agreements.map((agreement: any) => (
                                <div
                                    key={agreement.id}
                                    className="rounded-xl border p-4"
                                >
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <Link
                                                href={`/agreements/${agreement.id}`}
                                                className="text-lg font-bold text-red-700 hover:underline"
                                            >
                                                {agreement.title}
                                            </Link>
                                            <p className="mt-1 text-sm text-gray-600">
                                                {agreement.partner_organization}
                                            </p>

                                            <div className="mt-3 text-sm">
                                                <span
                                                    className={`rounded px-2 py-1 text-xs font-semibold ${getAgreementStatusColor(agreement.status)}`}
                                                >
                                                    {getAgreementStatusLabel(
                                                        agreement.status,
                                                    )}
                                                </span>

                                                {agreement.relation_label && (
                                                    <span className="ml-3 rounded bg-yellow-50 px-2 py-1 text-xs font-semibold text-yellow-800">
                                                        {agreement.relation_label}
                                                    </span>
                                                )}

                                                {agreement.received_from && (
                                                    <span className="ml-3 text-xs text-gray-600">
                                                        Received from:{' '}
                                                        <strong>
                                                            {
                                                                agreement.received_from
                                                            }
                                                        </strong>
                                                    </span>
                                                )}

                                                {agreement.versions &&
                                                    agreement.versions.length > 0 &&
                                                    (() => {
                                                        const latest =
                                                            agreement.versions[
                                                                agreement.versions
                                                                    .length - 1
                                                            ];
                                                        const latestUploadedByName =
                                                            typeof latest.uploaded_by ===
                                                            'string'
                                                                ? latest.uploaded_by
                                                                : (latest
                                                                      .uploaded_by
                                                                      ?.name ??
                                                                  latest.uploadedBy
                                                                      ?.name ??
                                                                  'Unknown');

                                                        return (
                                                            <span className="ml-3 text-xs text-gray-600">
                                                                Latest upload:{' '}
                                                                <strong>
                                                                    {
                                                                        latestUploadedByName
                                                                    }
                                                                </strong>
                                                            </span>
                                                        );
                                                    })()}

                                                {agreement.submitted_by && (
                                                    <span className="ml-3 text-xs text-gray-600">
                                                        Submitted by:{' '}
                                                        <strong>
                                                            {agreement.submitted_by}
                                                        </strong>
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex flex-col gap-2">
                                            <Link
                                                href={`/agreements/${agreement.id}`}
                                                className="rounded-lg bg-red-700 px-4 py-2 text-white hover:bg-red-800"
                                            >
                                                Open
                                            </Link>
                                        </div>
                                    </div>

                                    <div className="mt-4 border-t pt-4">
                                        <h4 className="mb-3 text-sm font-semibold text-gray-700">
                                            Track Agreement
                                        </h4>
                                        <WorkflowTimeline
                                            currentStatus={agreement.workflow_status}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
