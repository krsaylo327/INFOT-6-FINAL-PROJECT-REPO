import { Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    ArrowLeft,
    CheckCircle,
    Clock,
    FileText,
    AlertTriangle,
    Clipboard,
    Bell,
    Eye,
    BarChart3,
} from 'lucide-react';
import { AgreementStatusBadge } from '@/components/AgreementStatusBadge';
import { AgreementTypeBadge } from '@/components/AgreementTypeBadge';
import { EmptyState } from '@/components/EmptyState';
import AdminLayout from '@/layouts/AdminLayout';

interface StageDashboardProps {
    stage: string;
    stageName: string;
    stageHandler: string;
    nextStage: string | null;
    prevStage: string;
    agreementsAtStage: any[];
    stats: {
        atStage: number;
        allForReview: number;
        active: number;
        expired: number;
        inReview?: number;
        initials?: number;
        forRelease?: number;
    };
    analytics: any;
    expiringSoon: any[];
    expired: any[];
    recentActivities: any[];
    notifications: any[];
    unreadNotifications: number;
}

export function StageDashboard({
    stage,
    stageName,
    stageHandler,
    nextStage,
    prevStage,
    agreementsAtStage,
    stats,
    analytics,
    expiringSoon,
    expired,
    recentActivities,
    notifications,
    unreadNotifications,
}: StageDashboardProps) {
    const props = usePage().props as any;
    const showAgreementsNav =
        typeof props.showAgreementsNav !== 'undefined'
            ? props.showAgreementsNav
            : true;

    const humanize = (s: string | null | undefined) =>
        (s || '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, (c: string) => c.toUpperCase());

    return (
        <AdminLayout>
            <div className="space-y-10">
                {/* HEADER */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-4xl font-bold text-red-800">
                            {stageName} Dashboard
                        </h1>

                        <p className="mt-2 text-gray-600">
                            You are handling agreements at the{' '}
                            <span className="font-semibold text-red-700">
                                {stageName}
                            </span>{' '}
                            stage
                        </p>
                    </div>

                    <div className="flex gap-3">
                        {showAgreementsNav && (
                            <Link
                                href="/agreements"
                                className="flex items-center gap-2 rounded-xl bg-yellow-400 px-6 py-3 font-semibold text-black shadow hover:bg-yellow-500"
                            >
                                <Eye className="h-4 w-4" />
                                All Agreements
                            </Link>
                        )}
                    </div>
                </div>

                {/* URGENCY BANNER */}
                {agreementsAtStage.length > 0 && (
                    <div className="rounded-2xl border-l-8 border-red-600 bg-red-50 p-5">
                        <p className="text-lg font-semibold text-red-800">
                            You have{' '}
                            <span className="text-2xl font-bold text-red-700">
                                {agreementsAtStage.length}
                            </span>{' '}
                            agreement{agreementsAtStage.length !== 1 ? 's' : ''} awaiting your review
                        </p>
                        <p className="mt-1 text-sm text-red-600">
                            Review and forward or return them below
                        </p>
                    </div>
                )}

                {/* STAGE INFO CARD */}
                <div className="rounded-2xl bg-gradient-to-r from-red-50 to-red-100 border border-red-200 p-6">
                    <div className="flex items-center gap-4">
                        <div className="flex h-14 w-14 items-center justify-center rounded-full bg-red-200">
                            <Clipboard className="h-7 w-7 text-red-700" />
                        </div>
                        <div className="flex-1">
                            <h2 className="text-xl font-bold text-red-800">
                                {stageName}
                            </h2>
                            <p className="mt-1 text-sm text-gray-600">
                                Handler: <span className="font-medium text-gray-800">{stageHandler}</span>
                            </p>
                            {stage === 'attorney' && (
                                <p className="mt-1 text-sm text-gray-600">
                                    Handles:{' '}
                                    <span className="font-medium text-gray-800">
                                        Attorney Review & Initials
                                    </span>
                                </p>
                            )}
                        </div>
                        <div className="flex gap-6">
                            {prevStage && (
                                <div className="flex items-center gap-2 rounded-lg bg-white px-4 py-2">
                                    <ArrowLeft className="h-4 w-4 text-amber-600" />
                                    <div>
                                        <p className="text-xs text-gray-500">Return to</p>
                                        <p className="font-semibold text-gray-800 capitalize">
                                            {prevStage.replace('_', ' ')}
                                        </p>
                                    </div>
                                </div>
                            )}
                            {nextStage && (
                                <div className="flex items-center gap-2 rounded-lg bg-white px-4 py-2">
                                    <ArrowRight className="h-4 w-4 text-green-600" />
                                    <div>
                                        <p className="text-xs text-gray-500">Next</p>
                                        <p className="font-semibold text-gray-800 capitalize">
                                            {nextStage.replace('_', ' ')}
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                    {stage === 'attorney' && (
                        <div className="mt-4 flex items-center gap-2 text-sm text-gray-600">
                            <span className="font-medium">Forward path:</span>
                            <span className="rounded bg-blue-100 px-2 py-0.5 text-blue-700">
                                Attorney Review → Admin Aid → Initials → President
                            </span>
                        </div>
                    )}
                </div>

                {/* STATS */}
                {stage === 'attorney' && stats.inReview !== undefined ? (
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-4">
                        <div className="rounded-2xl border-l-8 border-blue-500 bg-white p-6 shadow">
                            <div className="flex items-center gap-3">
                                <Clock className="h-8 w-8 text-blue-500" />
                                <div>
                                    <h3 className="text-sm text-gray-500">
                                        For Review
                                    </h3>
                                    <p className="mt-1 text-4xl font-bold text-blue-700">
                                        {stats.inReview}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border-l-8 border-yellow-500 bg-white p-6 shadow">
                            <div className="flex items-center gap-3">
                                <AlertTriangle className="h-8 w-8 text-yellow-500" />
                                <div>
                                    <h3 className="text-sm text-gray-500">
                                        Awaiting Initials
                                    </h3>
                                    <p className="mt-1 text-4xl font-bold text-yellow-600">
                                        {stats.initials}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border-l-8 border-green-500 bg-white p-6 shadow">
                            <div className="flex items-center gap-3">
                                <CheckCircle className="h-8 w-8 text-green-500" />
                                <div>
                                    <h3 className="text-sm text-gray-500">
                                        Active
                                    </h3>
                                    <p className="mt-1 text-4xl font-bold text-green-600">
                                        {stats.active}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border-l-8 border-gray-400 bg-white p-6 shadow">
                            <div className="flex items-center gap-3">
                                <AlertTriangle className="h-8 w-8 text-gray-500" />
                                <div>
                                    <h3 className="text-sm text-gray-500">
                                        Expired
                                    </h3>
                                    <p className="mt-1 text-4xl font-bold text-gray-600">
                                        {stats.expired}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-4">
                        <div className="rounded-2xl border-l-8 border-red-500 bg-white p-6 shadow">
                            <div className="flex items-center gap-3">
                                <Clock className="h-8 w-8 text-red-500" />
                                <div>
                                    <h3 className="text-sm text-gray-500">
                                        At This Stage
                                    </h3>
                                    <p className="mt-1 text-4xl font-bold text-red-700">
                                        {stats.atStage}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border-l-8 border-yellow-500 bg-white p-6 shadow">
                            <div className="flex items-center gap-3">
                                <AlertTriangle className="h-8 w-8 text-yellow-500" />
                                <div>
                                    <h3 className="text-sm text-gray-500">
                                        For Review
                                    </h3>
                                    <p className="mt-1 text-4xl font-bold text-yellow-600">
                                        {stats.allForReview}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border-l-8 border-green-500 bg-white p-6 shadow">
                            <div className="flex items-center gap-3">
                                <CheckCircle className="h-8 w-8 text-green-500" />
                                <div>
                                    <h3 className="text-sm text-gray-500">
                                        Active Agreements
                                    </h3>
                                    <p className="mt-1 text-4xl font-bold text-green-600">
                                        {stats.active}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border-l-8 border-gray-400 bg-white p-6 shadow">
                            <div className="flex items-center gap-3">
                                <AlertTriangle className="h-8 w-8 text-gray-500" />
                                <div>
                                    <h3 className="text-sm text-gray-500">
                                        Expired
                                    </h3>
                                    <p className="mt-1 text-4xl font-bold text-gray-600">
                                        {stats.expired}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* AGREEMENTS AT THIS STAGE */}
                <div>
                    <div className="mb-6 flex items-center gap-3">
                        <FileText className="h-6 w-6 text-red-600" />
                        <h2 className="text-2xl font-bold text-red-700">
                            Agreements at {stageName}
                        </h2>
                    </div>

                    {agreementsAtStage.length === 0 ? (
                        <div className="rounded-2xl bg-white p-8 shadow">
                            <EmptyState
                                icon={Clipboard}
                                title="No agreements at this stage"
                                description="You will see agreements here when they are forwarded to you in the workflow."
                            />
                        </div>
                    ) : stage === 'attorney' ? (
                        ['attorney_review', 'attorney_initials'].map((attorneyStage) => {
                            const stageAgreements = agreementsAtStage.filter(
                                (a: any) => a.workflow_status === attorneyStage,
                            );

                            if (stageAgreements.length === 0) {
return null;
}

                            const stageLabel = attorneyStage
                                .replace('attorney_', '')
                                .replace('_', ' ')
                                .replace(/\b\w/g, (c: string) => c.toUpperCase());
                            const stageColor: Record<string, string> = {
                                'attorney_review': 'border-blue-500',
                                'attorney_initials': 'border-yellow-500',
                            };
                            const stageTagColor: Record<string, string> = {
                                'attorney_review': 'bg-blue-100 text-blue-700',
                                'attorney_initials': 'bg-yellow-100 text-yellow-700',
                            };

                            return (
                                <div key={attorneyStage} className="mb-8">
                                    <div className="mb-4 flex items-center gap-3">
                                        <span
                                            className={`rounded-full px-3 py-1 text-sm font-semibold ${stageTagColor[attorneyStage]}`}
                                        >
                                            {stageLabel}
                                        </span>
                                        <span className="text-sm text-gray-500">
                                            {stageAgreements.length} agreement
                                            {stageAgreements.length !== 1 ? 's' : ''}
                                        </span>
                                    </div>
                                    <div className="space-y-4">
                                        {stageAgreements.map((agreement: any) => (
                                            <div
                                                key={agreement.id}
                                                className={`rounded-2xl border-l-8 ${stageColor[attorneyStage]} bg-white p-6 shadow`}
                                            >
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-3">
                                                            <h3 className="text-lg font-bold text-gray-900">
                                                                {agreement.title}
                                                            </h3>
                                                            <AgreementTypeBadge
                                                                type={agreement.type}
                                                            />
                                                            <AgreementStatusBadge
                                                                status={agreement.status}
                                                            />
                                                        </div>

                                                        <p className="mt-2 text-sm text-gray-600">
                                                            {agreement.partner_organization}
                                                        </p>

                                                        <div className="mt-3 flex flex-wrap gap-4">
                                                            <p className="text-sm text-gray-500">
                                                                <span className="font-medium">
                                                                    Stage:
                                                                </span>{' '}
                                                                <span className="capitalize">
                                                                    {stageLabel}
                                                                </span>
                                                            </p>
                                                            {agreement.expires_at && (
                                                                <p className="text-sm text-gray-500">
                                                                    <span className="font-medium">
                                                                        Expires:
                                                                    </span>{' '}
                                                                    {agreement.expires_at}
                                                                </p>
                                                            )}
                                                        </div>

                                                        <div className="mt-3 flex flex-wrap gap-2">
                                                            <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                                                                Return to: {humanize(prevStage)}
                                                            </span>
                                                            <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                                                                Forward to: {humanize(nextStage)}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <Link
                                                        href={`/agreements/${agreement.id}`}
                                                        className="flex items-center gap-2 rounded-xl bg-red-700 px-5 py-3 font-semibold text-white hover:bg-red-800"
                                                    >
                                                        Open Agreement
                                                    </Link>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            );
                        })
                    ) : (
                        <div className="space-y-4">
                            {agreementsAtStage.map((agreement: any) => (
                                <div
                                    key={agreement.id}
                                    className="rounded-2xl bg-white p-6 shadow"
                                >
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3">
                                                <h3 className="text-lg font-bold text-gray-900">
                                                    {agreement.title}
                                                </h3>
                                                <AgreementTypeBadge
                                                    type={agreement.type}
                                                />
                                                <AgreementStatusBadge
                                                    status={agreement.status}
                                                />
                                            </div>

                                            <p className="mt-2 text-sm text-gray-600">
                                                {agreement.partner_organization}
                                            </p>

                                            <div className="mt-3 flex flex-wrap gap-4">
                                                <p className="text-sm text-gray-500">
                                                    <span className="font-medium">
                                                        Current Handler:
                                                    </span>{' '}
                                                    {agreement.current_handler}
                                                </p>
                                                {agreement.expires_at && (
                                                    <p className="text-sm text-gray-500">
                                                        <span className="font-medium">
                                                            Expires:
                                                        </span>{' '}
                                                        {agreement.expires_at}
                                                    </p>
                                                )}
                                                {agreement.signed_at && (
                                                    <p className="text-sm text-gray-500">
                                                        <span className="font-medium">
                                                            Signed:
                                                        </span>{' '}
                                                        {agreement.signed_at}
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        <Link
                                            href={`/agreements/${agreement.id}`}
                                            className="flex items-center gap-2 rounded-xl bg-red-700 px-5 py-3 font-semibold text-white hover:bg-red-800"
                                        >
                                            Open Agreement
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* ANALYTICS */}
                <div className="rounded-2xl bg-white p-6 shadow">
                    <div className="mb-6 flex items-center gap-3">
                        <BarChart3 className="h-6 w-6 text-red-600" />
                        <h2 className="text-xl font-bold text-red-700">
                            Analytics Overview
                        </h2>
                    </div>

                    <div className="grid grid-cols-2 gap-6 md:grid-cols-4">
                        <div>
                            <p className="text-sm text-gray-500">
                                Active Partnerships
                            </p>
                            <p className="mt-1 text-3xl font-bold text-gray-900">
                                {analytics.activePartnerships ?? 0}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">
                                Expiring Soon
                            </p>
                            <p className="mt-1 text-3xl font-bold text-yellow-600">
                                {analytics.upcomingExpirations ?? 0}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">
                                Renewal Rate
                            </p>
                            <p className="mt-1 text-3xl font-bold text-green-600">
                                {analytics.renewalRate ?? 0}%
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">
                                Partner Organizations
                            </p>
                            <p className="mt-1 text-3xl font-bold text-blue-600">
                                {analytics.partnerCount ?? 0}
                            </p>
                        </div>
                    </div>
                </div>

                {/* EXPIRING & EXPIRED */}
                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-2xl border-l-8 border-yellow-500 bg-white p-6 shadow">
                        <h2 className="mb-4 flex items-center gap-2 text-xl font-bold text-yellow-600">
                            <AlertTriangle className="h-5 w-5" />
                            Expiring Soon
                        </h2>

                        {expiringSoon.length === 0 ? (
                            <p className="text-gray-500">No agreements expiring soon.</p>
                        ) : (
                            <div className="space-y-3">
                                {expiringSoon.map((a: any) => (
                                    <div key={a.id} className="rounded-xl border p-4">
                                        <p className="font-semibold text-gray-900">
                                            {a.title}
                                        </p>
                                        <p className="text-sm text-gray-500">
                                            Expires: {a.expires_at}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="rounded-2xl border-l-8 border-red-500 bg-white p-6 shadow">
                        <h2 className="mb-4 flex items-center gap-2 text-xl font-bold text-red-600">
                            <AlertTriangle className="h-5 w-5" />
                            Expired Agreements
                        </h2>

                        {expired.length === 0 ? (
                            <p className="text-gray-500">No expired agreements.</p>
                        ) : (
                            <div className="space-y-3">
                                {expired.map((a: any) => (
                                    <div key={a.id} className="rounded-xl border p-4">
                                        <p className="font-semibold text-gray-900">
                                            {a.title}
                                        </p>
                                        <p className="text-sm text-gray-500">
                                            Expired: {a.expires_at}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                {/* RECENT ACTIVITIES */}
                <div className="rounded-2xl bg-white p-6 shadow">
                    <h2 className="mb-5 flex items-center gap-2 text-xl font-bold text-red-700">
                        <Clock className="h-5 w-5" />
                        Recent Activities
                    </h2>

                    {recentActivities.length === 0 ? (
                        <p className="text-gray-500">No recent activities.</p>
                    ) : (
                        <div className="space-y-3">
                            {recentActivities.map((activity: any) => (
                                <div
                                    key={activity.id}
                                    className="rounded-lg border-l-4 border-red-400 bg-gray-50 p-4"
                                >
                                    <p className="font-semibold text-gray-800">
                                        {activity.action}
                                    </p>
                                    <p className="text-sm text-gray-500">
                                        {activity.user_name} — {activity.agreement_title}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* NOTIFICATIONS */}
                <div className="rounded-2xl bg-white p-6 shadow">
                    <h2 className="mb-5 flex items-center gap-2 text-xl font-bold text-red-700">
                        <Bell className="h-5 w-5" />
                        Notifications
                        {unreadNotifications > 0 && (
                            <span className="ml-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-700 text-xs font-bold text-white">
                                {unreadNotifications}
                            </span>
                        )}
                    </h2>

                    {notifications.length === 0 ? (
                        <p className="text-gray-500">No notifications.</p>
                    ) : (
                        <div className="space-y-3">
                            {notifications.map((notification: any) => (
                                <div
                                    key={notification.id}
                                    className="rounded-xl border border-yellow-200 bg-yellow-50 p-4"
                                >
                                    <h3 className="font-bold text-yellow-700">
                                        {notification.title}
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-700">
                                        {notification.message}
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