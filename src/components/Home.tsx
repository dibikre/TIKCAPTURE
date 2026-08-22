import { useCallback, useRef, useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { SearchBar } from './SearchBar'
import { TikTokSuggestions } from './TikTokSuggestions'
import { LiveDetails } from './LiveDetails'
import { ErrorDisplay } from './ErrorDisplay'
import { WeeklyHighlights } from './WeeklyHighlights'
import { RecentRecordingsGrid } from './RecentRecordingsGrid'
import { useLiveStore } from '../stores/liveStore'
import { useUniversalSearch, detectPlatform } from '../hooks/useUniversalSearch'
import { extractUsername } from '../lib/utils'
import { useSeoMeta } from '../hooks/Useseometa'
import { Monitor, Sparkles, Shield, Zap, Chrome, ExternalLink } from 'lucide-react'
import { homeContent } from '../content/home-content'
import { BASE_URL } from '../lib/constants'

export function Home() {
  const navigate = useNavigate()
  const { searchQuery, setSearchQuery, searchResult, setSearchResult } = useLiveStore()
  const [notFoundUsername, setNotFoundUsername] = useState<string | null>(null)
  const [initialSearchValue, setInitialSearchValue] = useState('')
  const searchMutation = useUniversalSearch()

  const [refreshRecordings] = useState(0)

  const handleSearch = useCallback(async (input: string) => {
    if (window.location.pathname !== '/') {
      navigate('/')
    }
    setSearchQuery(input)
    setSearchResult(null)
    setNotFoundUsername(null)
    const platform = detectPlatform(input)
    try {
      const result = await searchMutation.mutateAsync(input)
      const isNotFound =
        ('notFound' in result && (result as { notFound?: boolean }).notFound) ||
        !result.user?.uniqueId
      if (isNotFound) {
        if (platform === 'tiktok') {
          const extracted = extractUsername(input)
          setNotFoundUsername(
            typeof extracted === 'string' ? extracted : extracted.username ?? input
          )
        } else {
          setNotFoundUsername(input)
        }
        return
      }
      setSearchResult(result)
    } catch (error) {
      console.error('Search failed:', error)
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchMutation, navigate, setSearchQuery, setSearchResult])

  const autoSearchDone = useRef(false)
  const [shouldAutoSearch, setShouldAutoSearch] = useState(false)
  const [autoSearchUsername, setAutoSearchUsername] = useState('')
  
  useEffect(() => {
    if (autoSearchDone.current) return
    const params = new URLSearchParams(window.location.search)
    const u = params.get('u')
    if (!u) return
    autoSearchDone.current = true
    const trimmed = u.trim()
    const matchUrl = trimmed.match(/tiktok\.com\/@([^/?]+)/)
    const username = matchUrl
      ? matchUrl[1]
      : trimmed.startsWith('@')
      ? trimmed.slice(1)
      : trimmed
    setInitialSearchValue(username)
    navigate('/', { replace: true })
    // ← MODIFICATION ICI : on stocke pour plus tard au lieu de lancer tout de suite
    setAutoSearchUsername(username)
    setShouldAutoSearch(true)
  }, [navigate])

  // ← NOUVEAU useEffect : lance la recherche APRÈS que SearchBar ait reçu initialValue
  useEffect(() => {
    if (!shouldAutoSearch || !autoSearchUsername) return
    if (initialSearchValue !== autoSearchUsername) return // Attendre que SearchBar ait reçu la valeur
    
    const timer = setTimeout(() => {
      handleSearch(autoSearchUsername)
      setShouldAutoSearch(false)
    }, 100)
    
    return () => clearTimeout(timer)
  }, [shouldAutoSearch, autoSearchUsername, initialSearchValue, handleSearch])

  const handleRetry = () => {
    if (searchQuery) handleSearch(searchQuery)
  }

  useSeoMeta({
    title: 'Enregistrer un Live TikTok gratuitement – TikCapture',
    description:
      'Capturez et enregistrez les lives TikTok en haute qualité, sans installation ni watermark. 100% gratuit, directement depuis votre navigateur.',
    canonical: `${BASE_URL}/`,
    ogImage: `${BASE_URL}/og-image.png`,
  })

  const featureIcons: Record<string, React.ReactNode> = {
    monitor:  <Monitor  className="w-5 h-5 text-[#FF0050]" />,
    sparkles: <Sparkles className="w-5 h-5 text-[#00F2EA]" />,
    shield:   <Shield   className="w-5 h-5 text-[#FF0050]" />,
    zap:      <Zap      className="w-5 h-5 text-[#00F2EA]" />,
  }

  return (
    <main className="relative z-10 pt-20 md:pt-32 pb-12 md:pb-20">
      <div className="container mx-auto px-6 sm:px-10 lg:px-12 max-w-full overflow-hidden">

        {!searchResult && !searchMutation.isError && !notFoundUsername && (
          <div className="text-center mb-8 md:mb-16 max-w-4xl mx-auto animate-fade-in px-1">
            <h1 className="text-3xl sm:text-5xl md:text-7xl font-display font-black mb-4 md:mb-6 tracking-tight uppercase">
              Enregistrez les{' '}
              <span className="gradient-text">Lives TikTok</span>
            </h1>
            <p className="text-base md:text-xl lg:text-2xl text-muted max-w-2xl mx-auto leading-relaxed px-2">
              Capturez vos moments preferes en haute qualite.
              Sans installation, sans watermark, directement dans votre navigateur.
            </p>
          </div>
        )}

        <SearchBar
          onSearch={handleSearch}
          isLoading={searchMutation.isPending}
          initialValue={initialSearchValue}
        />

        <TikTokSuggestions onSearch={handleSearch} />

        {!searchResult && !searchMutation.isError && !notFoundUsername && (
          <RecentRecordingsGrid onSelectUser={handleSearch} refreshTrigger={refreshRecordings} />
        )}

        {searchMutation.isError && (
          <ErrorDisplay error={searchMutation.error as Error} onRetry={handleRetry} />
        )}

        {notFoundUsername && !searchMutation.isError && (
          <LiveDetails onSearch={handleSearch} notFoundUsername={notFoundUsername} />
        )}

        {searchResult && !searchMutation.isError && !notFoundUsername && (
          <LiveDetails onSearch={handleSearch} />
        )}

        <div className="max-w-4xl mx-auto mt-32 space-y-16">

          <WeeklyHighlights />

          {/* Section Extension */}
          <div className="rounded-2xl bg-linear-to-r from-[#FF0050]/10 to-[#00F2EA]/10 border border-white/10 p-6 md:p-8 flex flex-col md:flex-row items-center gap-6 animate-fade-in">
            <div className="w-16 h-16 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center shrink-0 shadow-xl">
              <Chrome className="w-8 h-8 text-[#00F2EA]" />
            </div>
            <div className="flex-1 text-center md:text-left space-y-2">
              <h2 className="text-xl md:text-2xl font-bold text-foreground">
                Utilisez notre <span className="text-[#00F2EA]">Extension Chrome</span>
              </h2>
              <p className="text-sm md:text-base text-muted leading-relaxed">
                Pour une expérience optimale, nous vous recommandons d'utiliser notre extension officielle. 
                Enregistrez vos lives TikTok favoris en un clic, directement depuis Chrome.
              </p>
            </div>
            <a
              href="https://chromewebstore.google.com/detail/enregistreur-de-live-tikt/enelemgkfjmlpgcflabmeaeenodglfna?pli=1"
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-black font-bold text-sm hover:bg-white/90 transition-all hover:scale-105 active:scale-95 group"
            >
              Ajouter à Chrome
              <ExternalLink className="w-4 h-4 transition-transform group-hover:translate-x-1" />
            </a>
          </div>

          {/* Comment ça marche */}
          <div className="space-y-6 animate-fade-in">
            <h2 className="text-2xl font-bold text-foreground text-center">
              Comment enregistrer un live TikTok ?
            </h2>
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              {homeContent.howItWorks.map((item) => (
                <div key={item.step} className="rounded-xl glass border border-white/10">
                  <img
                    src={item.image}
                    alt={item.title}
                    loading="lazy"
                    width={676}
                    height={450}
                    referrerPolicy="no-referrer"
                    className="w-full h-40 object-cover m-3 rounded-lg"
                  />
                  <div className="p-5 space-y-2">
                    <span className="text-3xl font-black text-[#FF0050]/60">0{item.step}</span>
                    <h3 className="font-bold text-foreground">{item.title}</h3>
                    <p className="text-sm text-foreground leading-relaxed">{item.description}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Pourquoi TikCapture */}
          <div className="space-y-6 animate-fade-in">
            <h2 className="text-2xl font-bold text-foreground text-center">
              Pourquoi choisir TikCapture ?
            </h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {homeContent.features.map((item) => (
                <div
                  key={item.title}
                  className="rounded-xl glass border border-white/10 p-5 flex gap-4 items-start"
                >
                  <div className="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center shrink-0">
                    {featureIcons[item.icon]}
                  </div>
                  <div className="space-y-1">
                    <h3 className="font-bold text-foreground">{item.title}</h3>
                    <p className="text-sm text-foreground leading-relaxed">{item.description}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Texte SEO */}
          <div className="rounded-2xl glass border border-white/10 p-6 space-y-4 animate-fade-in">
            <h2 className="text-xl font-bold text-foreground">
              Enregistreur de lives TikTok gratuit
            </h2>
            <p className="text-sm text-foreground leading-relaxed">{homeContent.seoText.intro}</p>
            {homeContent.seoText.body.split('\n\n').map((p, i) => (
              <p key={i} className="text-sm text-foreground leading-relaxed">{p}</p>
            ))}
            <p className="text-sm text-foreground leading-relaxed italic border-l-2 border-[#FF0050]/40 pl-4">
              {homeContent.seoText.conclusion}
            </p>
          </div>

          {/* FAQ */}
          <div className="space-y-3 animate-fade-in">
            <h2 className="text-2xl font-bold text-foreground text-center mb-6">
              Questions frequentes
            </h2>
            {homeContent.faq.map((item) => (
              <details
                key={item.question}
                className="rounded-xl glass border border-white/10 px-5 py-4 group cursor-pointer"
              >
                <summary className="font-semibold text-foreground text-sm list-none flex items-center justify-between">
                  {item.question}
                  <span className="text-[#FF0050] group-open:rotate-45 transition-transform duration-200 text-xl leading-none shrink-0 ml-3">
                    +
                  </span>
                </summary>
                <p className="mt-3 text-sm text-foreground leading-relaxed">{item.answer}</p>
              </details>
            ))}
          </div>

        </div>
      </div>
    </main>
  )
}