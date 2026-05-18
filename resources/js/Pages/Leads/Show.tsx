import { useState, useEffect, useCallback } from 'react'
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

export default function LeadShow({ id }: { id: string }) {
    const [lead, setLead] = useState<Lead | null>(null)

    const fetchLead = useCallback(() => {
        fetch(`/api/leads/${id}`)
            .then(r => r.json())
            .then(setLead)
    }, [id])

    useEffect(() => { fetchLead() }, [fetchLead])

    if (!lead) {
        return <div className="text-center py-12 text-gray-500">Loading...</div>
    }

    return (
        <div>
            <div className="flex items-center gap-3 mb-6">
                <Link href="/leads" className="text-sm text-indigo-600 hover:text-indigo-800">&larr; Back to Leads</Link>
                <h1 className="text-2xl font-semibold text-gray-900">{lead.name || 'Unnamed Lead'}</h1>
                <span className={`inline-flex px-2.5 py-0.5 text-sm font-medium rounded-full ${statusColors[lead.status] || 'bg-gray-100 text-gray-800'}`}>
                    {lead.status}
                </span>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div className="lg:col-span-2 space-y-6">
                    <div className="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 className="font-semibold text-gray-900 mb-4">Lead Information</h2>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="text-xs text-gray-500 uppercase">Name</label>
                                <p className="text-sm font-medium">{lead.name || '-'}</p>
                            </div>
                            <div>
                                <label className="text-xs text-gray-500 uppercase">Phone</label>
                                <p className="text-sm font-medium">{lead.phone || '-'}</p>
                            </div>
                            <div>
                                <label className="text-xs text-gray-500 uppercase">Email</label>
                                <p className="text-sm font-medium">{lead.email || '-'}</p>
                            </div>
                            <div>
                                <label className="text-xs text-gray-500 uppercase">Source</label>
                                <p className="text-sm font-medium capitalize">{lead.source}</p>
                            </div>
                            <div>
                                <label className="text-xs text-gray-500 uppercase">Campaign</label>
                                <p className="text-sm font-medium">{lead.campaign?.name || '-'}</p>
                            </div>
                            <div>
                                <label className="text-xs text-gray-500 uppercase">PSID</label>
                                <p className="text-sm font-medium font-mono">{lead.psid || '-'}</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 className="font-semibold text-gray-900 mb-4">Conversations ({lead.conversations?.length || 0})</h2>
                        {lead.conversations && lead.conversations.length > 0 ? (
                            <ConversationTimeline conversations={lead.conversations} />
                        ) : (
                            <p className="text-sm text-gray-500">No conversations yet</p>
                        )}
                    </div>

                    {lead.agentActions && lead.agentActions.length > 0 && (
                        <div className="bg-white rounded-xl border border-gray-200 p-6">
                            <h2 className="font-semibold text-gray-900 mb-4">Agent Actions</h2>
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="text-right px-3 py-2 text-xs font-medium text-gray-500">Action</th>
                                            <th className="text-right px-3 py-2 text-xs font-medium text-gray-500">Model</th>
                                            <th className="text-right px-3 py-2 text-xs font-medium text-gray-500">Tokens</th>
                                            <th className="text-right px-3 py-2 text-xs font-medium text-gray-500">Time</th>
                                            <th className="text-right px-3 py-2 text-xs font-medium text-gray-500">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200">
                                        {lead.agentActions.map((action: AgentAction) => (
                                            <tr key={action.id as Key} className="text-sm">
                                                <td className="px-3 py-2.5 font-medium">{action.action_type}</td>
                                                <td className="px-3 py-2.5 text-gray-600">{action.model_used || '-'}</td>
                                                <td className="px-3 py-2.5 text-gray-600">{action.tokens_used ?? '-'}</td>
                                                <td className="px-3 py-2.5 text-gray-600">
                                                    {action.processing_time_ms != null ? `${action.processing_time_ms}ms` : '-'}
                                                </td>
                                                <td className="px-3 py-2.5 text-gray-500">
                                                    {new Date(action.created_at).toLocaleString('ar-SA')}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                </div>

                <div className="space-y-6">
                    <div className="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 className="font-semibold text-gray-900 mb-4">Lead Score</h2>
                        <div className="flex items-center gap-4">
                            <div className="relative w-20 h-20">
                                <svg className="w-20 h-20 -rotate-90" viewBox="0 0 36 36">
                                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e5e7eb" strokeWidth="3" />
                                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#6366f1" strokeWidth="3"
                                        strokeDasharray={`${lead.score}, 100`} strokeLinecap="round" />
                                </svg>
                                <span className="absolute inset-0 flex items-center justify-center text-xl font-bold text-gray-900">{lead.score}</span>
                            </div>
                            <div>
                                <div className="text-sm font-medium">{lead.score >= 70 ? '✅ Qualified' : '⏳ Not qualified'}</div>
                                <div className="text-xs text-gray-500 mt-1">{lead.score >= 70 ? 'Ready for handoff' : `${70 - lead.score} points to qualify`}</div>
                            </div>
                        </div>
                    </div>

                    {lead.fieldValues && lead.fieldValues.length > 0 && (
                        <div className="bg-white rounded-xl border border-gray-200 p-6">
                            <h2 className="font-semibold text-gray-900 mb-4">Custom Fields</h2>
                            <div className="space-y-2">
                                {lead.fieldValues.map((fv: LeadFieldValue) => (
                                    <div key={fv.id as Key} className="flex justify-between text-sm">
                                        <span className="text-gray-500">{fv.field_key}</span>
                                        <span className="font-medium">{fv.field_value || '-'}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    )
}
