import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import {defineConfig, loadEnv} from 'vite';

export default defineConfig(({mode}) => {
  const env = loadEnv(mode, '.', '');
  return {
    plugins: [
      react(), 
      tailwindcss(),
    ],
    define: {
      'process.env.GEMINI_API_KEY': JSON.stringify(env.GEMINI_API_KEY),
    },
    resolve: {
      alias: {
        '@': path.resolve(__dirname, '.'),
      },
    },
    build: {
      target: 'esnext',
      minify: 'esbuild',
      cssMinify: true,
      cssCodeSplit: true,
      sourcemap: false,
      chunkSizeWarningLimit: 1000,
      rollupOptions: {
        output: {
          manualChunks: {
            'vendor-react': ['react', 'react-dom', 'react-router-dom'],
            'vendor-query': ['@tanstack/react-query'],
            'vendor-icons': ['lucide-react'],
            'vendor-motion': ['motion'],
          },
        },
      },
    },
    server: {
      hmr: process.env.DISABLE_HMR !== 'true',
      proxy: {
        '/api_proxy.php': 'http://127.0.0.1:8000',
        '/generate_key.php': 'http://127.0.0.1:8000',
        '/tiktok_live.php': 'http://127.0.0.1:8000',
        '/tiktok_live_mobile.php': 'http://127.0.0.1:8000',
        '/suggestion_search.php': 'http://127.0.0.1:8000',
        '/suggestion.php': 'http://127.0.0.1:8000',
        '/video-meta.php': 'http://127.0.0.1:8000',
        '/seo-video.php': 'http://127.0.0.1:8000',
        '/seo-creator.php': 'http://127.0.0.1:8000',
        '/api': 'http://127.0.0.1:8000',
        '/segment_page': 'http://127.0.0.1:8000',
        '/uploads': 'http://127.0.0.1:8000',
        '/donnees': 'http://127.0.0.1:8000',
      },
    },
  };
});
