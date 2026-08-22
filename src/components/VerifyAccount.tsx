import { useState, useEffect } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { ShieldCheck, ArrowRight } from 'lucide-react'
import { useAuthStore } from '../stores/authStore'
import { useSeoMeta } from '../hooks/Useseometa'
import { AUTH_API, BASE_URL } from '../lib/constants'

export function VerifyAccount() {
  const location = useLocation()
  const navigate = useNavigate()
  const { setAuth } = useAuthStore()
  
  const [userId] = useState<number | null>(location.state?.userId || null)
  const [email]   = useState<string>(location.state?.email || '')
  const [code, setCode]     = useState('')
  const [error, setError]   = useState<string | null>(null)
  const [message, setMessage] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)
  const [resending, setResending] = useState(false)
  const [countdown, setCountdown] = useState(0)

  useSeoMeta({
    title: 'Vérification du compte – TikCapture',
    description: 'Vérifiez votre adresse email pour activer votre compte TikCapture.',
    canonical: `${BASE_URL}/verifier-compte`,
  })

  useEffect(() => {
    if (!userId) {
      navigate('/inscription')
    }
  }, [userId, navigate])

  useEffect(() => {
    let timer: ReturnType<typeof setInterval>
    if (countdown > 0) {
      timer = setInterval(() => setCountdown(prev => prev - 1), 1000)
    }
    return () => clearInterval(timer)
  }, [countdown])

  const handleVerify = async (e: React.FormEvent) => {
    e.preventDefault()
    if (code.length !== 6) {
      setError('Le code doit contenir 6 chiffres.')
      return
    }
    setError(null)
    setMessage(null)
    setLoading(true)
    try {
      const res = await fetch(`${AUTH_API}?action=register-verify`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId, verification_code: code }),
      })
      const data = await res.json()
      if (data.status === 'success') {
        setAuth(data.data.user, data.data.token)
        navigate('/dashboard')
      } else {
        setError(data.message || 'Code invalide ou expiré.')
      }
    } catch {
      setError('Erreur réseau, réessayez.')
    } finally {
      setLoading(false)
    }
  }

  const handleResend = async () => {
    if (countdown > 0) return
    setError(null)
    setMessage(null)
    setResending(true)
    try {
      const res = await fetch(`${AUTH_API}?action=resend-code`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId }),
      })
      const data = await res.json()
      if (data.status === 'success') {
        setMessage('Un nouveau code a été envoyé à ' + email)
        setCountdown(60)
      } else {
        setError(data.message || 'Erreur lors du renvoi du code.')
      }
    } catch {
      setError('Erreur réseau, réessayez.')
    } finally {
      setResending(false)
    }
  }

  return (
    <main className="relative z-10 pt-28 pb-16 min-h-screen flex items-center justify-center">
      <div className="w-full max-w-md px-4">
        <div className="glass rounded-2xl p-8 border border-white/10 space-y-6">
          
          <div className="text-center space-y-2">
            <div className="w-12 h-12 rounded-2xl bg-[#FF0050]/10 border border-[#FF0050]/20 flex items-center justify-center mx-auto">
              <ShieldCheck className="w-5 h-5 text-[#FF0050]" />
            </div>
            <h1 className="text-2xl font-bold">Vérifiez votre email</h1>
            <p className="text-sm text-muted">
              Nous avons envoyé un code à 6 chiffres à <br />
              <span className="text-foreground font-medium">{email}</span>
            </p>
          </div>

          {error && (
            <div className="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-red-400 text-sm text-center">
              {error}
            </div>
          )}

          {message && (
            <div className="bg-green-500/10 border border-green-500/30 rounded-xl px-4 py-3 text-green-400 text-sm text-center">
              {message}
            </div>
          )}

          <form onSubmit={handleVerify} className="space-y-4">
            <div>
              <label className="block text-sm text-muted mb-1.5 text-center">Code de vérification (6 chiffres)</label>
              <input
                type="text"
                maxLength={6}
                value={code}
                onChange={e => setCode(e.target.value.replace(/\D/g, ''))}
                required
                placeholder="123456"
                className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-4 text-center text-2xl tracking-[0.5em] font-mono focus:outline-none focus:border-[#FF0050]/50 transition"
              />
            </div>

            <button
              type="submit"
              disabled={loading || code.length !== 6}
              className="w-full bg-[#FF0050] hover:bg-[#e0004a] disabled:opacity-50 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2"
            >
              {loading ? (
                <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              ) : (
                <ArrowRight className="w-4 h-4" />
              )}
              {loading ? 'Vérification…' : 'Confirmer'}
            </button>
          </form>

          <div className="text-center space-y-4">
            <p className="text-sm text-muted">
              Vous n'avez pas reçu l'email ? Checkez vos spams ou :
            </p>
            <button
              onClick={handleResend}
              disabled={resending || countdown > 0}
              className="text-[#FF0050] hover:underline font-medium text-sm disabled:text-muted disabled:no-underline transition"
            >
              {countdown > 0 ? `Renvoyer le code (${countdown}s)` : 'Renvoyer un nouveau code'}
            </button>
          </div>

          <div className="pt-4 border-t border-white/10 text-center">
            <button 
              onClick={() => navigate('/connexion')}
              className="text-xs text-muted hover:text-foreground transition"
            >
              Retour à la connexion
            </button>
          </div>
        </div>
      </div>
    </main>
  )
}
