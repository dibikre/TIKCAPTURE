import { useEffect } from 'react'
import { Outlet, useLocation } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { Header } from './components/Header.tsx'
import { ThemeProvider } from './components/ThemeProvider.tsx'
import { useAuthStore } from './stores/authStore.ts'
import { Footer } from './components/Footer.tsx'
import { useSeoMeta } from './hooks/Useseometa.ts'
import { AdsLoader } from './components/AdsLoader'
import { useScrollToTop } from './hooks/useScrollToTop'
import { StructuredData } from './components/StructuredData'

// ─── Query client ─────────────────────────────────────────────────────────────

const queryClient = new QueryClient({
  defaultOptions: {
    queries: { staleTime: 30000, retry: 1 },
  },
})

// ─── AppContent ───────────────────────────────────────────────────────────────

function AppContent() {
  const location = useLocation()

  const { initFromStorage } = useAuthStore()
  useEffect(() => {
    initFromStorage()
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  useSeoMeta({
    title: 'TikCapture – Enregistrez et téléchargez vos contenus TikTok',
    description:
      "TikCapture vous permet d'enregistrer des lives TikTok et de télécharger des vidéos TikTok gratuitement, sans filigrane.",
    canonical: `https://tikcapture.live${location.pathname}`,
  })

  useScrollToTop()

  return (
    <div className="min-h-screen bg-background text-foreground transition-colors duration-500">
      {/* Background effects */}
      <div className="fixed inset-0 overflow-hidden pointer-events-none">
        <div
          className="absolute top-0 left-1/4 w-96 h-96 bg-[#FF0050]/20 rounded-full blur-[128px] dark:opacity-100 opacity-30 animate-pulse transition-opacity duration-500"
        />
        <div
          className="absolute bottom-0 right-1/4 w-96 h-96 bg-[#00F2EA]/10 rounded-full blur-[128px] dark:opacity-100 opacity-20 animate-pulse transition-opacity duration-500"
          style={{ animationDelay: '1.5s' }}
        />
      </div>

      <Header />
      <StructuredData />

      {/* Toutes les pages s'affichent ici, y compris Home */}
      <Outlet />

      <AdsLoader />
      <Footer />
    </div>
  )
}

// ─── App ──────────────────────────────────────────────────────────────────────

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <ThemeProvider defaultTheme="system">
        <AppContent />
      </ThemeProvider>
    </QueryClientProvider>
  )
}

export default App