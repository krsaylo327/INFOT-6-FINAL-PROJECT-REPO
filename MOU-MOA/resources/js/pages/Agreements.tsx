import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AgreementStatusBadge } from '@/components/AgreementStatusBadge';
import { AgreementTypeBadge } from '@/components/AgreementTypeBadge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AdminLayout from '@/layouts/AdminLayout';
import { matchesAgreementQuickFilter, matchesAgreementSearch } from '@/lib/agreement';

export default function Agreements() {
    const { agreements = [], auth } = usePage().props as any;
    const role = (auth?.user?.role || '')
        .toString()
        .toLowerCase()
        .replace(/\s+/g, '_');

    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState(() => {
        if (typeof window === 'undefined') {
            return '';
        }

        return new URLSearchParams(window.location.search).get('status') || '';
    });
    const [typeFilter, setTypeFilter] = useState('');
    const [quickFilter, setQuickFilter] = useState(() => {
        if (typeof window === 'undefined') {
            return '';
        }

        return new URLSearchParams(window.location.search).get('filter') || '';
    });
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [selectedAgreement, setSelectedAgreement] = useState<any>(null);
    const [processingAgreementId, setProcessingAgreementId] = useState<
        number | null
    >(null);

    const openDisableConfirm = (agreement: any) => {
        setSelectedAgreement(agreement);
        setConfirmOpen(true);
    };

    const doDisableAgreement = async () => {
        if (!selectedAgreement) {
return;
}

        await fetch(`/agreements/${selectedAgreement.id}/disable`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
            },
        });

        setConfirmOpen(false);
        setSelectedAgreement(null);
        window.location.reload();
    };

    const acknowledgeAgreement = async (agreement: any) => {
        if (!agreement) {
return;
}

        setProcessingAgreementId(agreement.id);

        try {
            await fetch(`/agreements/${agreement.id}/forward`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    next_status: 'active_agreement',
                    remarks:
                        'Authorized personnel acknowledged approved agreement',
                }),
            });

            window.location.reload();
        } finally {
            setProcessingAgreementId(null);
        }
    };

    const agreementRows = agreements.flatMap((agreement: any) => {
        const accessEvents = Array.isArray(agreement.access_events)
            ? agreement.access_events
            : [];

        if (accessEvents.length === 0) {
            return [
                {
                    ...agreement,
                    _displayKey: `${agreement.id}-default`,
                    _displayRelation: null,
                    _displayTone: null,
                    _displayDate: null,
                    _displayFrom: null,
                    _displayTo: null,
                },
            ];
        }

        return accessEvents.map((event: any, index: number) => ({
            ...agreement,
            _displayKey: `${agreement.id}-${event.type || 'event'}-${index}`,
            _displayRelation: event.type || null,
            _displayTone: event.tone || null,
            _displayDate: event.date || null,
            _displayFrom: event.from || null,
            _displayTo: event.to || null,
        }));
    });

    const filteredAgreements = agreementRows.filter((agreement: any) => {
        const isDraftAgreement =
            (agreement.status || '').toString() === 'draft';
        const matchesSearch = matchesAgreementSearch(agreement, search);
        const matchesStatus =
            statusFilter === '' ? true : agreement.status === statusFilter;
        const matchesType =
            typeFilter === '' ? true : agreement.type === typeFilter;
        const matchesQuick = (() => {
            if (quickFilter === 'submitted') {
                return agreement._displayRelation
                    ? agreement._displayRelation === 'Submitted'
                    : agreement.submitted_by === userId;
            }

            if (quickFilter === 'assigned') {
                return agreement._displayRelation
                    ? agreement._displayRelation === 'Received'
                    : matchesAgreementQuickFilter(
                          agreement,
                          auth.user ?? {},
                          quickFilter,
                      );
            }

            return matchesAgreementQuickFilter(
                agreement,
                auth.user ?? {},
                quickFilter,
            );
        })();

        if (isDraftAgreement) {
            return false;
        }

        return matchesSearch && matchesStatus && matchesType && matchesQuick;
    });

    const roleNormalized = (auth?.user?.role || '')
        .toString()
        .toLowerCase()
        .replace(/\s+/g, '_');
    const userId = auth?.user?.id;

    function formatAccessDate(value?: string | null) {
        if (!value) {
            return null;
        }

        const parsed = new Date(value);

        if (Number.isNaN(parsed.getTime())) {
            return null;
        }

        return parsed.toLocaleString();
    }

    function getAgreementLabel(agreement: any) {
        if (agreement._displayRelation) {
            return {
                text: agreement._displayRelation,
                tone: agreement._displayTone || 'bg-blue-50 text-blue-800',
                date: formatAccessDate(agreement._displayDate),
                to: agreement._displayTo || null,
                from: agreement._displayFrom || null,
            };
        }

        if (agreement.access_label) {
            return {
                text: agreement.access_label,
                tone: agreement.access_tone || 'bg-blue-50 text-blue-800',
                date: formatAccessDate(agreement.access_date),
                to: agreement.access_to || null,
                from: agreement.access_from || null,
            };
        }

        // Uploaded by current user (latest version)
        if (agreement.versions && agreement.versions.length > 0) {
            const latest = agreement.versions[agreement.versions.length - 1];

            if (
                latest.uploaded_by_id === userId ||
                latest.uploaded_by === (auth?.user?.name || '')
            ) {
                return {
                    text: 'Draft uploaded by you',
                    tone: 'bg-gray-100 text-gray-800',
                };
            }

            if (latest.uploadedBy && latest.uploadedBy.role) {
                return {
                    text: `Draft from ${latest.uploadedBy.role}`,
                    tone: 'bg-yellow-50 text-yellow-800',
                };
            }
        }

        // Submitted by current user
        if (agreement.submitted_by === userId) {
            return {
                text: 'Submitted by you',
                tone: 'bg-blue-50 text-blue-800',
            };
        }

        // Assigned/received for current user's role
        if (roleNormalized === 'attorney') {
            if (
                [
                    'attorney_review',
                    'attorney_initials',
                ].includes((agreement.workflow_status || '').toString())
            ) {
                return {
                    text: 'Assigned to you',
                    tone: 'bg-green-50 text-green-800',
                };
            }
        } else if (
            (agreement.workflow_status || '').toString() === roleNormalized
        ) {
            return {
                text: 'Assigned to you',
                tone: 'bg-green-50 text-green-800',
            };
        }

        return null;
    }

    return (
        <AdminLayout>
            <Head title="Agreements" />

            {/* HEADER */}
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-bold text-red-700">
                        Agreements Management
                    </h1>

                    <p className="mt-1 text-gray-500">
                        View and manage MOA and MOU records
                    </p>
                </div>

                {role === 'coordinator' &&
        auth?.user?.coordinator_stage === null && (
                    <Link
                        href="/agreements/create"
                        className="rounded-lg bg-red-700 px-5 py-2 font-semibold text-white hover:bg-red-800"
                    >
                        Add Agreement
                    </Link>
                )}
            </div>

            <div className="mb-6 rounded-2xl bg-white p-5 shadow">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <input
                        type="text"
                        placeholder="Search title, partner, type, status, or description..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="rounded-lg border px-4 py-3"
                    />

                    {/* STATUS FILTER */}
                    <select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        className="rounded-lg border px-4 py-3"
                    >
                        <option value="">All Status</option>

                        <option value="for_review">For Review</option>

                        <option value="active">Active</option>

                        <option value="renewed">Renewed</option>

                        <option value="expired">Expired</option>

                        <option value="terminated">Terminated</option>

                        <option value="disabled">Disabled</option>
                    </select>

                    {/* TYPE FILTER */}
                    <select
                        value={typeFilter}
                        onChange={(e) => setTypeFilter(e.target.value)}
                        className="rounded-lg border px-4 py-3"
                    >
                        <option value="">All Types</option>

                        <option value="MOA">MOA</option>

                        <option value="MOU">MOU</option>
                    </select>
                </div>

                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-600">
                    <p>
                        Showing{' '}
                        <span className="font-semibold text-gray-900">
                            {filteredAgreements.length}
                        </span>{' '}
                        of{' '}
                        <span className="font-semibold text-gray-900">
                            {agreementRows.length}
                        </span>{' '}
                        records.
                    </p>

                    {(search || quickFilter || statusFilter || typeFilter) && (
                        <button
                            type="button"
                            onClick={() => {
                                setSearch('');
                                setStatusFilter('');
                                setTypeFilter('');
                                setQuickFilter('');
                            }}
                            className="rounded-lg border border-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Clear filters
                        </button>
                    )}
                </div>

                {(quickFilter || statusFilter) && (
                    <div className="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-gray-600">
                        Showing{' '}
                        {quickFilter === 'submitted' && 'submitted agreements'}
                        {quickFilter === 'assigned' && 'assigned agreements'}
                        {quickFilter === 'pending' && 'pending agreements'}
                        {quickFilter === 'expiring' &&
                            'agreements expiring soon'}
                        {!quickFilter && 'filtered agreements'}
                        {statusFilter
                            ? ` with status ${statusFilter.replaceAll('_', ' ')}`
                            : ''}
                        .
                    </div>
                )}
            </div>

            {filteredAgreements.length === 0 && (
                <div className="rounded-2xl bg-white p-8 text-center text-gray-500 shadow">
                    No agreements match the current search and filter criteria.
                </div>
            )}

            {/* TABLE */}
            <div className="overflow-hidden rounded-2xl bg-white shadow">
                <div className="overflow-x-auto">
                    <table className="min-w-full">
                        <thead className="bg-yellow-400 text-black">
                            <tr>
                                <th className="px-6 py-4 text-left">Title</th>

                                <th className="px-6 py-4 text-left">Type</th>

                                <th className="px-6 py-4 text-left">Partner</th>

                                <th className="px-6 py-4 text-left">Status</th>

                                <th className="px-6 py-4 text-left">
                                    Expiration
                                </th>

                                <th className="px-6 py-4 text-left">
                                    Document
                                </th>

                                <th className="px-6 py-4 text-left">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            {filteredAgreements.map((agreement: any) => (
                                <tr
                                    key={agreement._displayKey || agreement.id}
                                    className="border-b hover:bg-gray-50"
                                >
                                    {/* TITLE */}
                                    <td className="px-6 py-4 font-semibold">
                                        <div className="flex items-start gap-3">
                                            <div className="flex-1">
                                                <Link
                                                    href={`/agreements/${agreement.id}`}
                                                    className="text-red-700 hover:underline"
                                                >
                                                    {agreement.title}
                                                </Link>

                                                {/* label */}
                                                {(() => {
                                                    const lbl =
                                                        getAgreementLabel(
                                                            agreement,
                                                        );

                                                    if (!lbl) {
                                                        return null;
                                                    }

                                                    return (
                                                        <div
                                                            className={`mt-2 inline-block rounded px-2 py-1 text-xs font-semibold ${lbl.tone}`}
                                                        >
                                                            <div>
                                                                {lbl.text}
                                                            </div>
                                                            {lbl.date && (
                                                                <div className="mt-1 text-[11px] opacity-80">
                                                                    {lbl.date}
                                                                </div>
                                                            )}
                                                            {lbl.to && (
                                                                <div className="text-[11px] opacity-80">
                                                                    Sent to{' '}
                                                                    {lbl.to}
                                                                </div>
                                                            )}
                                                            {lbl.from && (
                                                                <div className="text-[11px] opacity-80">
                                                                    Received
                                                                    from{' '}
                                                                    {lbl.from}
                                                                </div>
                                                            )}
                                                        </div>
                                                    );
                                                })()}
                                            </div>
                                        </div>
                                    </td>

                                    {/* TYPE */}
                                    <td className="px-6 py-4">
                                        <AgreementTypeBadge
                                            type={agreement.type}
                                        />
                                    </td>

                                    {/* PARTNER */}
                                    <td className="px-6 py-4">
                                        {agreement.partner_organization}
                                    </td>

                                    {/* STATUS */}
                                    <td className="px-6 py-4">
                                        <AgreementStatusBadge
                                            status={agreement.status}
                                        />
                                    </td>

                                    {/* EXPIRATION */}
                                    <td className="px-6 py-4">
                                        {agreement.expires_at || 'N/A'}
                                    </td>

                                    {/* DOCUMENT */}
                                    <td className="px-6 py-4">
                                        {agreement.document ? (
                                            <div className="flex flex-col gap-2">
                                                <div className="flex gap-2">
                                                    <a
                                                        href={`/agreements/${agreement.id}/download`}
                                                        target="_blank"
                                                        className="rounded bg-green-500 px-3 py-1 text-xs text-white hover:bg-green-600"
                                                    >
                                                        View
                                                    </a>

                                                    <a
                                                        href={`/agreements/${agreement.id}/download`}
                                                        download
                                                        className="rounded bg-blue-500 px-3 py-1 text-xs text-white hover:bg-blue-600"
                                                    >
                                                        Download
                                                    </a>
                                                </div>

                                                {/* Show uploader info if available (latest version or submitter) */}
                                                <div className="text-xs text-gray-600">
                                                    {agreement.versions &&
                                                    agreement.versions.length >
                                                        0 ? (
                                                        (() => {
                                                            const latest =
                                                                agreement
                                                                    .versions[
                                                                    agreement
                                                                        .versions
                                                                        .length -
                                                                        1
                                                                ];
                                                            const latestUploadedByName =
                                                                typeof latest.uploaded_by ===
                                                                'string'
                                                                    ? latest.uploaded_by
                                                                    : (latest
                                                                          .uploaded_by
                                                                          ?.name ??
                                                                      latest
                                                                          .uploadedBy
                                                                          ?.name ??
                                                                      '');

                                                            return (
                                                                <span>
                                                                    Draft
                                                                    uploaded by{' '}
                                                                    <strong>
                                                                        {latestUploadedByName ||
                                                                            'Unknown'}
                                                                    </strong>
                                                                    {latest
                                                                        .uploadedBy
                                                                        ?.role && (
                                                                        <span className="ml-2 rounded bg-yellow-50 px-2 py-1 text-xs font-semibold text-yellow-700">
                                                                            {
                                                                                latest
                                                                                    .uploadedBy
                                                                                    .role
                                                                            }
                                                                        </span>
                                                                    )}
                                                                </span>
                                                            );
                                                        })()
                                                    ) : agreement.submitted_by ? (
                                                        <span>
                                                            Submitted by{' '}
                                                            <strong>
                                                                {
                                                                    agreement.submitted_by
                                                                }
                                                            </strong>
                                                        </span>
                                                    ) : (
                                                        <span className="text-gray-400">
                                                            No uploader info
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        ) : (
                                            <span className="text-sm text-gray-400">
                                                No file
                                            </span>
                                        )}
                                    </td>

                                    {/* ACTIONS */}
                                    <td className="px-6 py-4">
                                        {role === 'admin' ||
                                        role === 'system_admin' ? (
                                            <span className="text-sm text-gray-500">
                                                View Only
                                            </span>
                                        ) : (
                                            <div className="flex flex-wrap gap-2">
                                                <Link
                                                    href={`/agreements/${agreement.id}/edit`}
                                                    className="rounded bg-blue-500 px-3 py-1 text-white hover:bg-blue-600"
                                                >
                                                    Edit
                                                </Link>

                                                {/* UPDATE STATUS */}
                                                <button
                                                    onClick={() => {
                                                        const nextStatus =
                                                            prompt(
                                                                'Enter next workflow status:\n\n' +
                                                                    'draft\n' +
                                                                    'for_review\n' +
                                                                    'legal_assistant_ii\n' +
                                                                    'legal_assistant_iii\n' +
                                                                    'attorney_review\n' +
                                                                    'president_office\n' +
                                                                    'released\n' +
                                                                    'active\n' +
                                                                    'renewed\n' +
                                                                    'expired\n' +
                                                                    'terminated',
                                                            );

                                                        if (!nextStatus) {
                                                            return;
                                                        }

                                                        fetch(
                                                            `/agreements/${agreement.id}/status`,
                                                            {
                                                                method: 'PATCH',
                                                                headers: {
                                                                    'Content-Type':
                                                                        'application/json',
                                                                    'X-CSRF-TOKEN':
                                                                        document
                                                                            .querySelector(
                                                                                'meta[name="csrf-token"]',
                                                                            )
                                                                            ?.getAttribute(
                                                                                'content',
                                                                            ) ||
                                                                        '',
                                                                },
                                                                body: JSON.stringify(
                                                                    {
                                                                        status: nextStatus,
                                                                    },
                                                                ),
                                                            },
                                                        ).then(() =>
                                                            window.location.reload(),
                                                        );
                                                    }}
                                                    className="rounded bg-yellow-500 px-3 py-1 text-white hover:bg-yellow-600"
                                                >
                                                    Update Status
                                                </button>

                                                {/* DISABLE */}
                                                {agreement.status !==
                                                    'disabled' && (
                                                    <>
                                                        <button
                                                            onClick={() =>
                                                                openDisableConfirm(
                                                                    agreement,
                                                                )
                                                            }
                                                            className="rounded bg-red-500 px-3 py-1 text-white hover:bg-red-600"
                                                        >
                                                            Disable
                                                        </button>

                                                        <Dialog
                                                            open={
                                                                confirmOpen &&
                                                                selectedAgreement?.id ===
                                                                    agreement.id
                                                            }
                                                        >
                                                            <DialogContent>
                                                                <DialogHeader>
                                                                    <DialogTitle>
                                                                        Disable
                                                                        agreement
                                                                    </DialogTitle>
                                                                    <DialogDescription>
                                                                        Are you
                                                                        sure you
                                                                        want to
                                                                        disable{' '}
                                                                        <strong>
                                                                            {
                                                                                agreement.title
                                                                            }
                                                                        </strong>
                                                                        ? This
                                                                        will
                                                                        move the
                                                                        agreement
                                                                        to a
                                                                        terminated
                                                                        state.
                                                                    </DialogDescription>
                                                                </DialogHeader>
                                                                <DialogFooter>
                                                                    <Button
                                                                        variant="outline"
                                                                        onClick={() =>
                                                                            setConfirmOpen(
                                                                                false,
                                                                            )
                                                                        }
                                                                    >
                                                                        Cancel
                                                                    </Button>
                                                                    <Button
                                                                        className="ml-2"
                                                                        onClick={
                                                                            doDisableAgreement
                                                                        }
                                                                    >
                                                                        Disable
                                                                    </Button>
                                                                </DialogFooter>
                                                            </DialogContent>
                                                        </Dialog>
                                                    </>
                                                )}
                                            </div>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
