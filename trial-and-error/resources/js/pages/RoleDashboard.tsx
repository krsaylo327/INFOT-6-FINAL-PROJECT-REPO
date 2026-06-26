import { Link, usePage } from '@inertiajs/react';
import PartnershipAnalyticsPanel from '@/components/PartnershipAnalyticsPanel';
import AdminLayout from '@/layouts/AdminLayout';
import { getAgreementStatusLabel } from '@/lib/agreement';

export default function RoleDashboard() {
    const props = usePage().props as any;
    const {
        role,
        roleTitle,
        canCreateAgreement,
        analytics,
        expiringSoonPreview = [],
        expiringSoon = [],
        expired = [],
        submittedAgreements = [],
        assignedAgreements = [],
        workflowItems = [],
        finalApprovedAgreements = [],
        recentActivities = [],
        recentAudit = [],
        recentVersions = [],
    } = props;
    const roleNormalized = (props.auth?.user?.role_normalized || role || '')
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

    const partnershipMonitoringCards = [
        {
            label: 'Active partnerships',
            value:
                analytics?.activePartnerships ?? finalApprovedAgreements.length,
            tone: 'bg-slate-50 border-slate-200 text-slate-900',
            hint: 'Currently active or renewed agreements.',
            href: '/agreements?status=active',
        },
        {
            label: 'Expiring soon',
            value: analytics?.upcomingExpirations ?? expiringSoon.length,
            tone: 'bg-amber-50 border-amber-200 text-amber-800',
            hint: 'Agreements expiring within 30 days.',
            href: '/agreements?filter=expiring',
        },
        {
            label: 'Expired',
            value: expired.length,
            tone: 'bg-red-50 border-red-200 text-red-800',
            hint: 'Records that need renewal or closure.',
            href: '/agreements?status=expired',
        },
    ];

    const actionSets: Record<string, Array<any>> = {
        authorized_personnel: [
            {
                title: 'Create Agreement',
                desc: 'Start a new MOA or MOU record.',
                href: '/agreements/create?mode=create',
            },
            {
                title: 'Upload Draft PDF',
                desc: 'Attach an initial draft while creating.',
                href: '/agreements/create?mode=upload',
            },
        ],
        legal_assistant_ii: [
            {
                title: 'Process Agreements',
                desc: 'Open and review agreements.',
                href: '/agreements',
            },
            {
                title: 'Track Workflow',
                desc: 'See agreements currently in your stage.',
                href: '/workflow-dashboard',
            },
        ],
        legal_assistant_iii: [
            {
                title: 'Process Agreements',
                desc: 'Review and forward agreements.',
                href: '/agreements',
            },
            {
                title: 'View History',
                desc: 'Audit trail and version history.',
                href: '/agreements',
            },
        ],
        attorney: [
            {
                title: 'Review Documents',
                desc: 'Open documents for legal review.',
                href: '/agreements',
            },
            {
                title: 'Mark Initials/Release',
                desc: 'Finalize and release agreements.',
                href: '/agreements',
            },
        ],
        administrative_aid: [
            {
                title: 'Log Agreement',
                desc: 'Record administrative details and timestamps.',
                href: '/agreements',
            },
            {
                title: 'Assign Back',
                desc: 'Return documents to handlers.',
                href: '/agreements',
            },
        ],
        president: [
            {
                title: 'Approve',
                desc: 'Provide final approval for agreements.',
                href: '/agreements',
            },
            {
                title: 'View Final',
                desc: 'Download final approved agreements.',
                href: '/agreements?status=active',
            },
        ],
    };

    const activeRole = role || props.auth?.user?.role_normalized || '';
    const roleKey = activeRole;
    const cardsForRole = (actionSets[roleKey] ?? []).filter((c: any) => {
        // Hide the inline "Create Agreement" action card for authorized personnel
        if (
            activeRole === 'authorized_personnel' &&
            c.title === 'Create Agreement'
        ) {
            return false;
        }

        return true;
    });

    const isAuthorizedPersonnel = activeRole === 'authorized_personnel';

    const renderAgreementCard = (agreement: any) => (
        <div key={agreement.id} className="rounded-xl border bg-gray-50 p-4">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="text-lg font-bold text-red-700">
                        {agreement.title}
                    </h3>
                    <p className="mt-1 text-gray-600">
                        {agreement.partner_organization}
                    </p>
                    <p className="mt-2 text-sm text-gray-500">
                        Stage:{' '}
                        {agreement.workflow_status?.replaceAll('_', ' ') ||
                            'N/A'}
                    </p>
                    <p className="text-sm text-gray-500">
                        Status: {agreement.status || 'N/A'}
                    </p>
                    {agreement.versions &&
                        agreement.versions.length > 0 &&
                        (() => {
                            const latest =
                                agreement.versions[
                                    agreement.versions.length - 1
                                ];
                            const latestUploadedByName =
                                typeof latest.uploaded_by === 'string'
                                    ? latest.uploaded_by
                                    : (latest.uploaded_by?.name ??
                                      latest.uploadedBy?.name ??
                                      '');

                            return (
                                <p className="mt-2 text-xs text-gray-600">
                                    Latest upload:{' '}
                                    {latestUploadedByName || 'Unknown'}
                                    {latest.uploadedBy?.role && (
                                        <span className="ml-2 rounded bg-yellow-50 px-2 py-1 text-xs font-semibold text-yellow-700">
                                            Draft from {latest.uploadedBy.role}
                                        </span>
                                    )}
                                </p>
                            );
                        })()}
                </div>

                <div className="flex flex-col gap-2 text-right">
                    <Link
                        href={`/agreements/${agreement.id}`}
                        className="rounded-lg bg-red-700 px-4 py-2 text-white hover:bg-red-800"
                    >
                        Open
                    </Link>

                    {agreement.status === 'active' && agreement.document && (
                        <a
                            href={`/agreements/${agreement.id}/download`}
                            target="_blank"
                            rel="noreferrer"
                            className="rounded-lg bg-green-600 px-4 py-2 text-white hover:bg-green-700"
                        >
                            Download Final
                        </a>
                    )}
                </div>
            </div>
        </div>
    );

    return (
        <AdminLayout>
            <div className="space-y-10">
                <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        {roleTitle &&
                            roleTitle !== 'Workflow Dashboard' &&
                            roleTitle !== 'Partner Coordinator Dashboard' &&
                            roleTitle !== 'Authorized Personnel Dashboard' && (
                                <h1 className="text-4xl font-bold text-red-800">
                                    {roleTitle}
                                </h1>
                            )}
                    </div>

                    {canCreateAgreement &&
                        roleTitle !== 'Authorized Personnel Dashboard' && (
                            <Link
                                href="/agreements/create"
                                className="rounded-xl bg-red-700 px-6 py-3 font-semibold text-white shadow hover:bg-red-800"
                            >
                                Create Agreement
                            </Link>
                        )}
                </div>

                {cardsForRole.length > 0 && (
                    <div className="rounded-2xl border-l-8 border-red-500 bg-white p-6 shadow">
                        <div className="grid gap-4 text-sm md:grid-cols-3">
                            {cardsForRole.map((c: any) => (
                                <div
                                    key={c.title}
                                    className="flex flex-col justify-between rounded-xl border p-4"
                                >
                                    <div>
                                        <p className="font-semibold">
                                            {c.title}
                                        </p>
                                        <p className="mt-1 text-gray-500">
                                            {c.desc}
                                        </p>
                                    </div>

                                    {c.href && (
                                        <Link
                                            href={c.href}
                                            className="mt-4 inline-block rounded-lg bg-red-700 px-4 py-2 text-sm text-white hover:bg-red-800"
                                        >
                                            Open
                                        </Link>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {analytics && (
                    <PartnershipAnalyticsPanel
                        analytics={analytics}
                        title={
                            roleTitle?.includes('Partner Coordinator')
                                ? 'Partner Coordinator Reports & Analytics'
                                : 'Reports & Analytics'
                        }
                    />
                )}

                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow">
                    <div className="mb-5 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                        <div>
                            <h2 className="text-2xl font-bold text-red-700">
                                Institutional Repository Monitoring
                            </h2>
                            <p className="mt-1 text-gray-600">
                                Access the centralized MOA/MOU repository with
                                approvals, partner details, and workflow status.
                            </p>
                        </div>

                        <Link
                            href="/agreements"
                            className="inline-flex items-center justify-center rounded-xl bg-red-700 px-4 py-2 font-semibold text-white hover:bg-red-800"
                        >
                            Open records
                        </Link>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        {partnershipMonitoringCards.map((card) => (
                            <Link
                                key={card.label}
                                href={card.href}
                                className={`rounded-2xl border p-5 transition hover:shadow-md ${card.tone}`}
                            >
                                <p className="text-sm font-medium opacity-80">
                                    {card.label}
                                </p>
                                <p className="mt-2 text-4xl font-bold">
                                    {card.value}
                                </p>
                                <p className="mt-2 text-sm opacity-80">
                                    {card.hint}
                                </p>
                            </Link>
                        ))}
                    </div>

                    <div className="mt-6 grid gap-6 lg:grid-cols-2">
                        <div className="rounded-2xl border border-slate-200 p-5">
                            <h3 className="mb-4 font-bold text-slate-900">
                                Expiring Soon
                            </h3>
                            {expiringSoonPreview.length === 0 ? (
                                <p className="text-gray-500">
                                    No agreements are currently nearing
                                    expiration.
                                </p>
                            ) : (
                                <div className="space-y-3">
                                    {expiringSoonPreview.map(
                                        (agreement: any) => (
                                            <div
                                                key={agreement.id}
                                                className="rounded-xl border border-amber-100 bg-amber-50 p-4"
                                            >
                                                <div className="flex items-start justify-between gap-4">
                                                    <div>
                                                        <p className="font-semibold text-slate-900">
                                                            {agreement.title}
                                                        </p>
                                                        <p className="mt-1 text-sm text-slate-600">
                                                            {
                                                                agreement.partner_organization
                                                            }
                                                        </p>
                                                        <p className="mt-2 text-xs text-slate-500">
                                                            {agreement.type} •{' '}
                                                            {getAgreementStatusLabel(
                                                                agreement.workflow_status,
                                                            )}
                                                        </p>
                                                    </div>
                                                    <div className="text-right text-sm font-semibold text-amber-700">
                                                        {agreement.expires_at ||
                                                            'No expiry date'}
                                                    </div>
                                                </div>
                                            </div>
                                        ),
                                    )}
                                </div>
                            )}
                        </div>

                        <div className="rounded-2xl border border-slate-200 p-5">
                            <h3 className="mb-4 font-bold text-slate-900">
                                Operational Summary
                            </h3>
                            <div className="space-y-3 text-sm text-slate-600">
                                <p>
                                    Use the agreement list to monitor title,
                                    partner organization, type, expiration, and
                                    workflow stage in one place.
                                </p>
                                <p>
                                    Renewals and expirations are already tracked
                                    in the analytics panel above, and the
                                    reminder system runs from the backend
                                    scheduler.
                                </p>
                                <p>
                                    Recent workflow activity and version history
                                    remain visible below for audit and approval
                                    tracking.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {submittedAgreements.length > 0 && (
                    <div className="rounded-2xl bg-white p-6 shadow">
                        <h2 className="mb-5 text-2xl font-bold text-red-700">
                            Submitted Agreements
                        </h2>
                        <div className="grid gap-4 lg:grid-cols-2">
                            {submittedAgreements.map(renderAgreementCard)}
                        </div>
                    </div>
                )}

                {assignedAgreements.length > 0 && (
                    <div className="rounded-2xl bg-white p-6 shadow">
                        <h2 className="mb-5 text-2xl font-bold text-red-700">
                            Assigned Agreements
                        </h2>
                        <div className="grid gap-4 lg:grid-cols-2">
                            {assignedAgreements.map(renderAgreementCard)}
                        </div>
                    </div>
                )}

                {workflowItems.length > 0 && !isSystemAdmin && (
                    <div className="rounded-2xl bg-white p-6 shadow">
                        <h2 className="mb-5 text-2xl font-bold text-red-700">
                            Current Workflow Stage
                        </h2>
                        <div className="grid gap-4 lg:grid-cols-2">
                            {workflowItems.map(renderAgreementCard)}
                        </div>
                    </div>
                )}

                {finalApprovedAgreements.length > 0 && (
                    <div className="rounded-2xl bg-white p-6 shadow">
                        <h2 className="mb-5 text-2xl font-bold text-red-700">
                            Final Approved Agreements
                        </h2>
                        <div className="grid gap-4 lg:grid-cols-2">
                            {finalApprovedAgreements.map(renderAgreementCard)}
                        </div>
                    </div>
                )}
                {/** Recent Audit & Versions */}
                <div className="rounded-2xl bg-white p-6 shadow">
                    <h2 className="mb-5 text-2xl font-bold text-red-700">
                        Recent Audit & Versions
                    </h2>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="rounded-xl border p-4">
                            <p className="mb-3 font-semibold">
                                Recent Workflow Actions
                            </p>
                            {recentAudit.length === 0 ? (
                                <p className="text-gray-500">
                                    No recent workflow actions.
                                </p>
                            ) : (
                                recentAudit.map((a: any) => (
                                    <div key={a.id} className="mb-3">
                                        <p className="text-sm font-medium">
                                            {a.action}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {typeof a.performed_by === 'string'
                                                ? a.performed_by
                                                : (a.performed_by?.name ??
                                                  a.performed_by ??
                                                  'Unknown')}{' '}
                                            • {a.created_at}
                                        </p>
                                    </div>
                                ))
                            )}
                        </div>

                        <div className="rounded-xl border p-4">
                            <p className="mb-3 font-semibold">
                                Recent Document Versions
                            </p>
                            {recentVersions.length === 0 ? (
                                <p className="text-gray-500">
                                    No recent versions.
                                </p>
                            ) : (
                                recentVersions.map((v: any) => (
                                    <div key={v.id} className="mb-3">
                                        <p className="text-sm font-medium">
                                            {v.version}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {typeof v.uploaded_by === 'string'
                                                ? v.uploaded_by
                                                : (v.uploaded_by?.name ??
                                                  v.uploadedBy?.name ??
                                                  'Unknown')}{' '}
                                            • {v.created_at}
                                        </p>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
                <div className="rounded-2xl bg-white p-6 shadow">
                    <h2 className="mb-5 text-2xl font-bold text-red-700">
                        Recent Activity
                    </h2>
                    {recentActivities.length === 0 ? (
                        <p className="text-gray-500">No recent activity.</p>
                    ) : (
                        <div className="space-y-3">
                            {recentActivities.map((activity: any) => (
                                <div
                                    key={activity.id}
                                    className="rounded-xl border p-4"
                                >
                                    <p className="font-semibold">
                                        {activity.action}
                                    </p>
                                    <p className="text-sm text-gray-500">
                                        {activity.user_name}
                                    </p>
                                    <p className="text-sm text-gray-600">
                                        {activity.agreement_title}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
