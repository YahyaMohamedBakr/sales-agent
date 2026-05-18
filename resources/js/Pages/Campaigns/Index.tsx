import { useState, useEffect } from 'react'
import type { Key } from 'react'

const statusColors: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    paused: 'bg-yellow-100 text-yellow-800',
    ended: 'bg-gray-100 text-gray-800',
}

export default function CampaignsIndex() {
    const [campaigns, setCampaigns] = useState<PaginatedData<Campaign> | null>(null)
    const [page, setPage] = useState(1)
    const [statusFilter, setStatusFilter] = useState('')

    useEffect(() => {
        const params = new URLSearchParams({ page: String(page) })
        if (statusFilter) params.set('status', statusFilter)
        fetch(`/api/campaigns?${params}`)
            .then(r => r.json())
            .then(setCampaigns)
    }, [page, statusFilter])

    return (
        <div>
            <h1 className="text-2xl font-semibold text-gray-900 mb-6">Campaigns</h1>

            <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div className="p-4 border-b border-gray-200 flex flex-wrap gap-3">
                    <select value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(1) }}
                        className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="paused">Paused</option>
                        <option value="ended">Ended</option>
                    </select>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Platform</th>
                                <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Leads</th>
                                <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Ad ID</th>
                                <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Created</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {campaigns?.data.map((c: Campaign) => (
                                <tr key={c.id as Key} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 text-sm font-medium text-gray-900">{c.name}</td>
                                    <td className="px-4 py-3">
                                        <span className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${statusColors[c.status] || 'bg-gray-100 text-gray-800'}`}>
                                            {c.status}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-700 capitalize">{c.platform || '-'}</td>
                                    <td className="px-4 py-3 text-sm font-medium">{c.leads_count ?? 0}</td>
                                    <td className="px-4 py-3 text-sm text-gray-500 font-mono">{c.meta_ad_id || '-'}</td>
                                    <td className="px-4 py-3 text-sm text-gray-500">
                                        {new Date(c.created_at).toLocaleDateString('ar-SA')}
                                    </td>
                                </tr>
                            ))}
                            {(!campaigns || campaigns.data.length === 0) && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-12 text-center text-gray-500">No campaigns found</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {campaigns && campaigns.last_page > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t border-gray-200">
                        <div className="text-sm text-gray-500">Showing {campaigns.from}–{campaigns.to} of {campaigns.total}</div>
                        <div className="flex gap-2">
                            <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1}
                                className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button onClick={() => setPage(p => Math.min(campaigns.last_page, p + 1))} disabled={page === campaigns.last_page}
                                className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    )
}
