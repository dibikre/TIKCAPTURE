import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Eye, EyeOff, UserPlus } from 'lucide-react'
import { useSeoMeta } from '../hooks/Useseometa'
import { BASE_URL, AUTH_API } from '../lib/constants'

export function Register() {
  const navigate    = useNavigate()

  const [username,  setUsername]  = useState('')
  const [email,     setEmail]     = useState('')
  const [fullName,  setFullName]  = useState('')
  const [password,  setPassword]  = useState('')
  const [showPwd,   setShowPwd]   = useState(false)
  const [error,     setError]     = useState<string | null>(null)
  const [loading,   setLoading]   = useState(false)

  useSeoMeta({
    title:       'Inscription – TikCapture',
    description: 'Créez votre compte TikCapture gratuitement et accédez aux replays complets.',
    canonical:   `${BASE_URL}/inscription`,
  })

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      const res  = await fetch(`${AUTH_API}?action=register-init`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ 
          username, 
          email, 
          password, 
          password_confirm: password, // For simplicity if second field not added
          full_name: fullName 
        }),
      })
      const data = await res.json()
      if (data.status === 'success') {
        // Redirect to verify account page with user details
        navigate('/verifier-compte', { 
          state: { 
            userId: data.data.user_id, 
            email: data.data.email 
          } 
        })
      } else {
        setError(data.message || 'Erreur lors de l\'inscription')
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
              <UserPlus className="w-5 h-5 text-[#FF0050]" />
            </div>
            <h1 className="text-2xl font-bold">Créer un compte</h1>
            <p className="text-sm text-muted">Gratuit — accès aux replays complets</p>
          </div>

          {/* Erreur */}
          {error && (
            <div className="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-red-400 text-sm text-center">
              {error}
            </div>
          )}

          {/* Formulaire */}
          <form onSubmit={handleSubmit} className="space-y-4">

            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="block text-sm text-muted mb-1.5">Username *</label>
                <input
                  type="text"
                  value={username}
                  onChange={e => setUsername(e.target.value)}
                  required
                  placeholder="monpseudo"
                  className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-foreground placeholder:text-muted focus:outline-none focus:border-[#FF0050]/50 transition"
                />
              </div>
              <div>
                <label className="block text-sm text-muted mb-1.5">Nom complet</label>
                <input
                  type="text"
                  value={fullName}
                  onChange={e => setFullName(e.target.value)}
                  placeholder="Optionnel"
                  className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-foreground placeholder:text-muted focus:outline-none focus:border-[#FF0050]/50 transition"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm text-muted mb-1.5">Email *</label>
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
              <label className="block text-sm text-muted mb-1.5">Mot de passe *</label>
              <div className="relative">
                <input
                  type={showPwd ? 'text' : 'password'}
                  value={password}
                  onChange={e => setPassword(e.target.value)}
                  required
                  placeholder="6 caractères minimum"
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
                <UserPlus className="w-4 h-4" />
              )}
              {loading ? 'Création…' : 'Créer mon compte'}
            </button>
          </form>

          {/* Footer */}
          <p className="text-center text-sm text-muted">
            Déjà un compte ?{' '}
            <Link to="/connexion" className="text-[#FF0050] hover:underline font-medium">
              Se connecter
            </Link>
          </p>
        </div>
      </div>
    </main>
  )
}