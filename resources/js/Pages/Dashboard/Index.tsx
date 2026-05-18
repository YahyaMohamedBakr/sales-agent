import { useState, useEffect } from 'react'
import { Link } from '@inertiajs/react'

export default function Dashboard() {
    const [stats, setStats] = useState<OverviewStats | null>(null)

    useEffect(() => {
        fetch('/api/analytics/overview')
            .then(r => r.json())
            .then(setStats)
    }, [])

    if (!stats) {
        return <div className="text-center py-12 text-gray-500">Loading...</div>
    }

    const cards = [
        { label: 'Total Leads', value: stats.total_leads, color: 'bg-blue-50 text-blue-700' },
        { label: 'Active', value: stats.active, color: 'bg-green-50 text-green-700' },
        { label: 'Qualified', value: stats.qualified, color: 'bg-purple-50 text-purple-700' },
        { label: 'Converted', value: stats.converted, color: 'bg-amber-50 text-amber-700' },
        { label: 'Conversion Rate', value: `${stats.conversion_rate}%`, color: 'bg-indigo-50 text-indigo-700' },
        { label: 'Qualification Rate', value: `${stats.qualification_rate}%`, color: 'bg-teal-50 text-teal-700' },
        { label: 'Conversations', value: stats.total_conversations, color: 'bg-pink-50 text-pink-700' },
        { label: 'Campaigns', value: stats.total_campaigns, color: 'bg-orange-50 text-orange-700' },
    ]

    return (
        <div>
            <h1 className="text-2xl font-semibold text-gray-900 mb-6">Dashboard</h1>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                {cards.map(card => (
                    <div key={card.label} className={`rounded-xl p-5 ${card.color}`}>
                        <div className="text-sm font-medium opacity-75">{card.label}</div>
                        <div className="text-3xl font-bold mt-1">{card.value}</div>
                    </div>
                ))}
            </div>
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Link href="/leads" className="block p-6 bg-white rounded-xl border border-gray-200 hover:shadow-md transition-shadow">
                    <h2 className="font-semibold text-gray-900">Manage Leads</h2>
                    <p className="text-sm text-gray-500 mt-1">View, filter, and manage your leads</p>
                </Link>
                <Link href="/analytics" className="block p-6 bg-white rounded-xl border border-gray-200 hover:shadow-md transition-shadow">
                    <h2 className="font-semibold text-gray-900">View Analytics</h2>
                    <p className="text-sm text-gray-500 mt-1">Charts, trends, and performance metrics</p>
                </Link>
                <Link href="/campaigns" className="block p-6 bg-white rounded-xl border border-gray-200 hover:shadow-md transition-shadow">
                    <h2 className="font-semibold text-gray-900">Campaigns</h2>
                    <p className="text-sm text-gray-500 mt-1">Track ad campaigns and their performance</p>
                </Link>
                <Link href="/agent" className="block p-6 bg-white rounded-xl border border-gray-200 hover:shadow-md transition-shadow">
                    <h2 className="font-semibold text-gray-900">Agent Monitoring</h2>
                    <p className="text-sm text-gray-500 mt-1">AI provider health and performance</p>
                </Link>
            </div>
        </div>
    )
}
