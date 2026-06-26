// @ts-nocheck

import { Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';

export default function CoordinatorWorkflowDashboard() {
    const {
        legalAssistantII = [],
        legalAssistantIII = [],
        attorneyReview = [],
        adminLogging = [],
        attorneyInitials = [],
        presidentApproval = [],
        activeAgreements = [],
    } = usePage().props as any;

    const AgreementCard = ({ agreement }: any) => (
        <div className="rounded-xl border bg-gray-50 p-4">
            <h3 className="text-lg font-bold text-red-700">
                {agreement.title}
            </h3>

            <p className="mt-1 text-gray-600">
                {agreement.partner_organization}
            </p>

            <p className="mt-2 text-sm text-gray-500">
                File:{' '}
                {agreement.document
                    ? agreement.document.split('/').pop()
                    : 'No uploaded file'}
            </p>

            <div className="mt-3 flex items-center justify-between">
                <span className="rounded-full bg-yellow-100 px-3 py-1 text-sm text-yellow-700">
                    {agreement.workflow_status}
                </span>

                <Link
                    href={`/agreements/${agreement.id}`}
                    className="rounded-lg bg-red-700 px-4 py-2 text-white hover:bg-red-800"
                >
                    Open
                </Link>
            </div>
        </div>
    );

    return (
        <AdminLayout>
            <div className="space-y-10">
                <div className="rounded-2xl bg-white p-6 shadow">
                    <h1 className="text-3xl font-bold text-gray-900">
                        Workflow Approval Monitor
                    </h1>

                    <p className="mt-2 text-sm text-gray-600">
                        Track file handoffs, exact uploaded filenames, and
                        approval status across every workflow stage.
                    </p>
                </div>

                {/* LEGAL ASSISTANT II */}
                <div className="rounded-2xl bg-white p-6 shadow">
                    <h2 className="mb-6 text-2xl font-bold text-yellow-600">
                        Pending for Legal Assistant II
                    </h2>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {legalAssistantII.length === 0 ? (
                            <p>No agreements available.</p>
                        ) : (
                            legalAssistantII.map((agreement: any) => (
                                <AgreementCard
                                    key={agreement.id}
                                    agreement={agreement}
                                />
                            ))
                        )}
                    </div>
                </div>

                {/* LEGAL ASSISTANT III */}
                <div className="rounded-2xl bg-white p-6 shadow">
                    <h2 className="mb-6 text-2xl font-bold text-orange-600">
                        Pending for Legal Assistant III
                    </h2>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {legalAssistantIII.length === 0 ? (
                            <p>No agreements available.</p>
                        ) : (
                            legalAssistantIII.map((agreement: any) => (
                                <AgreementCard
                                    key={agreement.id}
                                    agreement={agreement}
                                />
                            ))
                        )}
                    </div>
                </div>

                {/* ATTORNEY */}
                <div className="rounded-2xl bg-white p-6 shadow">
                    <h2 className="mb-6 text-2xl font-bold text-purple-700">
                        Pending for Attorney Review
                    </h2>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {attorneyReview.length === 0 ? (
                            <p>No agreements available.</p>
                        ) : (
                            attorneyReview.map((agreement: any) => (
                                <AgreementCard
                                    key={agreement.id}
                                    agreement={agreement}
                                />
                            ))
                        )}
                    </div>
                </div>

                {/* APPROVED */}
                <div className="rounded-2xl bg-white p-6 shadow">
                    <h2 className="mb-6 text-2xl font-bold text-blue-700">
                        Administrative Aid Logging
                    </h2>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {adminLogging.length === 0 ? (
                            <p>No agreements available.</p>
                        ) : (
                            adminLogging.map((agreement: any) => (
                                <AgreementCard
                                    key={agreement.id}
                                    agreement={agreement}
                                />
                            ))
                        )}
                    </div>
                </div>

                <div className="rounded-2xl bg-white p-6 shadow">
                    <h2 className="mb-6 text-2xl font-bold text-indigo-700">
                        Attorney Initials
                    </h2>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {attorneyInitials.length === 0 ? (
                            <p>No agreements available.</p>
                        ) : (
                            attorneyInitials.map((agreement: any) => (
                                <AgreementCard
                                    key={agreement.id}
                                    agreement={agreement}
                                />
                            ))
                        )}
                    </div>
                </div>

                <div className="rounded-2xl bg-white p-6 shadow">
                    <h2 className="mb-6 text-2xl font-bold text-pink-700">
                        President Approval
                    </h2>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {presidentApproval.length === 0 ? (
                            <p>No agreements available.</p>
                        ) : (
                            presidentApproval.map((agreement: any) => (
                                <AgreementCard
                                    key={agreement.id}
                                    agreement={agreement}
                                />
                            ))
                        )}
                    </div>
                </div>

                {/* ACTIVE AGREEMENTS */}
                <div className="rounded-2xl bg-white p-6 shadow">
                    <h2 className="mb-6 text-2xl font-bold text-green-700">
                        Active Agreements
                    </h2>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {activeAgreements.length === 0 ? (
                            <p>No active agreements yet.</p>
                        ) : (
                            activeAgreements.map((agreement: any) => (
                                <AgreementCard
                                    key={agreement.id}
                                    agreement={agreement}
                                />
                            ))
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
