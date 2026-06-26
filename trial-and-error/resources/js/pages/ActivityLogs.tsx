import { usePage } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';

export default function ActivityLogs() {
    const { logs } = usePage().props as any;

    return (
        <AdminLayout>
            <h1 className="mb-6 text-3xl font-bold text-red-700">
                Activity Logs
            </h1>

            {/* administrative actions removed: Clear Activity Logs and Reset Agreements buttons were removed to prevent accidental destructive actions */}

            <div className="rounded-xl bg-white shadow">
                <table className="w-full">
                    <thead className="bg-red-700 text-white">
                        <tr>
                            <th className="p-4 text-left">User</th>
                            <th className="p-4 text-left">Action</th>
                            <th className="p-4 text-left">Agreement</th>
                            <th className="p-4 text-left">Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        {logs.map((log: any) => (
                            <tr key={log.id} className="border-b">
                                <td className="p-4">{log.user_name}</td>
                                <td className="p-4">{log.action}</td>
                                <td className="p-4 text-red-600">
                                    {log.agreement_title}
                                </td>
                                <td className="p-4">
                                    {new Date(log.created_at).toLocaleString()}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
