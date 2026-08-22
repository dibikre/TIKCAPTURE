import { useState } from 'react'
import {
  Link2,
  Download,
  AlertCircle,
  Loader2,
  ArrowRight,
  Users,
  HardDrive,
} from 'lucide-react'
import { cn } from '../lib/utils'
import { useSeoMeta } from '../hooks/Useseometa'
import { tiktokVideoContent } from '../content/tiktok-video-content'
import { BASE_URL } from '../lib/constants'

// Imports from sub-folder
import { ApiResponse } from './Tiktokvideo/types'
import { normalizeTikTokUrl } from './Tiktokvideo/utils'
import { ResultPanel } from './Tiktokvideo/ResultPanel'

const MAINTENANCE_MODE = false
const MAINTENANCE_MESSAGE = 'Le telechargement de videos est temporairement indisponible pour maintenance. Veuillez reessayer dans quelques minutes.'

const API_URL = `${BASE_URL}/segment_page/api/tiktok-video.php`

export function TikTokVideo() {
  const [inputUrl, setInputUrl] = useState('')
  const [loading, setLoading]   = useState(false)
  const [result, setResult]     = useState<ApiResponse | null>(null)
  const [error, setError]       = useState<string | null>(null)

  useSeoMeta({
    title:         'Telecharger une video TikTok sans filigrane - TikCapture',
    description:   "Telechargez n'importe quelle video TikTok en MP4 HD, sans filigrane et sans inscription. Collez simplement l'URL TikTok pour obtenir votre video en quelques secondes.",
    canonical:     `${BASE_URL}/tiktok-video`,
    ogTitle:       'Telecharger une video TikTok sans filigrane - Gratuit & HD',
    ogDescription: 'Outil gratuit pour telecharger des videos TikTok en haute qualite, sans filigrane. Rapide, simple, aucune inscription requise.',
    ogImage:       `${BASE_URL}/og-image.png`,
  })

  const handleSearch = async (rawInput?: string) => {
    if (MAINTENANCE_MODE) {
      setError('🔧 ' + MAINTENANCE_MESSAGE)
      return
    }

    const raw = (rawInput ?? inputUrl).trim()
    if (!raw) return

    const cleanUrl = normalizeTikTokUrl(raw)
    if (!cleanUrl) {
      setError("URL invalide. Collez une URL TikTok complete de type : https://www.tiktok.com/@username/video/123...")
      return
    }

    setLoading(true)
    setResult(null)
    setError(null)
    if (rawInput) setInputUrl(rawInput)

    try {
      const res = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: cleanUrl }),
      })

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`)
      }

      const data: ApiResponse = await res.json()

      if (data.success) {
        setResult(data)
      } else if (data.maintenance) {
        setError('🔧 ' + (data.error ?? 'Service en maintenance.'))
      } else {
        setError(data.error ?? 'Une erreur est survenue.')
      }
    } catch (err) {
      const msg = err instanceof Error ? err.message : ''
      if (msg.startsWith('HTTP')) {
        setError(`Erreur serveur (${msg}). Veuillez reessayer.`)
      } else {
        setError("Impossible de contacter le serveur. Verifiez votre connexion ou reessayez.")
      }
    } finally {
      setLoading(false)
    }
  }

  const handleReset = () => {
    setResult(null)
    setError(null)
    setInputUrl('')
  }

  const previewUrl = inputUrl ? normalizeTikTokUrl(inputUrl) : null
  const isUrlValid  = previewUrl !== null
  const showPreview = inputUrl.length > 10 && !isUrlValid

  const EXAMPLES = [
    'https://www.tiktok.com/@zachking/video/7034470001303117061',
    'https://www.tiktok.com/@khaby.lame/video/7093769938460927238',
  ]

  if (MAINTENANCE_MODE) {
    return (
      <main className="relative z-10 pt-24 md:pt-32 pb-20">
        <div className="mx-auto px-4 sm:px-6 w-full max-w-5xl">
          <div className="flex flex-col items-center justify-center py-24 text-center animate-fade-in">
            <div className="w-16 h-16 rounded-2xl bg-yellow-500/15 border border-yellow-500/30 flex items-center justify-center mb-6">
              <span className="text-3xl">🔧</span>
            </div>
            <h2 className="text-2xl font-bold text-foreground mb-3">Maintenance en cours</h2>
            <p className="text-muted-foreground max-w-md leading-relaxed">{MAINTENANCE_MESSAGE}</p>
          </div>
        </div>
      </main>
    )
  }

  return (
    <main className="relative z-10 pt-24 md:pt-32 pb-20">
      <div className="mx-auto px-4 sm:px-6 w-full max-w-5xl">

        {/* Hero */}
        {!result && (
          <div className="text-center mb-10 animate-fade-in">
            <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/10 mb-6">
              <Download className="w-4 h-4 text-[#FF0050]" />
              <span className="text-sm text-muted-foreground">Sans filigrane · MP4 · Gratuit</span>
            </div>
            <h1 className="text-4xl sm:text-5xl font-black mb-4 tracking-tight leading-tight">
              Telecharger une{' '}
              <span className="gradient-text">video TikTok</span>
            </h1>
            <p className="text-base md:text-lg text-foreground/90 max-w-xl mx-auto leading-relaxed">
              Collez l'URL d'une video TikTok et telechargez-la instantanement en haute qualite, sans filigrane.
            </p>
          </div>
        )}

        {/* Search bar */}
        <div className="rounded-2xl glass border border-white/10 p-4 mb-6 animate-fade-in">
          <div className="flex gap-3">
            <div className={cn(
              'flex-1 flex items-center gap-3 rounded-xl bg-white/5 border px-4 py-3 transition-colors',
              inputUrl && !isUrlValid ? 'border-red-500/40' : 'border-white/10 focus-within:border-[#FF0050]/50'
            )}>
              <Link2 className="w-4 h-4 text-muted-foreground shrink-0" />
              <input
                type="text"
                value={inputUrl}
                onChange={(e) => setInputUrl(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                placeholder="https://www.tiktok.com/@username/video/..."
                className="flex-1 bg-transparent text-sm text-foreground placeholder:text-muted-foreground outline-none min-w-0"
                autoComplete="off"
              />
              {inputUrl && (
                <button onClick={() => { setInputUrl(''); setError(null) }} className="text-muted-foreground hover:text-foreground transition-colors text-lg leading-none shrink-0">
                  x
                </button>
              )}
            </div>

            <button
              onClick={() => handleSearch()}
              disabled={loading || !inputUrl.trim()}
              className="shrink-0 flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-[#FF0050] hover:bg-[#e0004a] disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-sm transition-all duration-200 hover:scale-105 active:scale-95 min-w-[44px] sm:min-w-0"
            >
              {loading
                ? <Loader2 className="w-4 h-4 animate-spin" />
                : <>
                    <ArrowRight className="w-4 h-4 sm:hidden" />
                    <span className="hidden sm:inline">Telecharger</span>
                    <ArrowRight className="w-4 h-4 hidden sm:inline" />
                  </>
              }
            </button>
          </div>

          {/* URL invalide hint */}
          {showPreview && (
            <p className="mt-2 text-xs text-red-400 flex items-center gap-1.5">
              <AlertCircle className="w-3.5 h-3.5 shrink-0" />
              Format non reconnu. Collez l'URL complete depuis TikTok (ex: https://www.tiktok.com/@user/video/...)
            </p>
          )}

          {/* URL normalisee confirmee */}
          {isUrlValid && inputUrl !== previewUrl && (
            <p className="mt-2 text-xs text-[#00F2EA]/70 flex items-center gap-1.5">
              URL detectee : <span className="truncate">{previewUrl}</span>
            </p>
          )}

          {/* Exemples */}
          {!result && !loading && (
            <div className="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
              <span>Exemples :</span>
              {EXAMPLES.map((ex, i) => (
                <button key={ex} onClick={() => handleSearch(ex)} className="text-[#FF0050] hover:underline">
                  Video #{i + 1}
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Loading */}
        {loading && (
          <div className="flex flex-col items-center gap-4 py-16 animate-fade-in">
            <div className="w-14 h-14 rounded-2xl bg-[#FF0050]/15 border border-[#FF0050]/30 flex items-center justify-center">
              <Loader2 className="w-7 h-7 text-[#FF0050] animate-spin" />
            </div>
            <div className="text-center">
              <p className="font-semibold text-foreground">Recuperation de la video...</p>
              <p className="text-sm text-muted-foreground mt-1">Cela peut prendre quelques secondes</p>
            </div>
          </div>
        )}

        {/* Error */}
        {error && !loading && (
          <div className="flex items-start gap-3 px-5 py-4 rounded-xl border border-red-500/30 bg-red-500/10 animate-fade-in-up">
            <AlertCircle className="w-5 h-5 text-red-400 shrink-0 mt-0.5" />
            <div>
              <p className="font-semibold text-foreground text-sm">Erreur</p>
              <p className="text-sm text-muted-foreground mt-0.5">{error}</p>
            </div>
          </div>
        )}

        {/* Result */}
        {result && !loading && (
          <ResultPanel data={result} onReset={handleReset} onSearch={(url) => handleSearch(url)} />
        )}

        {/* Feature pills */}
        {!result && !loading && (
          <div className="mt-10 grid grid-cols-3 gap-3 animate-fade-in">
            {[
              { icon: <Download className="w-4 h-4 text-[#FF0050]" />, label: 'Sans filigrane' },
              { icon: <Users className="w-4 h-4 text-[#00F2EA]" />, label: 'Sans inscription' },
              { icon: <HardDrive className="w-4 h-4 text-[#FF0050]" />, label: 'Haute qualite' },
            ].map((f) => (
              <div
                key={f.label}
                className="flex items-center gap-3 py-4 px-4 rounded-xl glass border border-white/10"
              >
                {f.icon}
                <span className="text-sm text-foreground/90">
                  {f.label}
                </span>
              </div>
            ))}
          </div>
        )}

        {/* Comment ca marche */}
        <div className="mt-16 space-y-6 animate-fade-in">
          <h2 className="text-2xl font-bold text-foreground text-center">
            Comment telecharger une video TikTok ?
          </h2>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {tiktokVideoContent.howItWorks.map((item) => (
              <div key={item.step} className="rounded-xl glass border border-white/10 p-5 space-y-2">
                <span className="text-3xl font-black text-[#FF0050]/30">0{item.step}</span>
                <h3 className="font-bold text-foreground">{item.title}</h3>
                <p className="text-sm text-foreground leading-relaxed">{item.description}</p>
              </div>
            ))}
          </div>
        </div>

        {/* Texte SEO */}
        <div className="mt-12 rounded-2xl glass border border-white/10 p-6 space-y-4 animate-fade-in">
          <h2 className="text-xl font-bold text-foreground">
            Telechargeur de videos TikTok gratuit & sans filigrane
          </h2>
          <p className="text-sm text-foreground leading-relaxed">{tiktokVideoContent.seoText.intro}</p>
          {tiktokVideoContent.seoText.body.split('\n\n').map((p, i) => (
            <p key={i} className="text-sm text-foreground leading-relaxed">{p}</p>
          ))}
          <p className="text-sm text-foreground leading-relaxed italic border-l-2 border-[#FF0050]/40 pl-4">
            {tiktokVideoContent.seoText.conclusion}
          </p>
        </div>

        {/* FAQ */}
        <div className="mt-12 space-y-3 animate-fade-in">
          <h2 className="text-2xl font-bold text-foreground text-center mb-6">
            Questions frequentes
          </h2>
          {tiktokVideoContent.faq.map((item) => (
            <details
              key={item.question}
              className="rounded-xl glass border border-white/10 px-5 py-4 group cursor-pointer"
            >
              <summary className="font-semibold text-foreground text-sm list-none flex items-center justify-between">
                {item.question}
                <span className="text-[#FF0050] group-open:rotate-45 transition-transform duration-200 text-xl leading-none shrink-0 ml-3">+</span>
              </summary>
              <p className="mt-3 text-sm text-foreground leading-relaxed">{item.answer}</p>
            </details>
          ))}
        </div>

      </div>
    </main>
  )
}
