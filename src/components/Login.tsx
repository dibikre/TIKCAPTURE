import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Eye, EyeOff, LogIn } from 'lucide-react'
import { useAuthStore } from '../stores/authStore'
import { useSeoMeta } from '../hooks/Useseometa'
import { BASE_URL, AUTH_API } from '../lib/constants'

export function Login() {
  const navigate   = useNavigate()
  const { setAuth } = useAuthStore()

  const [email,    setEmail]    = useState('')
  const [password, setPassword] = useState('')
  const [showPwd,  setShowPwd]  = useState(false)
  const [error,    setError]    = useState<string | null>(null)
  const [loading,  setLoading]  = useState(false)

  useSeoMeta({
    title:       'Connexion – TikCapture',
    description: 'Connectez-vous à votre compte TikCapture pour accéder à toutes les vidéos en replay.',
    canonical:   `${BASE_URL}/connexion`,
  })

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      const res  = await fetch(`${AUTH_API}?action=login`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ email, password }),
      })
      const data = await res.json()
      if (data.status === 'success') {
        setAuth(data.data.user, data.data.token)
        navigate('/dashboard')
      } else if (res.status === 403 && data.errors?.needs_verification) {
        // Rediriger vers la vérification si nécessaire
        navigate('/verifier-compte', { 
          state: { 
            userId: data.errors.user_id, 
            email: data.errors.email 
          } 
        })
      } else {
        setError(data.message || 'Erreur de connexion')
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

          {/* Header */}
          <div className="text-center space-y-2">
            <div className="w-12 h-12 rounded-2xl bg-[#FF0050]/10 border border-[#FF0050]/20 flex items-center justify-center mx-auto">
              <LogIn className="w-5 h-5 text-[#FF0050]" />
            </div>
            <h1 className="text-2xl font-bold">Connexion</h1>
            <p className="text-sm text-muted">Accédez aux vidéos complètes en replay</p>
          </div>

          {/* Erreur */}
          {error && (
            <div className="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-red-400 text-sm text-center">
              {error}
            </div>
          )}

          {/* Formulaire */}
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm text-muted mb-1.5">Email</label>
              <input
                type="email"
                value={email}
                onChange={e => setEmail(e.target.value)}
                required
                placeholder="vous@exemple.com"
                className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-foreground placeholder:text-muted focus:outline-none focus:border-[#FF0050]/50 transition"
              />
            </div>

            <div>
              <label className="block text-sm text-muted mb-1.5">Mot de passe</label>
              <div className="relative">
                <input
                  type={showPwd ? 'text' : 'password'}
                  value={password}
                  onChange={e => setPassword(e.target.value)}
                  required
                  placeholder="••••••••"
                  className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 pr-11 text-sm text-foreground placeholder:text-muted focus:outline-none focus:border-[#FF0050]/50 transition"
                />
                <button
                  type="button"
                  onClick={() => setShowPwd(v => !v)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-foreground transition"
                >
                  {showPwd ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-[#FF0050] hover:bg-[#e0004a] disabled:opacity-50 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2"
            >
              {loading ? (
                <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              ) : (
                <LogIn className="w-4 h-4" />
              )}
              {loading ? 'Connexion…' : 'Se connecter'}
            </button>
          </form>

          {/* Footer */}
          <div className="flex flex-col gap-3 text-center text-sm">
            <Link to="/mot-de-passe-oublie" className="text-[#FF0050] hover:underline font-medium">
              Mot de passe oublié ?
            </Link>
            <p className="text-muted">
              Pas encore de compte ?{' '}
              <Link to="/inscription" className="text-[#FF0050] hover:underline font-medium">
                S'inscrire gratuitement
              </Link>
            </p>
          </div>
        </div>
      </div>
    </main>
  )
}