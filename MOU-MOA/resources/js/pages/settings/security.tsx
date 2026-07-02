import { Head } from '@inertiajs/react';

export default function Security() {
    return (
        <>
            <Head title="Security" />
            <div className="p-6">
                <h1 className="text-2xl font-semibold">Security Settings</h1>
                <p className="text-sm text-gray-600">
                    Manage your password and two-factor authentication settings.
                </p>
            </div>
        </>
    );
}
