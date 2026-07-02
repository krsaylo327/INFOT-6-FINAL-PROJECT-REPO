import { Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import { WorkflowTimeline } from '@/components/WorkflowTimeline';

export default function SenderDashboard() {
    const props = usePage().props as any;
    const {
        stats,
        analytics,
        drafts = [],
        submitted = [],
        active = [],
        expiringSoon = [],
        expired = [],
        recentActivities = [],
        notifications = [],
    } = props;

    const unreadCount = props.unreadNotifications ?? 0;

    return (
        <AdminLayout>
            <div className="space-y-10">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-4xl font-bold text-red-800">
                            My Agreements
                        </h1>
                        <p className="mt-2 text-gray-600">
                            Track and manage your submitted agreements
                        </p>
                    </div>

                    <div className="flex gap-3">
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
                            View All Agreements
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-5">
                    <div className="rounded-2xl border-l-8 border-blue-500 bg-white p-6 shadow">
                        <h3 className="text-gray-500">Total</h3>
                        <p className="mt-4 text-5xl font-bold text-blue-600">
                            {stats.total}
                        </p>
                    </div>

                    <div className="rounded-2xl border-l-8 border-gray-400 bg-white p-6 shadow">
                        <h3 className="text-gray-500">Drafts</h3>
                        <p className="mt-4 text-5xl font-bold text-gray-600">
                            {stats.drafts}
                        </p>
                    </div>

                    <div className="rounded-2xl border-l-8 border-yellow-500 bg-white p-6 shadow">
                        <h3 className="text-gray-500">Submitted</h3>
                        <p className="mt-4 text-5xl font-bold text-yellow-500">
                            {stats.submitted}
                        </p>
                    </div>

                    <div className="rounded-2xl border-l-8 border-green-500 bg-white p-6 shadow">
                        <h3 className="text-gray-500">Active</h3>
                        <p className="mt-4 text-5xl font-bold text-green-600">
                            {stats.active}
                        </p>
                    </div>

                    <div className="rounded-2xl border-l-8 border-red-500 bg-white p-6 shadow">
                        <h3 className="text-gray-500">Expired</h3>
                        <p className="mt-4 text-5xl font-bold text-red-600">
                            {stats.expired}
                        </p>
                    </div>
                </div>

                {analytics && (
                    <div className="rounded-2xl bg-white p-6 shadow">
                        <h2 className="mb-4 text-2xl font-bold text-red-700">
                            Analytics
                        </h2>
                        <div className="grid grid-cols-2 gap-6 lg:grid-cols-4">
                            <div>
                                <p className="text-sm text-gray-500">
                                    Total Partners
                                </p>
                                <p className="text-2xl font-bold">
                                    {analytics.totalPartners ?? 0}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">
                                    Active Partners
                                </p>
                                <p className="text-2xl font-bold">
                                    {analytics.activePartners ?? 0}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">
                                    Pending Review
                                </p>
                                <p className="text-2xl font-bold">
                                    {analytics.pendingReview ?? 0}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">
                                    Expiring Soon
                                </p>
                                <p className="text-2xl font-bold">
                                    {analytics.expiringSoon ?? 0}
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="rounded-2xl bg-white p-6 shadow">
                        <h2 className="mb-4 text-xl font-bold text-gray-600">
                            Drafts ({drafts.length})
                        </h2>
                        {drafts.length === 0 ? (
                            <p className="text-gray-400">No drafts</p>
                        ) : (
                            <div className="space-y-3">
                                {drafts.map((a: any) => (
                                    <Link
                                        key={a.id}
                                        href={`/agreements/${a.id}`}
                                        className="block rounded-lg border p-3 hover:bg-gray-50"
                                    >
                                        <p className="font-semibold">{a.title}</p>
                                        <p className="text-sm text-gray-500">
                                            {a.partner_organization}
                                        </p>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="rounded-2xl bg-white p-6 shadow">
                        <h2 className="mb-4 text-xl font-bold text-yellow-600">
                            Submitted ({submitted.length})
                        </h2>
                        {submitted.length === 0 ? (
                            <p className="text-gray-400">No submitted agreements</p>
                        ) : (
                            <div className="space-y-4">
                                {submitted.map((a: any) => (
                                    <div
                                        key={a.id}
                                        className="rounded-lg border p-4"
                                    >
                                        <Link
                                            href={`/agreements/${a.id}`}
                                            className="block hover:bg-gray-50"
                                        >
                                            <p className="font-semibold">{a.title}</p>
                                            <p className="text-sm text-gray-500">
                                                {a.partner_organization}
                                            </p>
                                            <p className="text-xs text-gray-400">
                                                {a.workflow_status}
                                            </p>
                                        </Link>
                                        <div className="mt-3 border-t pt-3">
                                            <h4 className="mb-2 text-xs font-semibold text-gray-600">
                                                Track Agreement
                                            </h4>
                                            <WorkflowTimeline
                                                currentStatus={a.workflow_status}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="rounded-2xl bg-white p-6 shadow">
                        <h2 className="mb-4 text-xl font-bold text-green-600">
                            Active ({active.length})
                        </h2>
                        {active.length === 0 ? (
                            <p className="text-gray-400">No active agreements</p>
                        ) : (
                            <div className="space-y-3">
                                {active.map((a: any) => (
                                    <Link
                                        key={a.id}
                                        href={`/agreements/${a.id}`}
                                        className="block rounded-lg border p-3 hover:bg-gray-50"
                                    >
                                        <p className="font-semibold">{a.title}</p>
                                        <p className="text-sm text-gray-500">
                                            {a.partner_organization}
                                        </p>
                                        <p className="text-xs text-gray-400">
                                            Expires: {a.expires_at}
                                        </p>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-2xl border-l-8 border-yellow-500 bg-white p-6 shadow">
                        <h2 className="mb-4 text-2xl font-bold text-yellow-600">
                            Expiring Soon
                        </h2>
                        {expiringSoon.length === 0 ? (
                            <p>No agreements expiring soon.</p>
                        ) : (
                            expiringSoon.map((a: any) => (
                                <div key={a.id} className="mb-3 rounded-xl border p-4">
                                    <h3 className="font-bold">{a.title}</h3>
                                    <p className="text-gray-500">
                                        Expires: {a.expires_at}
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
                            expired.map((a: any) => (
                                <div key={a.id} className="mb-3 rounded-xl border p-4">
                                    <h3 className="font-bold">{a.title}</h3>
                                    <p className="text-gray-500">
                                        Expired: {a.expires_at}
                                    </p>
                                </div>
                            ))
                        )}
                    </div>
                </div>

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
                                    <p className="font-semibold">{activity.action}</p>
                                    <p className="text-sm text-gray-600">
                                        {activity.user_name}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="rounded-2xl bg-white p-6 shadow">
                    <div className="mb-5 flex items-center justify-between">
                        <h2 className="text-2xl font-bold text-red-700">
                            Notifications
                        </h2>
                        {unreadCount > 0 && (
                            <span className="rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-700">
                                {unreadCount} unread
                            </span>
                        )}
                    </div>
                    {notifications.length === 0 ? (
                        <p>No notifications available.</p>
                    ) : (
                        <div className="space-y-3">
                            {notifications.map((notification: any) => (
                                <div
                                    key={notification.id}
                                    className={`rounded-xl border p-4 ${
                                        notification.is_read
                                            ? 'bg-gray-50'
                                            : 'bg-yellow-50'
                                    }`}
                                >
                                    <h3 className="font-bold">{notification.title}</h3>
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