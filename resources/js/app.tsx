import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'
import Layout from './Layout'

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true })
        let page = pages[`./Pages/${name}.tsx`]
        if (!page) {
            page = pages[`./Pages/${name}.tsx`]
        }
        page.default.layout = page.default.layout || ((page: any) => <Layout children={page} />)
        return page
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />)
    },
})
