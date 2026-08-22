import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
  ],
  base: '/',
  build: {
    target: 'es2019',
    rollupOptions: {
      output: {
        manualChunks: {
        vendor: ['react', 'react-dom'],
        router: ['react-router-dom'],
        query: ['@tanstack/react-query'],
        ui: ['lucide-react', 'clsx', 'tailwind-merge'],
        themes: ['next-themes'],
        store: ['zustand'],
      },
      }
    },
    chunkSizeWarningLimit: 500,
  },
})