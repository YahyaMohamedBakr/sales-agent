import { useState, useEffect } from 'react';

interface Setting {
    key: string;
    label: string;
    secret?: boolean;
}

interface SettingGroup {
    title: string;
    items: Setting[];
}

const groups: SettingGroup[] = [
    {
        title: 'OpenAI',
        items: [
            { key: 'OPENAI_API_KEY', label: 'API Key', secret: true },
            { key: 'OPENAI_BASE_URL', label: 'Base URL' },
        ],
    },
    {
        title: 'Anthropic',
        items: [
            { key: 'ANTHROPIC_API_KEY', label: 'API Key', secret: true },
        ],
    },
    {
        title: 'Google',
        items: [
            { key: 'GOOGLE_API_KEY', label: 'API Key', secret: true },
        ],
    },
    {
        title: 'Groq',
        items: [
            { key: 'GROQ_API_KEY', label: 'API Key', secret: true },
        ],
    },
    {
        title: 'Mistral',
        items: [
            { key: 'MISTRAL_API_KEY', label: 'API Key', secret: true },
        ],
    },
    {
        title: 'DeepSeek',
        items: [
            { key: 'DEEPSEEK_API_KEY', label: 'API Key', secret: true },
        ],
    },
    {
        title: 'Together AI',
        items: [
            { key: 'TOGETHER_API_KEY', label: 'API Key', secret: true },
        ],
    },
    {
        title: 'Cohere',
        items: [
            { key: 'COHERE_API_KEY', label: 'API Key', secret: true },
        ],
    },
    {
        title: 'Zen (OpenCode)',
        items: [
            { key: 'ZEN_API_KEY', label: 'API Key', secret: true },
        ],
    },
    {
        title: 'Ollama',
        items: [
            { key: 'OLLAMA_URL', label: 'Server URL' },
            { key: 'OLLAMA_API_KEY', label: 'API Key', secret: true },
        ],
    },
    {
        title: 'Meta (Facebook/Instagram)',
        items: [
            { key: 'META_APP_ID', label: 'App ID' },
            { key: 'META_APP_SECRET', label: 'App Secret', secret: true },
            { key: 'META_PAGE_ID', label: 'Page ID' },
            { key: 'META_PAGE_ACCESS_TOKEN', label: 'Page Access Token', secret: true },
            { key: 'META_WEBHOOK_VERIFY_TOKEN', label: 'Webhook Verify Token' },
        ],
    },
    {
        title: 'WhatsApp',
        items: [
            { key: 'WHATSAPP_PHONE_NUMBER_ID', label: 'Phone Number ID' },
            { key: 'WHATSAPP_ACCESS_TOKEN', label: 'Access Token', secret: true },
        ],
    },
    {
        title: 'Email (SendGrid)',
        items: [
            { key: 'MAIL_HOST', label: 'SMTP Host' },
            { key: 'MAIL_PORT', label: 'SMTP Port' },
            { key: 'MAIL_USERNAME', label: 'SMTP Username' },
            { key: 'MAIL_PASSWORD', label: 'SMTP Password', secret: true },
            { key: 'MAIL_FROM_ADDRESS', label: 'From Address' },
            { key: 'MAIL_FROM_NAME', label: 'From Name' },
        ],
    },
];

export default function SettingsIndex() {
    const [values, setValues] = useState<Record<string, string>>({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState<string | null>(null);
    const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    useEffect(() => {
        fetch('/api/settings')
            .then(res => res.json())
            .then(data => {
                const v: Record<string, string> = {};
                Object.values(data).forEach((group: any) =>
                    (group as any[]).forEach((s: any) => (v[s.key] = s.value))
                );
                setValues(v);
                setLoading(false);
            })
            .catch(() => setLoading(false));
    }, []);

    function save(key: string) {
        setSaving(key);
        setMessage(null);

        fetch('/api/settings', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ key, value: values[key] || '', group: key.split('_')[0].toLowerCase() }),
        })
            .then(res => res.json())
            .then(() => {
                setMessage({ type: 'success', text: '✓ تم الحفظ' });
                setSaving(null);
                setTimeout(() => setMessage(null), 2000);
            })
            .catch(() => {
                setMessage({ type: 'error', text: '✗ فشل الحفظ' });
                setSaving(null);
            });
    }

    if (loading) return <div className="text-center py-12 text-gray-500">جاري التحميل...</div>;

    return (
        <div dir="rtl" className="max-w-4xl mx-auto">
            <h1 className="text-2xl font-bold mb-6">الإعدادات</h1>

            <p className="text-sm text-gray-500 mb-6">
                الكريدنشيالز دا بتتحط في الداتابيز وبتتدمج تلقائياً مع إعدادات النظام. الأولوية للقيم المخزنة هنا عن ملف .env
            </p>

            {message && (
                <div className={`p-3 rounded-lg mb-4 text-sm ${
                    message.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'
                }`}>
                    {message.text}
                </div>
            )}

            <div className="space-y-6">
                {groups.map(group => (
                    <div key={group.title} className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div className="px-6 py-3 bg-gray-50 border-b border-gray-200 font-semibold text-gray-800">
                            {group.title}
                        </div>
                        <div className="p-6 space-y-4">
                            {group.items.map(setting => (
                                <div key={setting.key} className="flex items-center gap-4">
                                    <label className="w-40 text-sm font-medium text-gray-600 shrink-0">
                                        {setting.label}
                                    </label>
                                    <input
                                        type={setting.secret ? 'password' : 'text'}
                                        value={values[setting.key] ?? ''}
                                        onChange={e => setValues(prev => ({ ...prev, [setting.key]: e.target.value }))}
                                        placeholder={setting.secret ? '••••••••' : ''}
                                        className="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                    />
                                    <button
                                        onClick={() => save(setting.key)}
                                        disabled={saving === setting.key}
                                        className="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shrink-0"
                                    >
                                        {saving === setting.key ? '...' : 'حفظ'}
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
