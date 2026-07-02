import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogClose,
} from '@/components/ui/dialog';
import AdminLayout from '@/layouts/AdminLayout';

export default function Users() {
    const props = usePage().props as any;
    const { users, auth } = props;
    const currentRole = (auth?.user?.role_normalized || auth?.user?.role || '')
        .toString()
        .toLowerCase()
        .replace(/\s+/g, '_');
    const isSystemAdminFallback = Boolean(
        auth?.user?.name &&
        auth.user.name.toString().toLowerCase().includes('system admin'),
    );
    const isAdminOrSystem =
        currentRole === 'admin' ||
        currentRole === 'system_admin' ||
        isSystemAdminFallback;
    const canViewAgreements = !(
        currentRole === 'system_admin' || isSystemAdminFallback
    );

    const [confirmOpen, setConfirmOpen] = useState<boolean>(false);
    const [selectedUser, setSelectedUser] = useState<any>(null);

    const openDisableConfirm = (user: any) => {
        setSelectedUser(user);
        setConfirmOpen(true);
    };

    const doDisableUser = async () => {
        if (!selectedUser) {
return;
}

        await fetch(`/users/${selectedUser.id}/disable`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
            },
        });

        setConfirmOpen(false);
        setSelectedUser(null);
        window.location.reload();
    };

    return (
        <AdminLayout>
            {/* PAGE HEADER */}
            <div className="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 className="text-4xl font-bold text-red-800">
                        Users Management
                    </h1>

                    <p className="mt-2 text-gray-600">
                        Manage system users and roles
                    </p>
                </div>

                {isAdminOrSystem && (
                    <Link
                        href="/users/create"
                        className="inline-flex items-center justify-center rounded-xl bg-yellow-400 px-5 py-3 font-bold text-black transition hover:bg-yellow-300"
                    >
                        Add user
                    </Link>
                )}
            </div>

            {/* TABLE */}
            <div className="overflow-hidden rounded-2xl bg-white shadow">
                <table className="min-w-full">
                    <thead className="bg-yellow-400 text-black">
                        <tr>
                            <th className="px-6 py-4 text-left">Name</th>

                            <th className="px-6 py-4 text-left">Email</th>

                            <th className="px-6 py-4 text-left">Role</th>

                            <th className="px-6 py-4 text-left">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        {users.map((user: any) => (
                            <tr
                                key={user.id}
                                className="border-b transition hover:bg-gray-50"
                            >
                                {/* NAME */}
                                <td className="px-6 py-4 font-semibold">
                                    {user.name}
                                </td>

                                {/* EMAIL */}
                                <td className="px-6 py-4">{user.email}</td>

                                {/* ROLE */}
                                <td className="px-6 py-4">
                                    {(() => {
                                        const rawRole = (user?.role || '')
                                            .toString()
                                            .toLowerCase()
                                            .replace(/\s+/g, '_');
                                        const normalized = (
                                            user?.role_normalized ||
                                            user?.role ||
                                            ''
                                        )
                                            .toString()
                                            .toLowerCase()
                                            .replace(/\s+/g, '_');
                                        const display =
                                            rawRole === 'system_admin'
                                                ? 'System Admin'
                                                : normalized ===
                                                    'authorized_personnel'
                                                  ? 'Authorized Personnel'
                                                  : normalized === 'admin'
                                                    ? 'Admin'
                                                    : [
                                                            'legal_assistant_ii',
                                                            'legal_assistant_iii',
                                                            'attorney',
                                                            'administrative_aid',
                                                            'president',
                                                            'coordinator',
                                                        ].includes(normalized)
                                                      ? 'Partner Coordinator'
                                                      : 'Authorized Personnel';

                                        const isAdminDisplay =
                                            rawRole === 'admin' ||
                                            rawRole === 'system_admin' ||
                                            normalized === 'admin';

                                        return (
                                            <span
                                                className={`rounded-full px-3 py-1 text-xs font-semibold uppercase ${isAdminDisplay ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}`}
                                            >
                                                {display}
                                            </span>
                                        );
                                    })()}
                                </td>

                                {/* ACTIONS */}
                                <td className="px-6 py-4">
                                    <div className="flex gap-2">
                                        {canViewAgreements &&
                                            (() => {
                                                const targetRawRole = (
                                                    user?.role || ''
                                                )
                                                    .toString()
                                                    .toLowerCase()
                                                    .replace(/\s+/g, '_');
                                                const targetIsSystemAdmin =
                                                    targetRawRole ===
                                                        'system_admin' ||
                                                    (user?.name || '')
                                                        .toString()
                                                        .toLowerCase()
                                                        .includes(
                                                            'system admin',
                                                        );

                                                if (targetIsSystemAdmin) {
                                                    return null;
                                                }

                                                return (
                                                    <Link
                                                        href={`/users/${user.id}/agreements`}
                                                        className="rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700"
                                                    >
                                                        View Agreements
                                                    </Link>
                                                );
                                            })()}

                                        {user.status !== 'disabled' ? (
                                            isAdminOrSystem ? (
                                                <>
                                                    <button
                                                        onClick={() =>
                                                            openDisableConfirm(
                                                                user,
                                                            )
                                                        }
                                                        className="rounded-lg bg-red-600 px-4 py-2 text-white transition hover:bg-red-700"
                                                    >
                                                        Disable
                                                    </button>

                                                    <Dialog
                                                        open={
                                                            confirmOpen &&
                                                            selectedUser?.id ===
                                                                user.id
                                                        }
                                                    >
                                                        <DialogContent>
                                                            <DialogHeader>
                                                                <DialogTitle>
                                                                    Disable user
                                                                </DialogTitle>
                                                                <DialogDescription>
                                                                    Are you sure
                                                                    you want to
                                                                    disable{' '}
                                                                    <strong>
                                                                        {
                                                                            user.name
                                                                        }
                                                                    </strong>
                                                                    ? This will
                                                                    prevent them
                                                                    from logging
                                                                    in.
                                                                </DialogDescription>
                                                            </DialogHeader>
                                                            <DialogFooter>
                                                                <Button
                                                                    variant="outline"
                                                                    onClick={() =>
                                                                        setConfirmOpen(
                                                                            false,
                                                                        )
                                                                    }
                                                                >
                                                                    Cancel
                                                                </Button>
                                                                <Button
                                                                    className="ml-2"
                                                                    onClick={
                                                                        doDisableUser
                                                                    }
                                                                >
                                                                    Disable
                                                                </Button>
                                                            </DialogFooter>
                                                            <DialogClose />
                                                        </DialogContent>
                                                    </Dialog>
                                                </>
                                            ) : (
                                                <span className="text-sm text-gray-500">
                                                    No actions
                                                </span>
                                            )
                                        ) : (
                                            <span className="font-semibold text-red-500">
                                                Disabled
                                            </span>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
