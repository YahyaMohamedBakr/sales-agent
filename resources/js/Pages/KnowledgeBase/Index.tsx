import { useState, useEffect } from 'react'
import type { Key } from 'react'

export default function KnowledgeBaseIndex() {
    const [docs, setDocs] = useState<PaginatedData<KnowledgeBase> | null>(null)
    const [categories, setCategories] = useState<string[]>([])
    const [page, setPage] = useState(1)
    const [search, setSearch] = useState('')
    const [categoryFilter, setCategoryFilter] = useState('')
    const [showForm, setShowForm] = useState(false)
    const [form, setForm] = useState({ category: '', title: '', content: '' })

    useEffect(() => {
        const params = new URLSearchParams({ page: String(page) })
        if (search) params.set('search', search)
        if (categoryFilter) params.set('category', categoryFilter)
        fetch(`/api/knowledge-base?${params}`).then(r => r.json()).then(setDocs)
    }, [page, search, categoryFilter])

    useEffect(() => {
        fetch('/api/knowledge-categories').then(r => r.json()).then(setCategories)
    }, [])

    const submitForm = async () => {
        if (!form.category || !form.title || !form.content) return
        await fetch('/api/knowledge-base', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(form),
        })
        setForm({ category: '', title: '', content: '' })
        setShowForm(false)
        fetch(`/api/knowledge-base?page=${page}`).then(r => r.json()).then(setDocs)
        fetch('/api/knowledge-categories').then(r => r.json()).then(setCategories)
    }

    const deleteDoc = async (id: string) => {
        if (!confirm('Delete this document?')) return
        await fetch(`/api/knowledge-base/${id}`, { method: 'DELETE' })
        fetch(`/api/knowledge-base?page=${page}`).then(r => r.json()).then(setDocs)
    }

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-semibold text-gray-900">Knowledge Base</h1>
                <button onClick={() => setShowForm(!showForm)}
                    className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                    {showForm ? 'Cancel' : '+ Add Document'}
                </button>
            </div>

            {showForm && (
                <div className="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                    <h2 className="font-semibold text-gray-900 mb-4">New Document</h2>
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <input type="text" value={form.category} onChange={e => setForm(f => ({ ...f, category: e.target.value }))}
                                placeholder="e.g. product, pricing, shipping"
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Title</label>
                            <input type="text" value={form.title} onChange={e => setForm(f => ({ ...f, title: e.target.value }))}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Content</label>
                            <textarea rows={4} value={form.content} onChange={e => setForm(f => ({ ...f, content: e.target.value }))}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <button onClick={submitForm}
                            className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                            Save
                        </button>
                    </div>
                </div>
            )}

            <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div className="p-4 border-b border-gray-200 flex flex-wrap gap-3">
                    <input type="text" placeholder="Search documents..." value={search}
                        onChange={e => { setSearch(e.target.value); setPage(1) }}
                        className="flex-1 min-w-[200px] px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    <select value={categoryFilter} onChange={e => { setCategoryFilter(e.target.value); setPage(1) }}
                        className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Categories</option>
                        {categories.map(c => <option key={c} value={c}>{c}</option>)}
                    </select>
                </div>

                <div className="divide-y divide-gray-200">
                    {docs?.data.map((doc: KnowledgeBase) => (
                        <div key={doc.id as Key} className="p-4 hover:bg-gray-50">
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <div className="flex items-center gap-2 mb-1">
                                        <span className="text-xs font-medium bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">
                                            {doc.category}
                                        </span>
                                        <h3 className="font-medium text-gray-900">{doc.title}</h3>
                                    </div>
                                    <p className="text-sm text-gray-600 line-clamp-2">{doc.content}</p>
                                </div>
                                <button onClick={() => deleteDoc(doc.id)}
                                    className="text-red-500 hover:text-red-700 text-sm ml-4">
                                    Delete
                                </button>
                            </div>
                        </div>
                    ))}
                    {(!docs || docs.data.length === 0) && (
                        <div className="px-4 py-12 text-center text-gray-500">No documents found</div>
                    )}
                </div>

                {docs && docs.last_page > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t border-gray-200">
                        <div className="text-sm text-gray-500">Showing {docs.from}–{docs.to} of {docs.total}</div>
                        <div className="flex gap-2">
                            <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1}
                                className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button onClick={() => setPage(p => Math.min(docs.last_page, p + 1))} disabled={page === docs.last_page}
                                className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    )
}
