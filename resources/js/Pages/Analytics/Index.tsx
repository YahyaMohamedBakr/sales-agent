import { useState, useEffect } from 'react'
import type { Key } from 'react'

function PieChart({ data, title }: { data: { source: string; count: number }[]; title: string }) {
    const total = data.reduce((s, d) => s + d.count, 0)
    const colors = ['#6366f1', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#ec4899']
    let cumulative = 0
    return (
        <div className="bg-white rounded-xl border border-gray-200 p-6">
            <h3 className="font-semibold text-gray-900 mb-4">{title}</h3>
            {total === 0 ? <p className="text-sm text-gray-500">No data</p> : (
                <div className="flex items-center gap-6">
                    <svg width="120" height="120" viewBox="0 0 32 32">
                        {data.map((d, i) => {
                            const pct = d.count / total
                            const angle = pct * 360
                            const startAngle = (cumulative / total) * 360
                            cumulative += d.count
                            const x1 = 16 + 14 * Math.cos(((startAngle - 90) * Math.PI) / 180)
                            const y1 = 16 + 14 * Math.sin(((startAngle - 90) * Math.PI) / 180)
                            const x2 = 16 + 14 * Math.cos(((startAngle + angle - 90) * Math.PI) / 180)
                            const y2 = 16 + 14 * Math.sin(((startAngle + angle - 90) * Math.PI) / 180)
                            const largeArc = angle > 180 ? 1 : 0
                            return (
                                <path key={i}
                                    d={`M16,16 L${x1},${y1} A14,14 0 ${largeArc},1 ${x2},${y2} Z`}
                                    fill={colors[i % colors.length]}
                                />
                            )
                        })}
                        <circle cx="16" cy="16" r="8" fill="white" />
                    </svg>
                    <div className="space-y-1.5">
                        {data.map((d, i) => (
                            <div key={d.source as Key} className="flex items-center gap-2 text-sm">
                                <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: colors[i % colors.length] }} />
                                <span className="capitalize">{d.source}</span>
                                <span className="font-medium">{d.count}</span>
                                <span className="text-gray-400">({Math.round((d.count / total) * 100)}%)</span>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    )
}

function BarChart({ data, title, color }: { data: { date?: string; count: number; status?: string; name?: string }[]; title: string; color?: string }) {
    const maxVal = Math.max(...data.map(d => d.count), 1)
    return (
        <div className="bg-white rounded-xl border border-gray-200 p-6">
            <h3 className="font-semibold text-gray-900 mb-4">{title}</h3>
            {data.length === 0 ? <p className="text-sm text-gray-500">No data</p> : (
                <div className="flex items-end gap-1.5 h-32">
                    {data.map((d, i) => (
                        <div key={i} className="flex-1 flex flex-col items-center gap-1">
                            <div className="w-full rounded-t" style={{
                                height: `${(d.count / maxVal) * 100}%`,
                                backgroundColor: color || '#6366f1',
                                minHeight: d.count > 0 ? '4px' : '0',
                            }} title={`${d.date || d.name || d.status || ''}: ${d.count}`} />
                            <span className="text-[10px] text-gray-500 truncate w-full text-center">
                                {d.date ? new Date(d.date).toLocaleDateString('ar-SA', { day: 'numeric', month: 'short' }) :
                                 d.name || d.status || ''}
                            </span>
                        </div>
                    ))}
                </div>
            )}
        </div>
    )
}

export default function Analytics() {
    const [overview, setOverview] = useState<OverviewStats | null>(null)
    const [bySource, setBySource] = useState<any[]>([])
    const [byDay, setByDay] = useState<any[]>([])
    const [byStatus, setByStatus] = useState<any[]>([])
    const [topCampaigns, setTopCampaigns] = useState<any[]>([])

    useEffect(() => {
        fetch('/api/analytics/overview').then(r => r.json()).then(setOverview)
        fetch('/api/analytics/leads-by-source').then(r => r.json()).then(setBySource)
        fetch('/api/analytics/leads-by-day?days=30').then(r => r.json()).then(setByDay)
        fetch('/api/analytics/leads-by-status').then(r => r.json()).then(setByStatus)
        fetch('/api/analytics/top-campaigns').then(r => r.json()).then(setTopCampaigns)
    }, [])

    return (
        <div>
            <h1 className="text-2xl font-semibold text-gray-900 mb-6">Analytics</h1>

            {overview && (
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div className="bg-white rounded-xl border border-gray-200 p-5">
                        <div className="text-sm text-gray-500">Total Leads</div>
                        <div className="text-2xl font-bold text-gray-900 mt-1">{overview.total_leads}</div>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-5">
                        <div className="text-sm text-gray-500">Qualified</div>
                        <div className="text-2xl font-bold text-green-600 mt-1">{overview.qualified}</div>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-5">
                        <div className="text-sm text-gray-500">Qualification Rate</div>
                        <div className="text-2xl font-bold text-indigo-600 mt-1">{overview.qualification_rate}%</div>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-5">
                        <div className="text-sm text-gray-500">Conversion Rate</div>
                        <div className="text-2xl font-bold text-emerald-600 mt-1">{overview.conversion_rate}%</div>
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <PieChart data={bySource} title="Leads by Source" />
                <BarChart data={byDay} title="Daily New Leads (30 days)" color="#6366f1" />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <BarChart data={byStatus} title="Leads by Status" color="#10b981" />
                <BarChart data={topCampaigns} title="Top Campaigns" color="#f59e0b" />
            </div>
        </div>
    )
}
