import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

interface Props {
    children: ReactNode;
}

export default function AppLayout({ children }: Props) {
    return (
        <div className="flex min-h-screen bg-gray-100">
            {/* SIDEBAR */}
            <aside className="flex w-64 flex-col bg-red-700 text-white">
                <div className="border-b border-red-600 p-6">
                    <h1 className="text-3xl font-bold text-yellow-300">
                        MOA-MOU
                    </h1>

                    <p className="text-sm text-red-100">Management System</p>
                </div>

                <nav className="flex-1 space-y-2 p-4">
                    <Link
                        href="/dashboard"
                        className="block rounded bg-yellow-400 px-4 py-3 font-bold text-red-900"
                    >
                        Dashboard
                    </Link>

                    <Link
                        href="/dashboard"
                        className="block rounded px-4 py-3 hover:bg-red-600"
                    >
                        Agreements
                    </Link>

                    <Link
                        href="/dashboard"
                        className="block rounded px-4 py-3 hover:bg-red-600"
                    >
                        Reports
                    </Link>

                    <Link
                        href="/dashboard"
                        className="block rounded px-4 py-3 hover:bg-red-600"
                    >
                        Users
                    </Link>
                </nav>

                <div className="border-t border-red-600 p-4">
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="w-full rounded bg-black px-4 py-2 text-white hover:bg-gray-900"
                    >
                        Logout
                    </Link>
                </div>
            </aside>

            {/* CONTENT */}
            <div className="flex flex-1 flex-col">
                {/* TOPBAR */}
                <header className="flex items-center justify-between bg-yellow-400 px-6 py-4 shadow">
                    <h2 className="text-3xl font-bold text-red-900">
                        Dashboard
                    </h2>

                    <div className="font-semibold text-red-900">
                        Administrator
                    </div>
                </header>

                {/* PAGE CONTENT */}
                <main className="flex-1 p-6">{children}</main>
            </div>
        </div>
    );
}
