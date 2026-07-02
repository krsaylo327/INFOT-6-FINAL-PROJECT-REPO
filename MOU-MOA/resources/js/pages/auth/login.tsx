import { Head, useForm, Link } from '@inertiajs/react';
import React, { useState } from 'react';

export default function Login() {
    const [showPassword, setShowPassword] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-100">
            <Head title="Login" />

            <div className="w-full max-w-md rounded-xl bg-white p-8 shadow-lg">
                <h2 className="mb-6 text-center text-2xl font-bold text-red-700">
                    Login
                </h2>

                <form onSubmit={submit} className="space-y-4">
                    {/* EMAIL */}
                    <div>
                        <label className="text-sm font-medium">Email</label>
                        <input
                            type="email"
                            className="mt-1 w-full rounded-lg border px-4 py-2"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        {errors.email && (
                            <p className="text-sm text-red-500">
                                {errors.email}
                            </p>
                        )}
                    </div>

                    {/* PASSWORD */}
                    <div>
                        <label className="text-sm font-medium">Password</label>
                        <input
                            type={showPassword ? 'text' : 'password'}
                            className="mt-1 w-full rounded-lg border px-4 py-2"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                        />
                        {errors.password && (
                            <p className="text-sm text-red-500">
                                {errors.password}
                            </p>
                        )}

                        <label className="mt-2 inline-flex items-center text-sm">
                            <input
                                type="checkbox"
                                className="form-checkbox mr-2"
                                checked={showPassword}
                                onChange={() => setShowPassword((v) => !v)}
                            />
                            Show password
                        </label>
                    </div>

                    {/* BUTTON */}
                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-lg bg-red-700 py-2 font-bold text-white hover:bg-red-800"
                    >
                        {processing ? 'Logging in...' : 'Login'}
                    </button>
                </form>

                <p className="mt-4 text-center text-sm">
                    Don't have an account?{' '}
                    <Link href="/register" className="font-medium text-red-700">
                        Sign up
                    </Link>
                </p>
            </div>
        </div>
    );
}
