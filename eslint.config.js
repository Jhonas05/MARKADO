import js from '@eslint/js'
import reactHooks from 'eslint-plugin-react-hooks'
import reactRefresh from 'eslint-plugin-react-refresh'

export default [
  { ignores: ['dist', 'coverage', 'node_modules'] },
  {
    ...js.configs.recommended,
    files: ['**/*.{js,jsx}'],
  },
  {
    ...reactHooks.configs.flat.recommended,
    files: ['**/*.{js,jsx}'],
  },
  {
    ...reactRefresh.configs.vite,
    files: ['**/*.{js,jsx}'],
  },
  {
    files: ['**/*.{js,jsx}'],
    languageOptions: {
      ecmaVersion: 2024,
      globals: {
        document: 'readonly',
        window: 'readonly',
      },
      parserOptions: {
        ecmaFeatures: { jsx: true },
        sourceType: 'module',
      },
    },
  },
]

