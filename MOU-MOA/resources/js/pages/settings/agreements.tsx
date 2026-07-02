import { router } from '@inertiajs/react';
import React, { useState } from 'react';
import Layout from '../../layouts/AdminLayout';

export default function AgreementsSettings({
    reminderDays = [],
    notifyRoles = [],
}: any) {
    const [days, setDays] = useState(reminderDays.join(', '));
    const [roles, setRoles] = useState(notifyRoles.join(', '));

    function submit(e: React.FormEvent) {
        e.preventDefault();
        const parsedDays = days
            .split(',')
            .map((d: string) => parseInt(d.trim(), 10))
            .filter(Boolean);
        const parsedRoles = roles
            .split(',')
            .map((r: string) => r.trim())
            .filter(Boolean);

        router.post('/settings/agreements', {
            reminderDays: parsedDays,
            notifyRoles: parsedRoles,
        });
    }

    return (
        <Layout>
            <div className="p-6">
                <h1 className="mb-4 text-2xl font-semibold">
                    Agreement Reminder Settings
                </h1>
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">
                            Reminder days (comma separated)
                        </label>
                        <input
                            value={days}
                            onChange={(e) => setDays(e.target.value)}
                            className="mt-1 block w-full"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700">
                            Notify roles (comma separated)
                        </label>
                        <input
                            value={roles}
                            onChange={(e) => setRoles(e.target.value)}
                            className="mt-1 block w-full"
                        />
                    </div>
                    <div>
                        <button type="submit" className="btn btn-primary">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </Layout>
    );
}
