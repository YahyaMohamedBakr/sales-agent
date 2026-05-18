import { useState, useEffect } from 'react'
import { Link } from '@inertiajs/react'
import type { Key } from 'react'

const statusColors: Record<string, string> = {
    new: 'bg-blue-100 text-blue-800',
    contacted: 'bg-yellow-100 text-yellow-800',
    qualifying: 'bg-purple-100 text-purple-800',
    qualified: 'bg-green-100 text-green-800',
    converted: 'bg-emerald-100 text-emerald-800',
    lost: 'bg-red-100 text-red-800',
}

export default function LeadsIndex() {
    const [leads, setLeads] = useState<PaginatedData<Lead> | null>(null)
    const [page, setPage] = useState(1)
    const [search, setSearch] = useState('')
    const [statusFilter, setStatusFilter] = useState('')
    const [sourceFilter, setSourceFilter] = useState('')

    useEffect(() => {
        const params = new URLSearchParams({ page: String(page) })
        if (search) params.set('search', search)
        if (statusFilter) params.set('status', statusFilter)
        if (sourceFilter) params.set('source', sourceFilter)
        fetch(`/api/leads?${params}`)
            .then(r => r.json())
            .then(setLeads)
    }, [page, search, statusFilter, sourceFilter])

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-semibold text-gray-900">Leads</h1>
            </div>

            <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div className="p-4 border-b border-gray-200 flex flex-wrap gap-3">
                    <input
                        type="text"
                        placeholder="Search by name, phone, email..."
                        value={search}
                        onChange={e => { setSearch(e.target.value); setPage(1) }}
                        className="flex-1 min-w-[200px] px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                    <select value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(1) }}
                        className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Status</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="qualifying">Qualifying</option>
                        <option value="qualified">Qualified</option>
                        <option value="converted">Converted</option>
                        <option value="lost">Lost</option>
                    </select>
                    <select value={sourceFilter} onChange={e => { setSourceFilter(e.target.value); setPage(1) }}
                        className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Sources</option>
                        <option value="comment">Comment</option>
                        <option value="messenger">Messenger</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="email">Email</option>
                    </select>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Contact</th>
                                <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Source</th>
                                <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Score</th>
                                <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Campaign</th>
                                <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {leads?.data.map((lead: Lead) => (
                                <tr key={lead.id as Key} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        <Link href={`/leads/${lead.id}`} className="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                            {lead.name || 'Unnamed'}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="text-sm text-gray-700">{lead.phone || '-'}</div>
                                        <div className="text-xs text-gray-500">{lead.email || '-'}</div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className="text-sm text-gray-700 capitalize">{lead.source}</span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <div className="w-16 bg-gray-200 rounded-full h-2">
                                                <div className="bg-indigo-500 h-2 rounded-full" style={{ width: `${lead.score}%` }} />
                                            </div>
                                            <span className="text-sm font-medium">{lead.score}</span>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${statusColors[lead.status] || 'bg-gray-100 text-gray-800'}`}>
                                            {lead.status}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-700">
                                        {lead.campaign?.name || '-'}
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-500">
                                        {new Date(lead.created_at).toLocaleDateString('ar-SA')}
                                    </td>
                                </tr>
                            ))}
                            {(!leads || leads.data.length === 0) && (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12 text-center text-gray-500">No leads found</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {leads && leads.last_page > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t border-gray-200">
                        <div className="text-sm text-gray-500">
                            Showing {leads.from}–{leads.to} of {leads.total}
                        </div>
                        <div className="flex gap-2">
                            <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1}
                                className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50">
                                Previous
                            </button>
                            <button onClick={() => setPage(p => Math.min(leads.last_page, p + 1))} disabled={page === leads.last_page}
                                className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50">
                                Next
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    )
}
