import { Link } from '@inertiajs/react';
import React from 'react';
import { login, register } from '@/routes';

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <header className="bg-yellow-400 px-6 py-4 shadow-md">
                <div className="mx-auto flex max-w-7xl items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div className="text-2xl font-extrabold text-red-900">
                            MOA-MOU
                        </div>
                        <div className="text-sm text-red-700">
                            Management System
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link
                            href={login()}
                            className="rounded bg-white px-4 py-2 text-red-900 shadow transition hover:bg-gray-100"
                        >
                            Login
                        </Link>

                        <Link
                            href={register()}
                            className="rounded bg-blue-600 px-4 py-2 text-white shadow transition hover:bg-blue-700"
                        >
                            Register
                        </Link>
                    </div>
                </div>
            </header>

            <main className="flex flex-1 items-center justify-center">
                <div className="w-full max-w-5xl px-6 py-24">{children}</div>
            </main>

            <footer className="py-6 text-center text-sm text-muted">
                <div className="mx-auto max-w-7xl">
                    © {new Date().getFullYear()} MOA-MOU Management System
                </div>
            </footer>
        </div>
    );
}
