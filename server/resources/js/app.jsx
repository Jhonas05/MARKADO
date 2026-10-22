import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { createInertiaApp } from '@inertiajs/react'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import '@prototype/styles.css'
import '@prototype/design-system.css'
import '../css/foundation.css'

createInertiaApp({
  title: (title) => (title ? `${title} · MARKADO` : 'MARKADO'),
  resolve: (name) =>
    resolvePageComponent(`./pages/${name}.jsx`, import.meta.glob('./pages/**/*.jsx')),
  setup({ el, App, props }) {
    createRoot(el).render(
      <StrictMode>
        <App {...props} />
      </StrictMode>,
    )
  },
  progress: {
    color: '#2742c5',
  },
})
