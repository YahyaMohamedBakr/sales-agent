declare module '*.tsx' {
    const component: any
    export default component
}

interface Lead {
    id: string
    psid?: string
    name?: string
    phone?: string
    email?: string
    source: string
    campaign_id?: string
    campaign?: Campaign
    score: number
    status: string
    metadata?: Record<string, any>
    created_at: string
    updated_at: string
    conversations?: Conversation[]
    fieldValues?: LeadFieldValue[]
    agentActions?: AgentAction[]
}

interface Campaign {
    id: string
    name: string
    meta_ad_id?: string
    status: string
    platform?: string
    page_id?: string
    metadata?: Record<string, any>
    leads_count?: number
    leads?: Lead[]
    created_at: string
}

interface Conversation {
    id: string
    lead_id: string
    channel: string
    message: string
    direction: 'inbound' | 'outbound'
    metadata?: Record<string, any>
    created_at: string
}

interface LeadFieldValue {
    id: string
    lead_id: string
    field_key: string
    field_value: string
}

interface AgentAction {
    id: string
    lead_id: string
    action_type: string
    prompt?: string
    response?: string
    model_used?: string
    tokens_used?: number
    processing_time_ms?: number
    agent_type?: string
    created_at: string
}

interface KnowledgeBase {
    id: string
    category: string
    title: string
    content: string
    active: boolean
    created_at: string
}

interface ProviderHealth {
    name: string
    status: 'online' | 'offline' | 'degraded'
    latency_ms?: number
    model: string
    error?: string
}

interface OverviewStats {
    total_leads: number
    qualified: number
    converted: number
    active: number
    total_conversations: number
    total_campaigns: number
    qualification_rate: number
    conversion_rate: number
}

interface PaginatedData<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
}
