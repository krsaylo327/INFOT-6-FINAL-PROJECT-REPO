import { Link, usePage } from '@inertiajs/react';
import {
    FileText,
    CheckCircle,
    Clock,
    AlertTriangle,
    Bell,
    Plus,
    Eye,
    ArrowRight,
    Clipboard,
} from 'lucide-react';
import { useState } from 'react';
import { AgreementStatusBadge } from '@/components/AgreementStatusBadge';
import { AgreementTypeBadge } from '@/components/AgreementTypeBadge';
import { WorkflowTimeline } from '@/components/WorkflowTimeline';
import AdminLayout from '@/layouts/AdminLayout';

export default function AuthorizedPersonnelDashboard() {
    const props = usePage().props as any;
    const {
        stats,
        analytics,
        drafts = [],
        inReview = [],
        active = [],
        expiringSoon = [],
        expired = [],
        recentActivities = [],
        notifications = [],
    } = props;

    const unreadCount = props.unreadNotifications ?? 0;

    const [activeTab, setActiveTab] = useState<
        'overview' | 'drafts' | 'in_review' | 'active'
    >('overview');

    const tabs: Array<{ key: string; label: string; count?: number; highlight?: boolean }> = [
        { key: 'overview', label: 'Overview' },
        { key: 'drafts', label: `Drafts (${stats.drafts})`, count: stats.drafts },
        {
            key: 'in_review',
            label: `In Review (${stats.inReview})`,
            count: stats.inReview,
        },
        { key: 'active', label: `Active (${stats.active})`, count: stats.active },
    ];

    const humanize = (s: string | undefined | null) =>
        (s || '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, (c: string) => c.toUpperCase());

    return (
        <AdminLayout>
            <div className="space-y-8">
                {/* HEADER */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-4xl font-bold text-red-800">
                            Authorized Personnel Dashboard
                        </h1>
                        <p className="mt-2 text-gray-600">
                            Create, track, and manage your agreements through the
                            approval workflow
                        </p>
                    </div>

                    <div className="flex gap-3">
                        <Link
                            href="/agreements"
                            className="flex items-center gap-2 rounded-xl bg-yellow-400 px-6 py-3 font-semibold text-black shadow hover:bg-yellow-500"
                        >
                            <Eye className="h-4 w-4" />
                            View All Agreements
                        </Link>
                    </div>
                </div>

                {/* STATS */}
                <div className="grid grid-cols-2 gap-4 md:grid-cols-5">
                    <div className="rounded-2xl border-l-8 border-gray-400 bg-white p-5 shadow">
                        <div className="flex items-center gap-3">
                            <FileText className="h-7 w-7 text-gray-500" />
                            <div>
                                <h3 className="text-xs text-gray-500">Total</h3>
                                <p className="mt-1 text-3xl font-bold text-gray-700">
                                    {stats.total}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl border-l-8 border-blue-500 bg-white p-5 shadow">
                        <div className="flex items-center gap-3">
                            <FileText className="h-7 w-7 text-blue-500" />
                            <div>
                                <h3 className="text-xs text-gray-500">Drafts</h3>
                                <p className="mt-1 text-3xl font-bold text-blue-700">
                                    {stats.drafts}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl border-l-8 border-yellow-500 bg-white p-5 shadow">
                        <div className="flex items-center gap-3">
                            <Clock className="h-7 w-7 text-yellow-500" />
                            <div>
                                <h3 className="text-xs text-gray-500">In Review</h3>
                                <p className="mt-1 text-3xl font-bold text-yellow-600">
                                    {stats.inReview}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl border-l-8 border-green-500 bg-white p-5 shadow">
                        <div className="flex items-center gap-3">
                            <CheckCircle className="h-7 w-7 text-green-500" />
                            <div>
                                <h3 className="text-xs text-gray-500">Active</h3>
                                <p className="mt-1 text-3xl font-bold text-green-700">
                                    {stats.active}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl border-l-8 border-red-500 bg-white p-5 shadow">
                        <div className="flex items-center gap-3">
                            <AlertTriangle className="h-7 w-7 text-red-500" />
                            <div>
                                <h3 className="text-xs text-gray-500">Expired</h3>
                                <p className="mt-1 text-3xl font-bold text-red-700">
                                    {stats.expired}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* TABS */}
                <div className="flex flex-wrap gap-2 border-b border-gray-200 pb-0">
                    {tabs.map((tab) => (
                        <button
                            key={tab.key}
                            onClick={() =>
                                setActiveTab(tab.key as typeof activeTab)
                            }
                            className={`flex items-center gap-2 rounded-t-xl px-4 py-3 text-sm font-semibold transition-colors ${
                                activeTab === tab.key
                                    ? 'border-b-2 border-red-600 text-red-700'
                                    : 'text-gray-500 hover:text-gray-700'
                            } ${
                                tab.highlight
                                    ? 'bg-red-50 text-red-700'
                                    : ''
                            }`}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                {/* TAB CONTENT */}
                <div>
                    {/* DRAFTS */}
                    {activeTab === 'drafts' && (
                        <div className="space-y-4">
                            {drafts.length === 0 ? (
                                <div className="rounded-2xl bg-white p-8 text-center shadow">
                                    <FileText className="mx-auto h-12 w-12 text-gray-300" />
                                    <p className="mt-3 text-gray-500">
                                        No draft agreements
                                    </p>
                                </div>
                            ) : (
                                drafts.map((a: any) => (
                                    <AgreementRow key={a.id} agreement={a} />
                                ))
                            )}
                        </div>
                    )}

                    {/* IN REVIEW */}
                    {activeTab === 'in_review' && (
                        <div className="space-y-4">
                            {inReview.length === 0 ? (
                                <div className="rounded-2xl bg-white p-8 text-center shadow">
                                    <Clock className="mx-auto h-12 w-12 text-gray-300" />
                                    <p className="mt-3 text-gray-500">
                                        No agreements in review
                                    </p>
                                </div>
                            ) : (
                                inReview.map((a: any) => (
                                    <AgreementRow
                                        key={a.id}
                                        agreement={a}
                                        showWorkflowStatus
                                    />
                                ))
                            )}
                        </div>
                    )}

                    {/* ACTIVE */}
                    {activeTab === 'active' && (
                        <div className="space-y-4">
                            {active.length === 0 ? (
                                <div className="rounded-2xl bg-white p-8 text-center shadow">
                                    <CheckCircle className="mx-auto h-12 w-12 text-gray-300" />
                                    <p className="mt-3 text-gray-500">
                                        No active agreements
                                    </p>
                                </div>
                            ) : (
                                active.map((a: any) => (
                                    <AgreementRow
                                        key={a.id}
                                        agreement={a}
                                        showExpires
                                    />
                                ))
                            )}
                        </div>
                    )}

                    {/* OVERVIEW */}
                    {activeTab === 'overview' && (
                        <div className="space-y-6">
                            {/* In Review + Expiring Soon + Recent Activity */}
                            <div className="grid gap-6 lg:grid-cols-2">
                                <div className="rounded-2xl border-l-8 border-yellow-500 bg-white p-6 shadow">
                                    <div className="mb-4 flex items-center gap-2">
                                        <Clock className="h-5 w-5 text-yellow-600" />
                                        <h2 className="text-xl font-bold text-yellow-700">
                                            In Review
                                        </h2>
                                    </div>
                                    {inReview.length === 0 ? (
                                        <p className="text-sm text-gray-400">
                                            No agreements currently in review
                                        </p>
                                    ) : (
                                        <div className="space-y-3">
                                            {inReview.slice(0, 3).map((a: any) => (
                                                <div
                                                    key={a.id}
                                                    className="flex items-center justify-between rounded-lg border p-3"
                                                >
                                                    <div>
                                                        <p className="font-semibold text-gray-800">
                                                            {a.title}
                                                        </p>
                                                        <p className="text-xs text-gray-500">
                                                            {humanize(
                                                                a.workflow_status,
                                                            )}
                                                        </p>
                                                    </div>
                                                    <ArrowRight className="h-4 w-4 text-gray-400" />
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Expiring Soon + Recent Activity */}
                            <div className="grid gap-6 lg:grid-cols-2">
                                <div className="rounded-2xl border-l-8 border-amber-500 bg-white p-6 shadow">
                                    <h2 className="mb-4 flex items-center gap-2 text-xl font-bold text-amber-600">
                                        <AlertTriangle className="h-5 w-5" />
                                        Expiring Soon
                                    </h2>
                                    {expiringSoon.length === 0 ? (
                                        <p className="text-sm text-gray-400">
                                            No agreements expiring within 30 days
                                        </p>
                                    ) : (
                                        <div className="space-y-3">
                                            {expiringSoon.slice(0, 3).map((a: any) => (
                                                <Link
                                                    key={a.id}
                                                    href={`/agreements/${a.id}`}
                                                    className="block rounded-lg border p-3 hover:bg-amber-50"
                                                >
                                                    <p className="font-semibold text-gray-800">
                                                        {a.title}
                                                    </p>
                                                    <p className="text-sm text-amber-600">
                                                        Expires: {a.expires_at}
                                                    </p>
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </div>

                                <div className="rounded-2xl border-l-8 border-gray-300 bg-white p-6 shadow">
                                    <h2 className="mb-4 flex items-center gap-2 text-xl font-bold text-gray-600">
                                        <Clipboard className="h-5 w-5" />
                                        Recent Activity
                                    </h2>
                                    {recentActivities.length === 0 ? (
                                        <p className="text-sm text-gray-400">
                                            No recent activity
                                        </p>
                                    ) : (
                                        <div className="space-y-3">
                                            {recentActivities
                                                .slice(0, 4)
                                                .map((activity: any) => (
                                                    <div
                                                        key={activity.id}
                                                        className="border-l-4 border-gray-300 pl-3"
                                                    >
                                                        <p className="font-semibold text-gray-700">
                                                            {activity.action}
                                                        </p>
                                                        <p className="text-xs text-gray-500">
                                                            {activity.agreement_title}
                                                        </p>
                                                    </div>
                                                ))}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* NOTIFICATIONS */}
                <div className="rounded-2xl bg-white p-6 shadow">
                    <div className="mb-5 flex items-center justify-between">
                        <h2 className="flex items-center gap-2 text-2xl font-bold text-red-700">
                            <Bell className="h-5 w-5" />
                            Notifications
                        </h2>
                        {unreadCount > 0 && (
                            <span className="rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-700">
                                {unreadCount} unread
                            </span>
                        )}
                    </div>
                    {notifications.length === 0 ? (
                        <p className="text-gray-400">No notifications</p>
                    ) : (
                        <div className="space-y-3">
                            {notifications.map((n: any) => (
                                <div
                                    key={n.id}
                                    className={`rounded-xl border p-4 ${
                                        n.is_read
                                            ? 'bg-gray-50'
                                            : 'bg-yellow-50'
                                    }`}
                                >
                                    <h3 className="font-bold">{n.title}</h3>
                                    <p className="mt-1 text-sm text-gray-700">
                                        {n.message}
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

function AgreementRow({
    agreement: a,
    showWorkflowStatus,
    showExpires,
}: {
    agreement: any;
    showWorkflowStatus?: boolean;
    showExpires?: boolean;
}) {
    const humanize = (s: string | undefined | null) =>
        (s || '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, (c: string) => c.toUpperCase());

    return (
        <div className="rounded-2xl bg-white p-5 shadow">
            <div className="flex items-start justify-between">
                <div className="flex-1">
                    <div className="flex items-center gap-3">
                        <h3 className="text-lg font-bold text-gray-900">
                            {a.title}
                        </h3>
                        <AgreementTypeBadge type={a.type} />
                        <AgreementStatusBadge status={a.status} />
                    </div>
                    <p className="mt-1 text-sm text-gray-600">
                        {a.partner_organization}
                    </p>
                    <div className="mt-2 flex flex-wrap gap-3 text-sm text-gray-500">
                        {showWorkflowStatus && (
                            <p>
                                <span className="font-medium">Stage:</span>{' '}
                                {humanize(a.workflow_status)}
                            </p>
                        )}
                        {showExpires && a.expires_at && (
                            <p>
                                <span className="font-medium">Expires:</span>{' '}
                                {a.expires_at}
                            </p>
                        )}
                        {a.signed_at && (
                            <p>
                                <span className="font-medium">Signed:</span>{' '}
                                {a.signed_at}
                            </p>
                        )}
                    </div>
                </div>

                <Link
                    href={`/agreements/${a.id}`}
                    className="flex items-center gap-2 rounded-xl bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800"
                >
                    Open Agreement
                </Link>
            </div>

            {showWorkflowStatus && (
                <div className="mt-4 border-t pt-4">
                    <h4 className="mb-3 text-sm font-semibold text-gray-700">
                        Track Agreement
                    </h4>
                    <WorkflowTimeline currentStatus={a.workflow_status} />
                </div>
            )}
        </div>
    );
}