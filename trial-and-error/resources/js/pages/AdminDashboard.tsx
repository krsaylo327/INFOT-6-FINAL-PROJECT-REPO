import { Link, usePage } from '@inertiajs/react';
import { PieChart, Pie, Cell, Tooltip, ResponsiveContainer } from 'recharts';
import PartnershipAnalyticsPanel from '@/components/PartnershipAnalyticsPanel';
import AdminLayout from '@/layouts/AdminLayout';

export default function AdminDashboard() {
    const props = usePage().props as any;
    const { role, stats, analytics, expiringSoon, expired, recentActivities } =
        props;

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
    const showAgreementsNav =
        !isSystemAdmin &&
        (typeof props.showAgreementsNav !== 'undefined'
            ? props.showAgreementsNav
            : true);

    const data = [
        { name: 'Active', value: stats.active },
        { name: 'Pending', value: stats.pending },
        { name: 'Disabled', value: stats.disabled },
    ];

    const COLORS = ['#22c55e', '#facc15', '#ef4444'];

    return (
        <AdminLayout>
            {/* HEADER */}
            <div className="mb-8 flex items-center justify-between">
                <div>
                    <h1 className="text-4xl font-bold text-red-800">
                        Admin Dashboard
                    </h1>

                    <p className="mt-2 text-gray-600">
                        MOA/MOU Analytics Overview
                    </p>
                </div>

                {showAgreementsNav && (
                    <Link
                        href="/agreements"
                        className="rounded-xl bg-yellow-400 px-6 py-3 font-semibold text-black shadow hover:bg-yellow-500"
                    >
                        Manage Agreements
                    </Link>
                )}
            </div>

            {/* STATS */}
            <div className="mb-10 grid grid-cols-1 gap-6 md:grid-cols-4">
                <div className="rounded-2xl border-l-8 border-blue-500 bg-white p-6 shadow">
                    <p className="text-gray-500">Total Agreements</p>

                    <p className="mt-3 text-4xl font-bold">{stats.total}</p>
                </div>

                <div className="rounded-2xl border-l-8 border-green-500 bg-white p-6 shadow">
                    <p className="text-gray-500">Active</p>

                    <p className="mt-3 text-4xl font-bold text-green-600">
                        {stats.active}
                    </p>
                </div>

                <div className="rounded-2xl border-l-8 border-yellow-500 bg-white p-6 shadow">
                    <p className="text-gray-500">Pending</p>

                    <p className="mt-3 text-4xl font-bold text-yellow-500">
                        {stats.pending}
                    </p>
                </div>

                <div className="rounded-2xl border-l-8 border-red-500 bg-white p-6 shadow">
                    <p className="text-gray-500">Disabled</p>

                    <p className="mt-3 text-4xl font-bold text-red-600">
                        {stats.disabled}
                    </p>
                </div>
            </div>

            <div className="mb-10 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div className="rounded-2xl border-l-8 border-slate-500 bg-white p-6 shadow">
                    <h2 className="mb-4 text-2xl font-bold text-red-700">
                        Digital Repository Overview
                    </h2>

                    <p className="mb-6 text-gray-600">
                        Centralized MOA/MOU repository for institution-wide
                        partnership records, statuses, and workflow history.
                    </p>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p className="text-sm text-slate-500">
                                Active Partnerships
                            </p>
                            <p className="mt-2 text-3xl font-bold text-slate-900">
                                {analytics.activePartnerships}
                            </p>
                        </div>
                        <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                            <p className="text-sm text-amber-700">
                                Upcoming Expirations
                            </p>
                            <p className="mt-2 text-3xl font-bold text-amber-800">
                                {analytics.upcomingExpirations}
                            </p>
                        </div>
                        <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                            <p className="text-sm text-emerald-700">
                                Renewed Agreements
                            </p>
                            <p className="mt-2 text-3xl font-bold text-emerald-900">
                                {analytics.renewedAgreements}
                            </p>
                        </div>
                        <div className="rounded-2xl border border-violet-200 bg-violet-50 p-4">
                            <p className="text-sm text-violet-700">
                                Partner Organizations
                            </p>
                            <p className="mt-2 text-3xl font-bold text-violet-900">
                                {analytics.partnerCount}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="rounded-2xl border-l-8 border-yellow-500 bg-white p-6 shadow">
                    <h2 className="mb-4 text-2xl font-bold text-yellow-700">
                        Expiration Reminder Pipeline
                    </h2>

                    <p className="mb-6 text-gray-600">
                        Automated reminder system alerts concerned offices
                        before agreement expiration and renewal milestones.
                    </p>

                    {expiringSoon.length === 0 ? (
                        <p className="text-gray-500">
                            No agreements are currently expiring soon.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {expiringSoon.slice(0, 5).map((agreement: any) => (
                                <div
                                    key={agreement.id}
                                    className="rounded-xl border bg-yellow-50 p-4"
                                >
                                    <p className="font-semibold text-slate-900">
                                        {agreement.title}
                                    </p>
                                    <p className="text-sm text-slate-600">
                                        Partner:{' '}
                                        {agreement.partner_organization}
                                    </p>
                                    <p className="mt-1 text-sm text-amber-700">
                                        Expires: {agreement.expires_at}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <div className="mb-10">
                <PartnershipAnalyticsPanel
                    analytics={analytics}
                    title="Admin Reports & Analytics"
                />
            </div>

            {/* CHART */}
            <div className="rounded-2xl bg-white p-8 shadow">
                <h2 className="mb-6 text-2xl font-bold text-red-700">
                    Agreement Status Overview
                </h2>

                <div className="h-80 w-full">
                    <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                            <Pie
                                data={data}
                                dataKey="value"
                                nameKey="name"
                                cx="50%"
                                cy="50%"
                                outerRadius={120}
                                label
                            >
                                {data.map((_, index) => (
                                    <Cell key={index} fill={COLORS[index]} />
                                ))}
                            </Pie>

                            <Tooltip />
                        </PieChart>
                    </ResponsiveContainer>
                </div>
            </div>

            {/* ALERTS */}
            <div className="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">
                {/* EXPIRING */}
                <div className="rounded-2xl border-l-8 border-yellow-500 bg-white p-6 shadow">
                    <h2 className="mb-4 text-2xl font-bold text-yellow-600">
                        Expiring Soon
                    </h2>

                    {expiringSoon.length === 0 ? (
                        <p className="text-gray-500">
                            No agreements expiring soon.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {expiringSoon.map((agreement: any) => (
                                <div
                                    key={agreement.id}
                                    className="rounded-xl border p-4"
                                >
                                    <h3 className="text-lg font-bold">
                                        {agreement.title}
                                    </h3>

                                    <p className="text-gray-500">
                                        Expires: {agreement.expires_at}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* EXPIRED */}
                <div className="rounded-2xl border-l-8 border-red-500 bg-white p-6 shadow">
                    <h2 className="mb-4 text-2xl font-bold text-red-600">
                        Expired Agreements
                    </h2>

                    {expired.length === 0 ? (
                        <p className="text-gray-500">No expired agreements.</p>
                    ) : (
                        <div className="space-y-3">
                            {expired.map((agreement: any) => (
                                <div
                                    key={agreement.id}
                                    className="rounded-xl border p-4"
                                >
                                    <h3 className="text-lg font-bold">
                                        {agreement.title}
                                    </h3>

                                    <p className="text-gray-500">
                                        Expired: {agreement.expires_at}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* RECENT ACTIVITY */}
            <div className="mt-10 rounded-2xl bg-white p-8 shadow">
                <div className="mb-6 flex items-center justify-between">
                    <h2 className="text-2xl font-bold text-red-700">
                        Recent Activity
                    </h2>

                    <Link
                        href="/activity-logs"
                        className="rounded-xl bg-red-700 px-4 py-2 text-white transition hover:bg-red-800"
                    >
                        View All
                    </Link>
                </div>

                <div className="space-y-4">
                    {recentActivities.length > 0 ? (
                        recentActivities.map((activity: any) => (
                            <Link
                                href="/activity-logs"
                                key={activity.id}
                                className="block rounded-lg border-b px-2 py-4 transition hover:bg-gray-50"
                            >
                                <div className="flex justify-between">
                                    <div>
                                        <p className="font-bold">
                                            {activity.user_name}
                                        </p>

                                        <p className="text-gray-600">
                                            {activity.action}
                                        </p>

                                        <p className="text-red-700">
                                            {activity.agreement_title}
                                        </p>
                                    </div>

                                    <p className="text-sm text-gray-500">
                                        {new Date(
                                            activity.created_at,
                                        ).toLocaleString()}
                                    </p>
                                </div>
                            </Link>
                        ))
                    ) : (
                        <p className="text-gray-500">No recent activities.</p>
                    )}
                </div>
            </div>

            {/* QUICK ACTIONS */}
            <div className="mt-10 flex gap-4"></div>
        </AdminLayout>
    );
}
