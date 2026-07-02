// @ts-nocheck

import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/layouts/AdminLayout';

export default function UploaderReconciliation() {
    const { versions = [], users = [] } = usePage().props as any;

    const [mapping, setMapping] = useState({});

    return (
        <AdminLayout>
            <div>
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-red-700">
                            Uploader Reconciliation
                        </h1>
                        <p className="text-gray-500">
                            Map legacy uploader names to existing users.
                        </p>
                    </div>
                </div>

                <div className="rounded-2xl bg-white p-6 shadow">
                    {versions.length === 0 ? (
                        <p className="text-gray-500">
                            No unmapped versions found.
                        </p>
                    ) : (
                        <table className="min-w-full">
                            <thead className="bg-yellow-400 text-black">
                                <tr>
                                    <th className="px-6 py-3 text-left">
                                        Version
                                    </th>
                                    <th className="px-6 py-3 text-left">
                                        Agreement
                                    </th>
                                    <th className="px-6 py-3 text-left">
                                        Uploaded By (legacy)
                                    </th>
                                    <th className="px-6 py-3 text-left">
                                        Map To User
                                    </th>
                                    <th className="px-6 py-3 text-left">
                                        Action
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                {versions.map((v) => (
                                    <tr key={v.id} className="border-b">
                                        <td className="px-6 py-3">
                                            {v.version}
                                        </td>
                                        <td className="px-6 py-3">
                                            {v.agreement_id}
                                        </td>
                                        <td className="px-6 py-3">
                                            {typeof v.uploaded_by === 'string'
                                                ? v.uploaded_by
                                                : (v.uploaded_by?.name ??
                                                  v.uploadedBy?.name ??
                                                  '')}
                                        </td>
                                        <td className="px-6 py-3">
                                            <select
                                                value={mapping[v.id] || ''}
                                                onChange={(e) =>
                                                    setMapping({
                                                        ...mapping,
                                                        [v.id]: e.target.value,
                                                    })
                                                }
                                                className="rounded border px-3 py-2"
                                            >
                                                <option value="">
                                                    -- select user --
                                                </option>
                                                {users.map((u) => (
                                                    <option
                                                        key={u.id}
                                                        value={u.id}
                                                    >
                                                        {u.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </td>
                                        <td className="px-6 py-3">
                                            <form
                                                method="post"
                                                action="/admin/uploader-reconciliation/map"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="_token"
                                                    value={
                                                        (usePage().props as any)
                                                            .csrf
                                                    }
                                                />
                                                <input
                                                    type="hidden"
                                                    name="version_id"
                                                    value={v.id}
                                                />
                                                <input
                                                    type="hidden"
                                                    name="user_id"
                                                    value={mapping[v.id] || ''}
                                                />
                                                <button
                                                    type="submit"
                                                    disabled={!mapping[v.id]}
                                                    className="rounded bg-blue-600 px-3 py-1 text-white"
                                                >
                                                    Map
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
