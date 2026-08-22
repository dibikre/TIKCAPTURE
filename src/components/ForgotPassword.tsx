import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Mail, ArrowLeft, Send } from 'lucide-react'
import { useSeoMeta } from '../hooks/Useseometa'
import { AUTH_API, BASE_URL } from '../lib/constants'

export function ForgotPassword() {
  const [email, setEmail]   = useState('')
  const [error, setError]   = useState<string | null>(null)
  const [message, setMessage] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  useSeoMeta({
    title: 'Mot de passe oublié – TikCapture',
    description: 'Réinitialisez votre mot de passe TikCapture en quelques étapes.',
    canonical: `${BASE_URL}/mot-de-passe-oublie`,
  })

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    setMessage(null)
    setLoading(true)
    try {
      const res = await fetch(`${AUTH_API}?action=forgot-password`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email }),
      })
      const data = await res.json()
      if (data.status === 'success') {
        setMessage('Si cet email est enregistré, un lien de réinitialisation a été envoyé.')
        setEmail('')
      } else {
        setError(data.message || 'Une erreur est survenue.')
      }
    } catch {
      setError('Erreur réseau, réessayez.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="relative z-10 pt-28 pb-16 min-h-screen flex items-center justify-center">
      <div className="w-full max-w-md px-4">
        <div className="glass rounded-2xl p-8 border border-white/10 space-y-6">
          
          <div className="text-center space-y-2">
            <div className="w-12 h-12 rounded-2xl bg-[#FF0050]/10 border border-[#FF0050]/20 flex items-center justify-center mx-auto">
              <Mail className="w-5 h-5 text-[#FF0050]" />
            </div>
            <h1 className="text-2xl font-bold">Mot de passe oublié</h1>
            <p className="text-sm text-muted">
              Entrez votre email pour recevoir un lien de <br />
              réinitialisation de votre mot de passe.
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

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm text-muted mb-1.5">Adresse Email</label>
              <input
                type="email"
                value={email}
                onChange={e => setEmail(e.target.value)}
                required
                placeholder="vous@exemple.com"
                className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-[#FF0050]/50 transition"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-[#FF0050] hover:bg-[#e0004a] disabled:opacity-50 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2"
            >
              {loading ? (
                <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              ) : (
                <Send className="w-4 h-4" />
              )}
              {loading ? 'Envoi…' : 'Envoyer le lien'}
            </button>
          </form>

          <div className="text-center">
            <Link to="/connexion" className="inline-flex items-center gap-2 text-sm text-muted hover:text-foreground transition">
              <ArrowLeft className="w-4 h-4" />
              Retour à la connexion
            </Link>
          </div>
        </div>
      </div>
    </main>
  )
}
