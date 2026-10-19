import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(),tailwindcss()],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
  server: {
    host: true,
    // Permite acesso via preview/proxy (ex.: *.e2b.app) em ambiente de desenvolvimento
    allowedHosts: ['.e2b.app', 'localhost'],
  },
})
