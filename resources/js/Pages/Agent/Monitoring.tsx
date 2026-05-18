import { useState, useEffect } from 'react'
import type { Key } from 'react'

export default function AgentMonitoring() {
    const [health, setHealth] = useState<{ available: ProviderHealth[]; report: any } | null>(null)
    const [chatMessage, setChatMessage] = useState('')
    const [chatResponse, setChatResponse] = useState<any>(null)
    const [chatLoading, setChatLoading] = useState(false)
    const [activeTab, setActiveTab] = useState<'health' | 'chat'>('health')

    useEffect(() => {
        fetch('/api/agent/health/full')
            .then(r => r.json())
            .then(setHealth)
    }, [])

    const sendChat = async () => {
        if (!chatMessage.trim()) return
        setChatLoading(true)
        try {
            const r = await fetch('/api/agent/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ message: chatMessage }),
            })
            setChatResponse(await r.json())
        } catch (e: any) {
            setChatResponse({ success: false, error: e.message })
        }
        setChatLoading(false)
    }

    const statusDot = (status: string) => {
        switch (status) {
            case 'online': return 'bg-green-500'
            case 'degraded': return 'bg-yellow-500'
            default: return 'bg-red-500'
        }
    }

    return (
        <div>
            <h1 className="text-2xl font-semibold text-gray-900 mb-6">Agent Monitoring</h1>

            <div className="flex gap-2 mb-6">
                {(['health', 'chat'] as const).map(tab => (
                    <button key={tab}
                        onClick={() => setActiveTab(tab)}
                        className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors ${
                            activeTab === tab ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                        }`}
                    >
                        {tab === 'health' ? 'Provider Health' : 'Chat Test'}
                    </button>
                ))}
            </div>

            {activeTab === 'health' && (
                <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div className="px-6 py-4 border-b border-gray-200">
                        <h2 className="font-semibold text-gray-900">AI Providers Status</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Provider</th>
                                    <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Model</th>
                                    <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Latency</th>
                                    <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Error</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {health?.available.map((p: ProviderHealth) => (
                                    <tr key={p.name} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 text-sm font-medium text-gray-900">{p.name}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <span className={`w-2 h-2 rounded-full ${statusDot(p.status)}`} />
                                                <span className="text-sm capitalize">{p.status}</span>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-600">{p.model || '-'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-600">
                                            {p.latency_ms != null ? `${p.latency_ms}ms` : '-'}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-red-600">{p.error || '-'}</td>
                                    </tr>
                                ))}
                                {(!health || health.available.length === 0) && (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-12 text-center text-gray-500">No providers available</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {activeTab === 'chat' && (
                <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <h2 className="font-semibold text-gray-900 mb-4">Test LLM Chat</h2>
                    <div className="flex gap-3">
                        <input
                            type="text"
                            value={chatMessage}
                            onChange={e => setChatMessage(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && sendChat()}
                            placeholder="Type a message to the AI..."
                            className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        <button onClick={sendChat} disabled={chatLoading || !chatMessage.trim()}
                            className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            {chatLoading ? 'Sending...' : 'Send'}
                        </button>
                    </div>
                    {chatResponse && (
                        <div className={`mt-4 p-4 rounded-lg text-sm ${chatResponse.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}`}>
                            {chatResponse.success ? (
                                <div>
                                    <p className="font-medium mb-1">Response:</p>
                                    <p className="whitespace-pre-wrap">{chatResponse.response}</p>
                                    <div className="mt-2 text-xs text-gray-500">
                                        Model: {chatResponse.model} | Provider: {chatResponse.provider} | Tokens: {chatResponse.tokens} | Time: {chatResponse.time_ms}ms
                                    </div>
                                </div>
                            ) : (
                                <p>Error: {chatResponse.error}</p>
                            )}
                        </div>
                    )}
                </div>
            )}
        </div>
    )
}
