import { Link, usePage, router } from '@inertiajs/react'
import { useState } from 'react'

interface PageProps {
    auth?: {
        user?: {
            id: string
            name: string
            email: string
        } | null
    }
}

const nav = [
    { name: 'Dashboard', href: '/dashboard', icon: '📊' },
    { name: 'Leads', href: '/leads', icon: '👤' },
    { name: 'Campaigns', href: '/campaigns', icon: '📢' },
    { name: 'Agent', href: '/agent', icon: '🤖' },
    { name: 'Analytics', href: '/analytics', icon: '📈' },
    { name: 'Knowledge Base', href: '/knowledge-base', icon: '📚' },
    { name: 'Settings', href: '/settings', icon: '⚙️' },
]

export default function Layout({ children }: { children: React.ReactNode }) {
    const [sidebarOpen, setSidebarOpen] = useState(false)
    const { url, props } = usePage<PageProps>()
    const user = props.auth?.user

    return (
        <div className="flex h-screen bg-gray-50">
            <div className={`fixed inset-0 z-40 lg:hidden ${sidebarOpen ? '' : 'hidden'}`}>
                <div className="absolute inset-0 bg-black/30" onClick={() => setSidebarOpen(false)} />
            </div>

            <aside className={`
                fixed inset-y-0 right-0 z-50 w-64 bg-white border-l border-gray-200 shadow-sm
                transform transition-transform lg:relative lg:translate-x-0
                ${sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'}
            `}>
                <div className="flex items-center gap-2 px-6 h-16 border-b border-gray-200">
                    <span className="text-xl">🤖</span>
                    <span className="font-semibold text-gray-900">AI Sales Agent</span>
                </div>
                <nav className="p-4 space-y-1">
                    {nav.map(item => {
                        const active = url.startsWith(item.href)
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors ${
                                    active
                                        ? 'bg-indigo-50 text-indigo-700'
                                        : 'text-gray-700 hover:bg-gray-100'
                                }`}
                            >
                                <span>{item.icon}</span>
                                {item.name}
                            </Link>
                        )
                    })}
                </nav>
            </aside>

            <div className="flex-1 flex flex-col min-w-0">
                <header className="flex items-center gap-4 px-6 h-16 bg-white border-b border-gray-200 shadow-sm">
                    <button
                        onClick={() => setSidebarOpen(true)}
                        className="lg:hidden p-2 text-gray-500 hover:text-gray-700"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div className="flex-1" />
                    <div className="flex items-center gap-3">
                        {user && (
                            <div className="flex items-center gap-3">
                                <span className="text-sm text-gray-700">{user.name}</span>
                                <button
                                    onClick={() => router.post('/logout')}
                                    className="text-sm text-red-500 hover:text-red-700 transition-colors"
                                >
                                    تسجيل خروج
                                </button>
                            </div>
                        )}
                        <span className="text-sm text-gray-500">AI Sales Agent v1</span>
                    </div>
                </header>
                <main className="flex-1 overflow-auto p-6">
                    {children}
                </main>
            </div>
        </div>
    )
}
