import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const directory = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@inertiajs/react': path.resolve(directory, 'src/test/InertiaReactStub.js'),
      '@prototype': path.resolve(directory, 'src'),
      react: path.resolve(directory, 'node_modules/react'),
      'react-dom': path.resolve(directory, 'node_modules/react-dom'),
    },
  },
  test: {
    environment: 'jsdom',
    setupFiles: './src/test/setup.js',
    css: true,
    pool: 'threads',
    maxWorkers: 1,
  },
})
