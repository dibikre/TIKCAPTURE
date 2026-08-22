import { useState, useEffect, useRef } from 'react'
import { Mail, MessageSquare, CheckCircle2, Loader2, AlertCircle } from 'lucide-react'
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

const API_URL  = `${BASE_URL}/segment_page/api/l-email.php`
const SITE_KEY = '0x4AAAAAACnAeDMEtH-f7HaL'

// ─── Component ────────────────────────────────────────────────────────────────

export function Contact() {
  const [name,     setName]     = useState('')
  const [email,    setEmail]    = useState('')
  const [subject,  setSubject]  = useState('')
  const [message,  setMessage]  = useState('')
  const [website,  setWebsite]  = useState('')
  const [status,   setStatus]   = useState<Status>('idle')
  const [errorMsg, setErrorMsg] = useState<string>('')

  const turnstileRef   = useRef<HTMLDivElement>(null)
  const widgetIdRef    = useRef<string | null>(null)
  const turnstileToken = useRef<string>('')

  useSeoMeta({
    title:         'Contact – TikCapture',
    description:   "Contactez l'équipe TikCapture pour toute question technique, demande de partenariat ou retour utilisateur. Nous répondons sous 24h.",
    canonical:     `${BASE_URL}/contact`,
    ogTitle:       'Contactez TikCapture – Réponse sous 24h',
    ogDescription: "Une question ou un problème ? Écrivez-nous. L'équipe TikCapture vous répond dans les plus brefs délais.",
    ogImage:       `${BASE_URL}/og-image.png`,
  })

  useEffect(() => {
    const scriptId = 'cf-turnstile-script'

    const renderWidget = () => {
      if (!turnstileRef.current || !window.turnstile) return
      if (widgetIdRef.current) return

      widgetIdRef.current = window.turnstile.render(turnstileRef.current, {
        sitekey:           SITE_KEY,
        theme:             'dark',
        size:              'normal',
        callback:          (token: string) => { turnstileToken.current = token },
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

  const handleSubmit = async () => {
    setErrorMsg('')

    if (!name.trim() || !email.trim() || !message.trim()) {
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
          name, email, subject, message,
          website,
          cf_turnstile_token: turnstileToken.current,
        }),
      })

      const data: { success: boolean; message: string } = await res.json()

      if (data.success) {
        setStatus('success')
      } else {
        setErrorMsg(data.message || 'Une erreur est survenue.')
        setStatus('error')
        if (widgetIdRef.current && window.turnstile) {
          window.turnstile.reset(widgetIdRef.current)
          turnstileToken.current = ''
        }
      }
    } catch {
      setErrorMsg('Impossible de joindre le serveur. Vérifiez votre connexion.')
      setStatus('error')
      if (widgetIdRef.current && window.turnstile) {
        window.turnstile.reset(widgetIdRef.current)
        turnstileToken.current = ''
      }
    }
  }

  const isDisabled = status === 'loading' || !name || !email || !message

  return (
    <main className="relative z-10 pt-24 md:pt-32 pb-20">
      <div className="mx-auto px-4 sm:px-6 w-full max-w-2xl">

        <div className="text-center mb-12 animate-fade-in">
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/10 mb-6">
            <Mail className="w-4 h-4 text-[#FF0050]" />
            <span className="text-sm text-muted-foreground">On vous répond sous 24h</span>
          </div>
          <h1 className="text-4xl sm:text-5xl font-black mb-4 tracking-tight">
            <span className="gradient-text">Contact</span>
          </h1>
          <p className="text-base text-muted max-w-md mx-auto leading-relaxed">
            Une question, un problème technique ou une demande de partenariat ? Écrivez-nous.
          </p>
        </div>

        {status === 'success' ? (
          <div className="flex flex-col items-center gap-4 py-16 text-center animate-fade-in-up">
            <div className="w-16 h-16 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center">
              <CheckCircle2 className="w-8 h-8 text-emerald-400" />
            </div>
            <h2 className="text-xl font-bold text-foreground">Message envoyé !</h2>
            <p className="text-muted-foreground text-sm max-w-xs">
              Nous avons bien reçu votre message et vous répondrons dans les plus brefs délais.
            </p>
          </div>
        ) : (
          <div className="rounded-2xl glass border border-white/10 p-6 sm:p-8 space-y-5 animate-fade-in-up">

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="space-y-1.5">
                <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Nom *</label>
                <input
                  value={name} onChange={e => setName(e.target.value)}
                  placeholder="Votre nom"
                  className="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-[#FF0050]/50 transition-colors"
                />
              </div>
              <div className="space-y-1.5">
                <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Email *</label>
                <input
                  type="email" value={email} onChange={e => setEmail(e.target.value)}
                  placeholder="votre@email.com"
                  className="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-[#FF0050]/50 transition-colors"
                />
              </div>
            </div>

            <div aria-hidden="true" style={{ position: 'absolute', left: '-9999px', opacity: 0, height: 0, overflow: 'hidden' }}>
              <input tabIndex={-1} autoComplete="off" value={website} onChange={e => setWebsite(e.target.value)} name="website" type="text" />
            </div>

            <div className="space-y-1.5">
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Sujet</label>
              <select
                value={subject} onChange={e => setSubject(e.target.value)}
                className="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-foreground focus:outline-none focus:border-[#FF0050]/50 transition-colors"
              >
                <option value="">Sélectionnez un sujet</option>
                <option value="bug">Problème technique</option>
                <option value="question">Question générale</option>
                <option value="partenariat">Partenariat</option>
                <option value="autre">Autre</option>
              </select>
            </div>

            <div className="space-y-1.5">
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Message *</label>
              <textarea
                value={message} onChange={e => setMessage(e.target.value)}
                rows={5} placeholder="Décrivez votre demande..."
                className="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-[#FF0050]/50 transition-colors resize-none"
              />
              <p className="text-xs text-muted-foreground text-right">{message.length}/5000</p>
            </div>

            <div className="flex justify-center">
              <div ref={turnstileRef} />
            </div>

            {(status === 'error' || errorMsg) && (
              <div className="flex items-center gap-2 text-sm text-red-400">
                <AlertCircle className="w-4 h-4 shrink-0" />
                {errorMsg || 'Une erreur est survenue. Réessayez.'}
              </div>
            )}

            <button
              onClick={handleSubmit}
              disabled={isDisabled}
              className="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-[#FF0050] hover:bg-[#e0004a] disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-sm transition-all hover:scale-[1.02] active:scale-95"
            >
              {status === 'loading'
                ? <><Loader2 className="w-4 h-4 animate-spin" /> Envoi en cours…</>
                : <><MessageSquare className="w-4 h-4" /> Envoyer le message</>
              }
            </button>

          </div>
        )}
      </div>
    </main>
  )
}