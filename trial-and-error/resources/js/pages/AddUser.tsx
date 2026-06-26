import { Form, Head, Link } from '@inertiajs/react';
import { Lock, Mail, ShieldCheck, UserCheck } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/AdminLayout';

const roleOptions = [
    { value: 'admin', label: 'Admin' },
    { value: 'coordinator', label: 'Coordinator' },
    {
        value: 'authorized_personnel',
        label: 'Authorized Personnel (Partner Tracking Only)',
    },
];

const coordinatorStageOptions = [
    { value: '', label: 'Select stage (leave empty for sender)' },
    { value: 'legal_assistant_ii', label: 'Legal Assistant II' },
    { value: 'legal_assistant_iii', label: 'Legal Assistant III' },
    { value: 'attorney', label: 'Attorney (handles all attorney stages)' },
    { value: 'administrative_aid', label: 'Administrative Aid' },
    { value: 'president_approval', label: 'President' },
];

export default function AddUser() {
    const [selectedRole, setSelectedRole] = useState('admin');

    return (
        <AdminLayout>
            <Head title="Add User" />

            <div className="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 className="text-4xl font-bold text-red-800">
                        Add User
                    </h1>

                    <p className="mt-2 text-gray-600">
                        Create a new user account for the system.
                    </p>
                </div>

                <Link
                    href="/users"
                    className="inline-flex items-center justify-center rounded-xl border border-red-200 bg-white px-5 py-3 font-bold text-red-700 transition hover:bg-red-50"
                >
                    Back to users
                </Link>
            </div>

            <div className="max-w-3xl rounded-2xl bg-white p-8 shadow">
                <Form
                    action="/users"
                    method="post"
                    resetOnSuccess={['password', 'password_confirmation']}
                    className="space-y-8"
                >
                    {({ processing, errors }) => (
                        <>
                            {/* IDENTITY */}
                            <div className="space-y-4">
                                <div className="flex items-center gap-2 border-b pb-2">
                                    <UserCheck className="h-5 w-5 text-red-600" />
                                    <h2 className="text-lg font-semibold text-gray-800">
                                        Identity
                                    </h2>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="name">
                                        Full Name{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        type="text"
                                        name="name"
                                        required
                                        autoFocus
                                        placeholder="e.g., Juan dela Cruz"
                                        className="rounded-xl"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">
                                        Email Address{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <div className="relative">
                                        <Mail className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                        <Input
                                            id="email"
                                            type="email"
                                            name="email"
                                            required
                                            placeholder="email@example.com"
                                            className="rounded-xl pl-10"
                                        />
                                    </div>
                                    <InputError message={errors.email} />
                                </div>
                            </div>

                            {/* ACCESS */}
                            <div className="space-y-4">
                                <div className="flex items-center gap-2 border-b pb-2">
                                    <ShieldCheck className="h-5 w-5 text-red-600" />
                                    <h2 className="text-lg font-semibold text-gray-800">
                                        Access & Role
                                    </h2>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="role">
                                        Role{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Select
                                        onValueChange={(val) =>
                                            setSelectedRole(val)
                                        }
                                        defaultValue="admin"
                                    >
                                        <SelectTrigger
                                            id="role"
                                            className="rounded-xl"
                                        >
                                            <SelectValue placeholder="Select a role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {roleOptions.map((role) => (
                                                <SelectItem
                                                    key={role.value}
                                                    value={role.value}
                                                >
                                                    {role.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="role"
                                        value={selectedRole}
                                    />
                                    <InputError message={errors.role} />
                                </div>

                                {selectedRole === 'coordinator' && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="coordinator_stage">
                                            Coordinator Stage
                                        </Label>
                                        <Select name="coordinator_stage" defaultValue="">
                                            <SelectTrigger className="rounded-xl">
                                                <SelectValue placeholder="Select a stage" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {coordinatorStageOptions.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.coordinator_stage}
                                        />
                                    </div>
                                )}
                            </div>

                            {/* CREDENTIALS */}
                            <div className="space-y-4">
                                <div className="flex items-center gap-2 border-b pb-2">
                                    <Lock className="h-5 w-5 text-red-600" />
                                    <h2 className="text-lg font-semibold text-gray-800">
                                        Credentials
                                    </h2>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password">
                                        Password{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <div className="relative">
                                        <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                        <Input
                                            id="password"
                                            type="password"
                                            name="password"
                                            required
                                            placeholder="Strong password"
                                            className="rounded-xl pl-10"
                                        />
                                    </div>
                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirm Password{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <div className="relative">
                                        <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                        <Input
                                            id="password_confirmation"
                                            type="password"
                                            name="password_confirmation"
                                            required
                                            placeholder="Re-enter password"
                                            className="rounded-xl pl-10"
                                        />
                                    </div>
                                    <InputError
                                        message={errors.password_confirmation}
                                    />
                                </div>
                            </div>

                            <div className="flex justify-end border-t pt-6">
                                <Button
                                    type="submit"
                                    className="bg-red-700 hover:bg-red-800 rounded-xl px-8"
                                    disabled={processing}
                                >
                                    {processing
                                        ? 'Creating...'
                                        : 'Create User'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AdminLayout>
    );
}
