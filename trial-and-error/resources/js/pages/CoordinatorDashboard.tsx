// @ts-nocheck

import { Link, usePage } from '@inertiajs/react';
import PartnershipAnalyticsPanel from '@/components/PartnershipAnalyticsPanel';
import AdminLayout from '@/layouts/AdminLayout';

export default function CoordinatorDashboard() {
    const {
        stats,
        analytics,
        expiringSoon = [],
        expired = [],
        workflowItems = [],
        recentActivities = [],
        notifications = [],
    } = usePage().props as any;

    const props = usePage().props as any;
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

    const canCreateAgreement =
        props.auth?.user?.coordinator_stage === null &&
        props.auth?.user?.coordinator_stage !== undefined;

    return (
        <AdminLayout>
            <div className="space-y-10">
                {/* HEADER */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-4xl font-bold text-red-800">
                            MOA/MOU Tracking Dashboard
                        </h1>

                        <p className="mt-2 text-gray-600">
                            Agreement lifecycle monitoring and workflow tracking
                        </p>
                    </div>

                    <div className="flex gap-3">
                        <div className="flex gap-4">
                            {showAgreementsNav && canCreateAgreement && (
                                <>
                                    <Link
                                        href="/agreements/create"
                                        className="rounded-xl bg-red-700 px-6 py-3 font-semibold text-white shadow hover:bg-red-800"
                                    >
                                        Add Agreement
                                    </Link>

                                    <Link
                                        href="/agreements"
                                        className="rounded-xl bg-yellow-400 px-6 py-3 font-semibold text-black shadow hover:bg-yellow-500"
                                    >
                                        View Agreements
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </div>

                <PartnershipAnalyticsPanel
                    analytics={analytics}
                    title="Partner Coordinator Reports & Analytics"
                />

                {/* OBJECTIVE 2 */}
                <div>
                    <h2 className="mb-6 text-2xl font-bold text-red-700">
                        Digital Repository & Agreement Lifecycle Monitoring
                    </h2>

                    <p className="mb-6 text-gray-600">
                        Centralized tracking of institution-wide MOA/MOU
                        records, agreement status, partner scope, and expiration
                        reminders.
                    </p>

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <div className="rounded-2xl border-l-8 border-blue-500 bg-white p-6 shadow">
                            <h3 className="text-gray-500">Total Agreements</h3>

                            <p className="mt-4 text-5xl font-bold text-blue-600">
                                {stats.total}
                            </p>
                        </div>

                        <div className="rounded-2xl border-l-8 border-yellow-500 bg-white p-6 shadow">
                            <h3 className="text-gray-500">For Review</h3>

                            <p className="mt-4 text-5xl font-bold text-yellow-500">
                                {stats.for_review}
                            </p>
                        </div>

                        <div className="rounded-2xl border-l-8 border-green-500 bg-white p-6 shadow">
                            <h3 className="text-gray-500">Active Agreements</h3>

                            <p className="mt-4 text-5xl font-bold text-green-600">
                                {stats.active}
                            </p>
                        </div>

                        <div className="rounded-2xl border-l-8 border-red-500 bg-white p-6 shadow">
                            <h3 className="text-gray-500">
                                Expired Agreements
                            </h3>

                            <p className="mt-4 text-5xl font-bold text-red-600">
                                {stats.expired}
                            </p>
                        </div>
                    </div>
                </div>

                {/* OBJECTIVE 7 */}
                <div>
                    <h2 className="mb-6 text-2xl font-bold text-red-700">
                        Workflow Tracking and Routing
                    </h2>

                    <div className="rounded-2xl bg-white p-6 shadow">
                        {workflowItems.length === 0 ? (
                            <p className="text-gray-500">
                                No workflow items currently active.
                            </p>
                        ) : (
                            <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                                {workflowItems.map((item: any) => (
                                    <div
                                        key={item.id}
                                        className="rounded-xl border bg-gray-50 p-5"
                                    >
                                        <h3 className="text-lg font-bold text-red-700">
                                            {item.title}
                                        </h3>

                                        <p className="mt-2 text-gray-600">
                                            {item.partner_organization}
                                        </p>

                                        <div className="mt-4 space-y-2">
                                            <p className="text-sm">
                                                <span className="font-semibold">
                                                    Workflow:
                                                </span>{' '}
                                                {item.workflow_status}
                                            </p>

                                            <p className="text-sm">
                                                <span className="font-semibold">
                                                    Current Handler:
                                                </span>{' '}
                                                {item.current_handler}
                                            </p>

                                            <p className="text-sm">
                                                <span className="font-semibold">
                                                    Status:
                                                </span>{' '}
                                                {item.status}
                                            </p>
                                        </div>

                                        <div className="mt-5">
                                            <Link
                                                href={`/agreements/${item.id}`}
                                                className="rounded-lg bg-red-700 px-4 py-2 text-white hover:bg-red-800"
                                            >
                                                Open Agreement
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                {/* EXPIRING AGREEMENTS */}
                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-2xl border-l-8 border-yellow-500 bg-white p-6 shadow">
                        <h2 className="mb-4 text-2xl font-bold text-yellow-600">
                            Expiring Soon
                        </h2>

                        {expiringSoon.length === 0 ? (
                            <p>No agreements expiring soon.</p>
                        ) : (
                            expiringSoon.map((agreement: any) => (
                                <div
                                    key={agreement.id}
                                    className="mb-3 rounded-xl border p-4"
                                >
                                    <h3 className="font-bold">
                                        {agreement.title}
                                    </h3>

                                    <p className="text-gray-500">
                                        Expires: {agreement.expires_at}
                                    </p>
                                </div>
                            ))
                        )}
                    </div>

                    <div className="rounded-2xl border-l-8 border-red-500 bg-white p-6 shadow">
                        <h2 className="mb-4 text-2xl font-bold text-red-600">
                            Expired Agreements
                        </h2>

                        {expired.length === 0 ? (
                            <p>No expired agreements.</p>
                        ) : (
                            expired.map((agreement: any) => (
                                <div
                                    key={agreement.id}
                                    className="mb-3 rounded-xl border p-4"
                                >
                                    <h3 className="font-bold">
                                        {agreement.title}
                                    </h3>

                                    <p className="text-gray-500">
                                        Expired: {agreement.expires_at}
                                    </p>
                                </div>
                            ))
                        )}
                    </div>
                </div>

                {/* RECENT ACTIVITIES */}
                <div className="rounded-2xl bg-white p-6 shadow">
                    <h2 className="mb-5 text-2xl font-bold text-red-700">
                        Recent Activities
                    </h2>

                    {recentActivities.length === 0 ? (
                        <p>No activities yet.</p>
                    ) : (
                        <div className="space-y-3">
                            {recentActivities.map((activity: any) => (
                                <div
                                    key={activity.id}
                                    className="rounded-lg border-l-4 border-red-600 bg-gray-50 p-4"
                                >
                                    <p className="font-semibold">
                                        {activity.action}
                                    </p>

                                    <p className="text-sm text-gray-600">
                                        {activity.user_name}
                                    </p>

                                    <p className="text-sm text-gray-500">
                                        {activity.agreement_title}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* NOTIFICATIONS */}
                <div className="rounded-2xl bg-white p-6 shadow">
                    <h2 className="mb-5 text-2xl font-bold text-red-700">
                        Notifications
                    </h2>

                    {notifications.length === 0 ? (
                        <p>No notifications available.</p>
                    ) : (
                        <div className="space-y-3">
                            {notifications.map((notification: any) => (
                                <div
                                    key={notification.id}
                                    className="rounded-xl border bg-yellow-50 p-4"
                                >
                                    <h3 className="font-bold text-yellow-700">
                                        {notification.title}
                                    </h3>

                                    <p className="mt-1 text-gray-700">
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
