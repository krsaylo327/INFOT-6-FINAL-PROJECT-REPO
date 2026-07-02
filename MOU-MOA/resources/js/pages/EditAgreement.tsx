import { useForm, usePage } from '@inertiajs/react';
import { Save, Send, RefreshCw } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import AdminLayout from '@/layouts/AdminLayout';

export default function EditAgreement() {
    const { agreement } = usePage().props as any;

    const { data, setData, post, processing, errors } = useForm<{
        _method: 'PUT';
        title: string;
        type: string;
        partner_organization: string;
        description: string;
        signed_at: string;
        expires_at: string;
        status: string;
        document: File | null;
    }>({
        _method: 'PUT',
        title: agreement.title || '',
        type: agreement.type || '',
        partner_organization: agreement.partner_organization || '',
        description: agreement.description || '',
        signed_at: agreement.signed_at || '',
        expires_at: agreement.expires_at || '',
        status: agreement.status || '',
        document: null as File | null,
    });

    const submit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        post(`/agreements/${agreement.id}`, {
            forceFormData: true,
        });
    };

    const handleSaveDraft = () => {
        setData('status', 'draft');

        window.requestAnimationFrame(() => {
            post(`/agreements/${agreement.id}`, {
                forceFormData: true,
            });
        });
    };

    const handleSubmitForReview = () => {
        setData('status', 'for_review');

        window.requestAnimationFrame(() => {
            post(`/agreements/${agreement.id}`, {
                forceFormData: true,
            });
        });
    };

    return (
        <AdminLayout>
            <div className="mx-auto max-w-4xl">
                <div className="mb-8">
                    <h1 className="text-4xl font-bold text-red-800">
                        Edit Agreement
                    </h1>

                    <p className="mt-2 text-gray-500">
                        Update agreement details and upload revised files
                    </p>
                </div>

                <form
                    onSubmit={submit}
                    className="space-y-6 rounded-2xl bg-white p-8 shadow"
                >
                    {/* TITLE */}
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">
                            Agreement Title{' '}
                            <span className="text-red-500">*</span>
                        </label>

                        <input
                            type="text"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                        />

                        <InputError message={errors.title} className="mt-1" />
                    </div>

                    {/* TYPE */}
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">
                            Agreement Type
                        </label>

                        <select
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value)}
                            className="w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                        >
                            <option value="">Select Type</option>
                            <option value="MOA">MOA</option>
                            <option value="MOU">MOU</option>
                        </select>

                        <InputError message={errors.type} className="mt-1" />
                    </div>

                    {/* PARTNER */}
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">
                            Partner Organization
                        </label>

                        <input
                            type="text"
                            value={data.partner_organization}
                            onChange={(e) =>
                                setData('partner_organization', e.target.value)
                            }
                            className="w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                        />

                        <InputError
                            message={errors.partner_organization}
                            className="mt-1"
                        />
                    </div>

                    {/* DESCRIPTION */}
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">
                            Description
                        </label>

                        <textarea
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            className="h-28 w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                        />

                        <InputError
                            message={errors.description}
                            className="mt-1"
                        />
                    </div>

                    {/* SIGNED DATE */}
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">
                            Signed Date
                        </label>

                        <input
                            type="date"
                            value={data.signed_at}
                            onChange={(e) =>
                                setData('signed_at', e.target.value)
                            }
                            className="w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                        />

                        <InputError
                            message={errors.signed_at}
                            className="mt-1"
                        />
                    </div>

                    {/* EXPIRATION DATE */}
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">
                            Expiration Date
                        </label>

                        <input
                            type="date"
                            value={data.expires_at}
                            onChange={(e) =>
                                setData('expires_at', e.target.value)
                            }
                            className="w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                        />

                        <InputError
                            message={errors.expires_at}
                            className="mt-1"
                        />
                    </div>

                    {/* STATUS */}
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">
                            Agreement Status
                        </label>

                        <select
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                        >
                            <option value="draft">Draft</option>
                            <option value="for_review">For Review</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="renewed">Renewed</option>
                            <option value="terminated">Terminated</option>
                        </select>

                        <InputError message={errors.status} className="mt-1" />
                    </div>

                    {/* REVISED DOCUMENT */}
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">
                            Upload Revised PDF
                        </label>

                        <input
                            type="file"
                            accept=".pdf"
                            onChange={(e) =>
                                setData('document', e.target.files?.[0] || null)
                            }
                            className="w-full rounded-xl border border-gray-200 px-4 py-3 transition focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                        />

                        <InputError
                            message={errors.document}
                            className="mt-1"
                        />

                        <p className="mt-2 text-sm text-gray-500">
                            Uploading a new PDF will automatically create a
                            document version history.
                        </p>
                    </div>

                    {/* ACTION BUTTONS */}
                    <div className="flex flex-wrap items-center justify-end gap-4 border-t pt-6">
                        <button
                            type="button"
                            disabled={processing}
                            onClick={handleSaveDraft}
                            className="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-6 py-3 font-semibold text-gray-600 transition hover:bg-gray-50"
                        >
                            <Save className="h-4 w-4" />
                            Save Draft
                        </button>

                        <button
                            type="button"
                            disabled={processing}
                            onClick={handleSubmitForReview}
                            className="flex items-center gap-2 rounded-xl bg-red-700 px-6 py-3 font-semibold text-white transition hover:bg-red-800"
                        >
                            <Send className="h-4 w-4" />
                            Submit for Review
                        </button>

                        <button
                            type="submit"
                            disabled={processing}
                            className="flex items-center gap-2 rounded-xl bg-blue-700 px-6 py-3 font-semibold text-white transition hover:bg-blue-800"
                        >
                            <RefreshCw className="h-4 w-4" />
                            Update Agreement
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
