import type { Key } from 'react'

const channelIcons: Record<string, string> = {
    messenger: '💬',
    whatsapp: '📱',
    comment: '📝',
    email: '📧',
}

export default function ConversationTimeline({ conversations }: { conversations: Conversation[] }) {
    return (
        <div className="space-y-4">
            {conversations.map((msg: Conversation) => (
                <div key={msg.id as Key} className={`flex ${msg.direction === 'inbound' ? 'justify-start' : 'justify-end'}`}>
                    <div className={`max-w-[80%] rounded-2xl px-4 py-3 ${
                        msg.direction === 'inbound'
                            ? 'bg-gray-100 text-gray-900 rounded-br-sm'
                            : 'bg-indigo-600 text-white rounded-bl-sm'
                    }`}>
                        <div className="flex items-center gap-1.5 mb-1">
                            <span className="text-xs">{channelIcons[msg.channel] || '💬'}</span>
                            <span className={`text-[10px] font-medium uppercase ${msg.direction === 'inbound' ? 'text-gray-500' : 'text-indigo-200'}`}>
                                {msg.direction === 'inbound' ? 'Inbound' : 'Outbound'}
                            </span>
                        </div>
                        <p className="text-sm whitespace-pre-wrap">{msg.message}</p>
                        <div className={`text-[10px] mt-1 ${msg.direction === 'inbound' ? 'text-gray-400' : 'text-indigo-200'}`}>
                            {new Date(msg.created_at).toLocaleString('ar-SA')}
                        </div>
                    </div>
                </div>
            ))}
        </div>
    )
}
