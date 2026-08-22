import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { KeyRound, Eye, EyeOff, CheckCircle2 } from 'lucide-react'
import { useSeoMeta } from '../hooks/Useseometa'
import { AUTH_API, BASE_URL } from '../lib/constants'

export function ResetPassword() {
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token')

  const [password, setPassword] = useState('')
  const [confirm, setConfirm]   = useState('')
  const [showPwd, setShowPwd]   = useState(false)
  const [error, setError]       = useState<string | null>(null)
  const [success, setSuccess]   = useState(false)
  const [loading, setLoading]   = useState(false)

  useSeoMeta({
    title: 'Nouveau mot de passe – TikCapture',
    description: 'Définissez votre nouveau mot de passe TikCapture.',
    canonical: `${BASE_URL}/reset-password`,
  })

  if (!token) {
    return (
      <main className="pt-28 pb-16 min-h-screen flex items-center justify-center">
        <div className="glass p-8 rounded-2xl border border-white/10 text-center space-y-4">
          <p className="text-red-400">Lien de réinitialisation invalide ou manquant.</p>
          <Link to="/connexion" className="text-[#FF0050] hover:underline block">Retour à la connexion</Link>
        </div>
      </main>
    )
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (password !== confirm) {
      setError('Les mots de passe ne correspondent pas.')
      return
    }
    setError(null)
    setLoading(true)
    try {
      const res = await fetch(`${AUTH_API}?action=reset-password`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          token, 
          new_password: password, 
          new_password_confirm: confirm 
        }),
      })
      const data = await res.json()
      if (data.status === 'success') {
        setSuccess(true)
      } else {
        setError(data.message || 'Le lien a peut-être expiré.')
      }
    } catch {
      setError('Erreur réseau, réessayez.')
    } finally {
      setLoading(false)
    }
  }

  if (success) {
    return (
      <main className="relative z-10 pt-28 pb-16 min-h-screen flex items-center justify-center">
        <div className="w-full max-w-md px-4">
          <div className="glass rounded-2xl p-8 border border-white/10 text-center space-y-6">
            <div className="w-16 h-16 rounded-full bg-green-500/10 border border-green-500/20 flex items-center justify-center mx-auto">
              <CheckCircle2 className="w-8 h-8 text-green-500" />
            </div>
            <div className="space-y-2">
              <h1 className="text-2xl font-bold">Mot de passe modifié !</h1>
              <p className="text-sm text-muted">Votre mot de passe a été mis à jour avec succès.</p>
            </div>
            <Link 
              to="/connexion" 
              className="w-full bg-[#FF0050] hover:bg-[#e0004a] text-white font-semibold py-3 rounded-xl transition flex items-center justify-center"
            >
              Se connecter
            </Link>
          </div>
        </div>
      </main>
    )
  }

  return (
    <main className="relative z-10 pt-28 pb-16 min-h-screen flex items-center justify-center">
      <div className="w-full max-w-md px-4">
        <div className="glass rounded-2xl p-8 border border-white/10 space-y-6">
          
          <div className="text-center space-y-2">
            <div className="w-12 h-12 rounded-2xl bg-[#FF0050]/10 border border-[#FF0050]/20 flex items-center justify-center mx-auto">
              <KeyRound className="w-5 h-5 text-[#FF0050]" />
            </div>
            <h1 className="text-2xl font-bold">Nouveau mot de passe</h1>
            <p className="text-sm text-muted">Choisissez un mot de passe sécurisé.</p>
          </div>

          {error && (
            <div className="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-red-400 text-sm text-center">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm text-muted mb-1.5">Nouveau mot de passe</label>
              <div className="relative">
                <input
                  type={showPwd ? 'text' : 'password'}
                  value={password}
                  onChange={e => setPassword(e.target.value)}
                  required
                  placeholder="••••••••"
                  className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 pr-11 text-sm focus:outline-none focus:border-[#FF0050]/50 transition"
                />
                <button
                  type="button"
                  onClick={() => setShowPwd(!showPwd)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-foreground"
                >
                  {showPwd ? <EyeOff size={18} /> : <Eye size={18} />}
                </button>
              </div>
            </div>

            <div>
              <label className="block text-sm text-muted mb-1.5">Confirmer le mot de passe</label>
              <input
                type={showPwd ? 'text' : 'password'}
                value={confirm}
                onChange={e => setConfirm(e.target.value)}
                required
                placeholder="••••••••"
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
                <KeyRound className="w-4 h-4" />
              )}
              {loading ? 'Mise à jour…' : 'Changer le mot de passe'}
            </button>
          </form>
        </div>
      </div>
    </main>
  )
}
