import {
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

type AnalyticsPartner = {
    partner_organization: string;
    total_partnerships: number;
    active_partnerships: number;
    renewed_partnerships: number;
    expiring_soon: number;
    renewal_rate: number;
    performance_score: number;
};

type AnalyticsSummary = {
    activePartnerships: number;
    upcomingExpirations: number;
    renewedAgreements: number;
    renewalRate: number;
    partnerCount: number;
    partnerPerformance: AnalyticsPartner[];
};

function formatPercentage(value: number): string {
    return `${value.toFixed(1)}%`;
}

export default function PartnershipAnalyticsPanel({
    analytics,
    title = 'Reports & Analytics',
}: {
    analytics: AnalyticsSummary;
    title?: string;
}) {
    const performanceData = analytics.partnerPerformance ?? [];

    return (
        <div className="space-y-6">
            <div className="flex items-end justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-bold text-red-700">{title}</h2>
                    <p className="mt-1 text-gray-600">
                        Automated partnership tracking, renewals, and
                        institutional performance.
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
                    <p className="text-sm text-slate-500">
                        Active Partnerships
                    </p>
                    <p className="mt-3 text-4xl font-bold text-slate-900">
                        {analytics.activePartnerships}
                    </p>
                    <p className="mt-2 text-sm text-slate-500">
                        Current active and renewed agreements.
                    </p>
                </div>

                <div className="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <p className="text-sm text-amber-700">
                        Upcoming Expirations
                    </p>
                    <p className="mt-3 text-4xl font-bold text-amber-700">
                        {analytics.upcomingExpirations}
                    </p>
                    <p className="mt-2 text-sm text-amber-700/80">
                        Agreements expiring within 30 days.
                    </p>
                </div>

                <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <p className="text-sm text-emerald-700">Renewal Rate</p>
                    <p className="mt-3 text-4xl font-bold text-emerald-700">
                        {formatPercentage(analytics.renewalRate)}
                    </p>
                    <p className="mt-2 text-sm text-emerald-700/80">
                        Renewed agreements vs expired agreements.
                    </p>
                </div>

                <div className="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm">
                    <p className="text-sm text-red-700">
                        Institutions Monitored
                    </p>
                    <p className="mt-3 text-4xl font-bold text-red-700">
                        {analytics.partnerCount}
                    </p>
                    <p className="mt-2 text-sm text-red-700/80">
                        Unique partner organizations tracked.
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 className="mb-4 text-xl font-bold text-red-700">
                        Institutional Collaboration Performance
                    </h3>
                    {performanceData.length === 0 ? (
                        <p className="text-gray-500">
                            No partner performance data available yet.
                        </p>
                    ) : (
                        <div className="h-80">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart
                                    data={performanceData}
                                    layout="vertical"
                                    margin={{ left: 16, right: 16 }}
                                >
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        opacity={0.25}
                                    />
                                    <XAxis type="number" />
                                    <YAxis
                                        type="category"
                                        dataKey="partner_organization"
                                        width={140}
                                    />
                                    <Tooltip />
                                    <Bar
                                        dataKey="performance_score"
                                        fill="#b91c1c"
                                        radius={[0, 12, 12, 0]}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    )}
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 className="mb-4 text-xl font-bold text-red-700">
                        Top Partner Scorecard
                    </h3>
                    {performanceData.length === 0 ? (
                        <p className="text-gray-500">
                            No partner scorecard data available yet.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {performanceData.map(
                                (partner: AnalyticsPartner) => (
                                    <div
                                        key={partner.partner_organization}
                                        className="rounded-xl border border-slate-200 p-4"
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div>
                                                <p className="font-semibold text-slate-900">
                                                    {
                                                        partner.partner_organization
                                                    }
                                                </p>
                                                <p className="mt-1 text-sm text-slate-500">
                                                    {
                                                        partner.active_partnerships
                                                    }{' '}
                                                    active,{' '}
                                                    {
                                                        partner.renewed_partnerships
                                                    }{' '}
                                                    renewed,{' '}
                                                    {partner.expiring_soon}{' '}
                                                    expiring soon
                                                </p>
                                            </div>

                                            <div className="text-right">
                                                <p className="text-sm text-slate-500">
                                                    Renewal rate
                                                </p>
                                                <p className="font-semibold text-emerald-700">
                                                    {formatPercentage(
                                                        partner.renewal_rate,
                                                    )}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="mt-3 flex items-center justify-between text-sm text-slate-500">
                                            <span>
                                                Total partnerships:{' '}
                                                {partner.total_partnerships}
                                            </span>
                                            <span>
                                                Score:{' '}
                                                {partner.performance_score}
                                            </span>
                                        </div>
                                    </div>
                                ),
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
