import { Link, usePage } from '@inertiajs/react';
import { Bell, X } from 'lucide-react';
import React, { useState } from 'react';
import ErrorBoundary from '@/components/ErrorBoundary';

export default function AdminLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const props = usePage().props as any;
    const auth = props.auth;
    const rawNotifications =
        props.notifications ?? props.auth?.user?.notifications ?? [];
    const notifications = Array.isArray(rawNotifications)
        ? rawNotifications
        : rawNotifications
          ? Object.values(rawNotifications)
          : [];
    const user = auth?.user ?? null;
    const roleNormalized = (auth?.user?.role_normalized || user?.role || '')
        .toString()
        .toLowerCase()
        .replace(/\s+/g, '_');
    const showAgreementsNavProp =
        typeof props.showAgreementsNav !== 'undefined'
            ? props.showAgreementsNav
            : null;
    const isSystemAdminFallback = Boolean(
        (user?.name &&
            user.name.toString().toLowerCase().includes('system admin')) ||
        (auth?.user?.role &&
            auth.user.role.toString().toLowerCase().includes('system')),
    );

    // Map internal role to partner coordinator grouping
    const partnerRoles = [
        'legal_assistant_ii',
        'legal_assistant_iii',
        'attorney',
        'administrative_aid',
        'president',
    ];

    // Note: normalizeRole converts 'system_admin' -> 'admin', so isAdmin encompasses both
    const isAdmin = roleNormalized === 'admin' || isSystemAdminFallback;
    const isCoordinator = roleNormalized === 'coordinator';
    const isAuthorizedPersonnel = roleNormalized === 'authorized_personnel';
    const isPartnerRole = partnerRoles.includes(roleNormalized);
    const canViewAgreements = !(roleNormalized === 'viewer');

    // Navigation visibility logic
    const showAgreementsNav =
        canViewAgreements &&
        (showAgreementsNavProp === null ? true : showAgreementsNavProp);
    const [showNotifications, setShowNotifications] = useState(false);
    const [localNotifications, setLocalNotifications] =
        useState<any[]>(notifications);

    const rawRole = (auth?.user?.role || '')
        .toString()
        .toLowerCase()
        .replace(/\s+/g, '_');

    const roleDisplay = (() => {
        if (rawRole === 'system_admin') {
            return 'System Admin';
        }

        if (roleNormalized === 'authorized_personnel') {
            return 'Authorized Personnel';
        }

        if (roleNormalized === 'admin') {
            return 'Admin';
        }

        if (
            partnerRoles.includes(roleNormalized) ||
            roleNormalized === 'coordinator'
        ) {
            return 'Partner Coordinator';
        }

        if (rawRole) {
            return rawRole
                .replace(/_/g, ' ')
                .replace(/\b\w/g, (char: string) => char.toUpperCase());
        }

        return 'User';
    })();

    const unreadNotifications = localNotifications.filter(
        (notification) => !notification.is_read,
    );

    const markNotificationRead = async (notificationId: number) => {
        await fetch(`/notifications/${notificationId}/read`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
            },
        });

        setLocalNotifications((currentNotifications) =>
            currentNotifications.map((notification) =>
                notification.id === notificationId
                    ? { ...notification, is_read: true }
                    : notification,
            ),
        );
    };

    const toggleNotifications = async () => {
        const nextOpenState = !showNotifications;
        setShowNotifications(nextOpenState);

        if (nextOpenState && unreadNotifications.length > 0) {
            await Promise.all(
                unreadNotifications.map((notification) =>
                    markNotificationRead(notification.id),
                ),
            );
        }
    };

    return (
        <div className="flex min-h-screen bg-gray-100">
            {/* SIDEBAR */}
            <aside className="flex w-72 flex-col bg-gradient-to-b from-red-900 to-red-700 text-white shadow-2xl">
                {/* LOGO */}
                <div className="border-b border-red-500 p-8">
                    <h1 className="text-4xl font-extrabold tracking-wide text-yellow-400">
                        MOA-MOU
                    </h1>

                    <p className="mt-2 text-sm text-red-100">
                        Management System
                    </p>
                </div>

                {/* NAVIGATION */}
                <nav className="flex-1 space-y-3 px-5 py-8">
                    <Link
                        href="/dashboard"
                        className="block rounded-xl px-5 py-4 font-semibold transition hover:bg-red-800"
                    >
                        Dashboard
                    </Link>

                    {canViewAgreements && !isSystemAdminFallback && (
                        <Link
                            href="/agreements"
                            className="block rounded-xl px-5 py-4 font-semibold transition hover:bg-red-800"
                        >
                            Agreements
                        </Link>
                    )}

                    <Link
                        href="/workflow-dashboard"
                        className="block rounded-xl px-5 py-4 font-semibold transition hover:bg-red-800"
                    >
                        Workflow
                    </Link>

                    {isAdmin && (
                        <Link
                            href="/users"
                            className="block rounded-xl px-5 py-4 font-semibold transition hover:bg-red-800"
                        >
                            Users
                        </Link>
                    )}

                    <Link
                        href="/activity-logs"
                        className="block rounded-xl px-5 py-4 font-semibold transition hover:bg-red-800"
                    >
                        Activity Logs
                    </Link>
                </nav>

                {/* LOGOUT */}
                <div className="border-t border-red-500 p-5">
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="w-full rounded-xl bg-red-500 py-4 font-bold shadow-lg transition hover:bg-red-400"
                    >
                        Logout
                    </Link>
                </div>
            </aside>

            {/* MAIN CONTENT */}
            <main className="flex-1">
                {/* TOPBAR */}
                <header className="flex items-center justify-between bg-yellow-400 px-10 py-5 shadow-md">
                    <div />

                    <div className="flex items-center gap-6">
                        {/* NOTIFICATION BUTTON */}
                        <div className="relative">
                            <button
                                onClick={toggleNotifications}
                                className="relative rounded-full bg-white p-3 shadow transition hover:bg-gray-100"
                                aria-label="Notifications"
                            >
                                <Bell className="h-5 w-5 text-gray-700" />

                                {unreadNotifications.length > 0 && (
                                    <span className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs text-white">
                                        {unreadNotifications.length}
                                    </span>
                                )}
                            </button>

                            {/* DROPDOWN */}
                            {showNotifications && (
                                <div className="absolute right-0 z-50 mt-4 max-h-96 w-96 overflow-y-auto rounded-2xl border bg-white shadow-2xl">
                                    <div className="flex items-center justify-between border-b p-4">
                                        <h2 className="text-lg font-bold text-red-700">
                                            Notifications
                                        </h2>

                                        <button
                                            onClick={() =>
                                                setShowNotifications(false)
                                            }
                                            className="rounded-full p-1 hover:bg-gray-100"
                                            aria-label="Close notifications"
                                        >
                                            <X className="h-4 w-4 text-gray-500" />
                                        </button>
                                    </div>

                                    {unreadNotifications.length > 0 ? (
                                        unreadNotifications.map(
                                            (notification: any) => (
                                                <button
                                                    key={notification.id}
                                                    onClick={() =>
                                                        markNotificationRead(
                                                            notification.id,
                                                        )
                                                    }
                                                    className="w-full border-b p-4 text-left transition hover:bg-gray-50"
                                                >
                                                    <p className="font-bold text-yellow-700">
                                                        {notification.title}
                                                    </p>

                                                    <p className="mt-1 text-sm text-gray-700">
                                                        {notification.message}
                                                    </p>
                                                </button>
                                            ),
                                        )
                                    ) : (
                                        <div className="p-4 text-gray-500">
                                            No notifications available.
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* USER INFO */}
                        <div className="text-right">
                            <p className="font-bold text-red-900">
                                {user?.name ?? 'Guest'}
                            </p>

                            <p className="text-sm text-red-700">
                                {user ? roleDisplay : 'Guest'}
                            </p>
                        </div>

                        {/* AVATAR */}
                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-red-700 text-lg font-bold text-white shadow">
                            {(user?.name ?? 'G').charAt(0)}
                        </div>
                    </div>
                </header>

                {/* PAGE CONTENT */}
                <div className="p-10">
                    <ErrorBoundary>{children}</ErrorBoundary>
                </div>
            </main>
        </div>
    );
}
