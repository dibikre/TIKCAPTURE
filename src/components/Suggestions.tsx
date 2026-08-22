import { useState, useEffect, useRef } from 'react'
import { Lightbulb, CheckCircle2, Loader2, Star, AlertCircle } from 'lucide-react'
import { useSeoMeta } from '../hooks/Useseometa'
import { BASE_URL } from '../lib/constants'

// ─── Types ────────────────────────────────────────────────────────────────────

type Status = 'idle' | 'loading' | 'success' | 'error'

declare global {
  interface Window {
    turnstile?: {
      render: (container: string | HTMLElement, options: Record<string, unknown>) => string
      reset:  (widgetId: string) => void
      remove: (widgetId: string) => void
    }
  }
}

// ─── Constantes ───────────────────────────────────────────────────────────────

const API_URL  = `${BASE_URL}/segment_page/api/suggestion.php`
const SITE_KEY = '0x4AAAAAACnAeDMEtH-f7HaL' // ← Même SITE KEY que Contact (même widget Turnstile)

const CATEGORIES = [
  'Nouvelle fonctionnalité',
  'Amélioration existante',
  'Interface / Design',
  'Performance',
  'Autre',
]

// ─── Component ────────────────────────────────────────────────────────────────

export function Suggestions({ onNavigateHome: _ }: { onNavigateHome?: () => void }) {
  const [category, setCategory] = useState('')
  const [title,    setTitle]    = useState('')
  const [detail,   setDetail]   = useState('')
  const [rating,   setRating]   = useState(0)
  const [hovered,  setHovered]  = useState(0)
  const [website,  setWebsite]  = useState('') // honeypot
  const [status,   setStatus]   = useState<Status>('idle')
  const [errorMsg, setErrorMsg] = useState<string>('')

  const turnstileRef   = useRef<HTMLDivElement>(null)
  const widgetIdRef    = useRef<string | null>(null)
  const turnstileToken = useRef<string>('')

  // ── SEO ──
  useSeoMeta({
    title:         'Suggestions – Proposez vos idées pour TikCapture',
    description:   'Partagez vos idées et suggestions pour améliorer TikCapture. Nouvelle fonctionnalité, design, performance — chaque suggestion est lue et prise en compte.',
    canonical:     `${BASE_URL}/suggestion`,
    ogTitle:       'Suggestions TikCapture – Votre avis compte',
    ogDescription: 'Aidez-nous à améliorer TikCapture en partageant vos idées. Toutes les suggestions sont examinées pour les prochaines mises à jour.',
    ogImage:       `${BASE_URL}/og-image.png`,
  })

  // ── Charger le script Turnstile + render widget ──
  useEffect(() => {
    const scriptId = 'cf-turnstile-script'

    const renderWidget = () => {
      if (!turnstileRef.current || !window.turnstile) return
      if (widgetIdRef.current) return

      widgetIdRef.current = window.turnstile.render(turnstileRef.current, {
        sitekey:            SITE_KEY,
        theme:              'dark',
        size:               'normal',
        callback:           (token: string) => { turnstileToken.current = token },
        'expired-callback': () => { turnstileToken.current = '' },
        'error-callback':   () => { turnstileToken.current = '' },
      })
    }

    const isPrerender = navigator.userAgent.includes('ReactSnap')
    if (!isPrerender) {
      if (!document.getElementById(scriptId)) {
        const s    = document.createElement('script')
        s.id       = scriptId
        s.src      = 'https://challenges.cloudflare.com/turnstile/v0/api.js'
        s.async    = true
        s.defer    = true
        s.onload   = renderWidget
        document.head.appendChild(s)
      } else if (window.turnstile) {
        renderWidget()
      }
    }

    return () => {
      if (widgetIdRef.current && window.turnstile) {
        window.turnstile.remove(widgetIdRef.current)
        widgetIdRef.current = null
      }
    }
  }, [])

  // ── Reset Turnstile après erreur ──
  const resetTurnstile = () => {
    if (widgetIdRef.current && window.turnstile) {
      window.turnstile.reset(widgetIdRef.current)
      turnstileToken.current = ''
    }
  }

  // ── Envoi ──
  const handleSubmit = async () => {
    setErrorMsg('')

    if (!title.trim() || !detail.trim()) {
      setErrorMsg('Veuillez remplir tous les champs obligatoires.')
      return
    }

    if (!turnstileToken.current) {
      setErrorMsg('Veuillez compléter la vérification anti-bot.')
      return
    }

    setStatus('loading')

    try {
      const res = await fetch(API_URL, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title,
          detail,
          category,
          rating,
          website,                              // honeypot
          cf_turnstile_token: turnstileToken.current,
        }),
      })

      const data: { success: boolean; message: string } = await res.json()

      if (data.success) {
        setStatus('success')
      } else {
        setErrorMsg(data.message || 'Une erreur est survenue.')
        setStatus('error')
        resetTurnstile()
      }
    } catch {
      setErrorMsg('Impossible de joindre le serveur. Vérifiez votre connexion.')
      setStatus('error')
      resetTurnstile()
    }
  }

  const isDisabled = status === 'loading' || !title || !detail

  // ─── Render ───────────────────────────────────────────────────────────────

  return (
    <main className="relative z-10 pt-24 md:pt-32 pb-20">
      <div className="mx-auto px-4 sm:px-6 w-full max-w-2xl">

        {/* Header */}
        <div className="text-center mb-12 animate-fade-in">
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/10 mb-6">
            <Lightbulb className="w-4 h-4 text-[#FF0050]" />
            <span className="text-sm text-muted-foreground">Votre avis compte</span>
          </div>
          <h1 className="text-4xl sm:text-5xl font-black mb-4 tracking-tight">
            Vos <span className="gradient-text">suggestions</span>
          </h1>
          <p className="text-base text-muted max-w-md mx-auto leading-relaxed">
            Partagez vos idées pour améliorer TikCapture. Chaque suggestion est lue et prise en compte.
          </p>
        </div>

        {/* Succès */}
        {status === 'success' ? (
          <div className="flex flex-col items-center gap-4 py-16 text-center animate-fade-in-up">
            <div className="w-16 h-16 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center">
              <CheckCircle2 className="w-8 h-8 text-emerald-400" />
            </div>
            <h2 className="text-xl font-bold text-foreground">Merci pour votre suggestion !</h2>
            <p className="text-muted-foreground text-sm max-w-xs">
              Nous l'avons bien reçue et l'examinerons attentivement pour les prochaines mises à jour.
            </p>
          </div>

        ) : (
          <div className="rounded-2xl glass border border-white/10 p-6 sm:p-8 space-y-5 animate-fade-in-up">

            {/* Rating */}
            <div className="space-y-2">
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Votre satisfaction actuelle</label>
              <div className="flex gap-1">
                {[1,2,3,4,5].map(n => (
                  <button
                    key={n}
                    onClick={() => setRating(n)}
                    onMouseEnter={() => setHovered(n)}
                    onMouseLeave={() => setHovered(0)}
                    className="transition-transform hover:scale-110"
                  >
                    <Star className={`w-7 h-7 transition-colors ${(hovered || rating) >= n ? 'text-[#FF0050] fill-[#FF0050]' : 'text-white/20'}`} />
                  </button>
                ))}
              </div>
            </div>

            {/* Catégorie */}
            <div className="space-y-2">
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Catégorie</label>
              <div className="flex flex-wrap gap-2">
                {CATEGORIES.map(c => (
                  <button
                    key={c}
                    onClick={() => setCategory(c)}
                    className={`px-3 py-1.5 rounded-full text-xs font-medium border transition-all ${
                      category === c
                        ? 'bg-[#FF0050]/20 border-[#FF0050]/50 text-[#FF0050]'
                        : 'glass border-white/10 text-muted-foreground hover:border-white/20'
                    }`}
                  >
                    {c}
                  </button>
                ))}
              </div>
            </div>

            {/* Titre */}
            <div className="space-y-1.5">
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Titre de votre suggestion *</label>
              <input
                value={title} onChange={e => setTitle(e.target.value)}
                placeholder="Ex : Ajouter le téléchargement en lot"
                maxLength={150}
                className="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-[#FF0050]/50 transition-colors"
              />
            </div>

            {/* Détails */}
            <div className="space-y-1.5">
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Détails *</label>
              <textarea
                value={detail} onChange={e => setDetail(e.target.value)}
                rows={4} placeholder="Décrivez votre idée en détail..."
                maxLength={3000}
                className="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-[#FF0050]/50 transition-colors resize-none"
              />
              <p className="text-xs text-muted-foreground text-right">{detail.length}/3000</p>
            </div>

            {/* Honeypot — invisible */}
            <div aria-hidden="true" style={{ position: 'absolute', left: '-9999px', opacity: 0, height: 0, overflow: 'hidden' }}>
              <input
                tabIndex={-1}
                autoComplete="off"
                value={website}
                onChange={e => setWebsite(e.target.value)}
                name="website"
                type="text"
              />
            </div>

            {/* Cloudflare Turnstile */}
            <div className="flex justify-center">
              <div ref={turnstileRef} />
            </div>

            {/* Erreur */}
            {(status === 'error' || errorMsg) && (
              <div className="flex items-center gap-2 text-sm text-red-400">
                <AlertCircle className="w-4 h-4 shrink-0" />
                {errorMsg || 'Une erreur est survenue. Réessayez.'}
              </div>
            )}

            {/* Submit */}
            <button
              onClick={handleSubmit}
              disabled={isDisabled}
              className="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-[#FF0050] hover:bg-[#e0004a] disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-sm transition-all hover:scale-[1.02] active:scale-95"
            >
              {status === 'loading'
                ? <><Loader2 className="w-4 h-4 animate-spin" /> Envoi…</>
                : <><Lightbulb className="w-4 h-4" /> Envoyer ma suggestion</>
              }
            </button>

          </div>
        )}

      </div>
    </main>
  )
}