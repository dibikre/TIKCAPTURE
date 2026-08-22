import { useEffect } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { LogOut, User, Crown, Mail, Calendar, Hammer, AlertTriangle } from 'lucide-react'
import { useAuthStore } from '../stores/authStore'
import { useSeoMeta } from '../hooks/Useseometa'
import { BASE_URL } from '../lib/constants'
import { cn } from '../lib/utils'

export function Dashboard() {
  const { user, clearAuth, isLoading } = useAuthStore()
  const navigate = useNavigate()

  useSeoMeta({
    title:       'Tableau de bord – TikCapture',
    description: 'Gérez votre compte TikCapture.',
    canonical:   `${BASE_URL}/dashboard`,
    noIndex:     true,
  } as Parameters<typeof useSeoMeta>[0])

  useEffect(() => {
    if (!isLoading && !user) navigate('/connexion')
  }, [user, isLoading, navigate])

  const handleLogout = () => {
    clearAuth()
    navigate('/')
  }

  if (isLoading || !user) {
    return (
      <main className="relative z-10 pt-28 pb-16 min-h-screen flex items-center justify-center">
        <div className="w-8 h-8 border-2 border-[#FF0050]/30 border-t-[#FF0050] rounded-full animate-spin" />
      </main>
    )
  }

  const isPremium   = user.is_premium === 1
  const expiresAt   = user.subscription_expires_at
    ? new Date(user.subscription_expires_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
    : null

  return (
    <main className="relative z-10 pt-28 pb-16">
      <div className="container mx-auto px-4 max-w-2xl space-y-6">

        {/* ── Banner Construction ── */}
        <div className="glass rounded-2xl border border-yellow-500/20 bg-yellow-500/5 p-6 relative overflow-hidden group">
          {/* Background pattern */}
          <div className="absolute top-0 right-0 w-32 h-32 bg-yellow-500/10 rounded-full blur-3xl -mr-10 -mt-10" />
          
          <div className="relative z-10 flex flex-col sm:flex-row items-center gap-5">
            <div className="w-14 h-14 rounded-2xl bg-yellow-500/20 flex items-center justify-center shrink-0 animate-pulse">
              <Hammer className="w-7 h-7 text-yellow-500" />
            </div>
            
            <div className="text-center sm:text-left space-y-1">
              <div className="flex items-center justify-center sm:justify-start gap-2 text-yellow-500">
                <AlertTriangle className="w-4 h-4" />
                <span className="text-xs font-bold uppercase tracking-wider">Interface en développement</span>
              </div>
              <h2 className="text-2xl font-black text-foreground">SECTION EN CONSTRUCTION</h2>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Nous travaillons actuellement sur de nouvelles fonctionnalités pour votre espace personnel. Revenez bientôt !
              </p>
            </div>
          </div>

          {/* Warning stripes at the bottom */}
          <div className="absolute bottom-0 left-0 right-0 h-1 flex">
            {[...Array(20)].map((_, i) => (
              <div 
                key={i} 
                className={cn(
                  "flex-1 h-full",
                  i % 2 === 0 ? "bg-yellow-500/40" : "bg-transparent"
                )} 
              />
            ))}
          </div>
        </div>

        {/* Profil */}
        <div className="glass rounded-2xl p-6 border border-white/10 space-y-5">

          <div className="flex items-center gap-4">
            {/* Avatar */}
            <div className="w-16 h-16 rounded-2xl bg-[#FF0050]/10 border border-[#FF0050]/20 flex items-center justify-center overflow-hidden shrink-0">
              {user.avatar_url ? (
                <img src={user.avatar_url} alt={user.username} className="w-full h-full object-cover" />
              ) : (
                <User className="w-7 h-7 text-[#FF0050]" />
              )}
            </div>

            <div className="flex-1 min-w-0">
              <div className="flex items-center gap-2 flex-wrap">
                <h1 className="text-xl font-bold truncate">{user.full_name || user.username}</h1>
                {isPremium && (
                  <span className="flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-500/15 text-yellow-400 border border-yellow-500/20">
                    <Crown className="w-3 h-3" /> Premium
                  </span>
                )}
              </div>
              <p className="text-sm text-muted">@{user.username}</p>
            </div>

            <button
              onClick={handleLogout}
              className="flex items-center gap-2 px-4 py-2 rounded-xl border border-white/10 text-sm text-muted hover:text-red-400 hover:border-red-400/30 transition"
            >
              <LogOut className="w-4 h-4" />
              <span className="hidden sm:inline">Déconnexion</span>
            </button>
          </div>

          {/* Infos */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-white/8">
            <div className="flex items-center gap-3 p-3 rounded-xl bg-white/3">
              <Mail className="w-4 h-4 text-muted shrink-0" />
              <div className="min-w-0">
                <p className="text-xs text-muted">Email</p>
                <p className="text-sm font-medium truncate">{user.email}</p>
              </div>
            </div>

            <div className="flex items-center gap-3 p-3 rounded-xl bg-white/3">
              <Crown className="w-4 h-4 text-muted shrink-0" />
              <div>
                <p className="text-xs text-muted">Abonnement</p>
                <p className="text-sm font-medium">
                  {isPremium ? (user.subscription_plan || 'Premium') : 'Gratuit'}
                </p>
              </div>
            </div>

            {isPremium && expiresAt && (
              <div className="flex items-center gap-3 p-3 rounded-xl bg-white/3 sm:col-span-2">
                <Calendar className="w-4 h-4 text-muted shrink-0" />
                <div>
                  <p className="text-xs text-muted">Expiration</p>
                  <p className="text-sm font-medium">{expiresAt}</p>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Accès premium */}
        {!isPremium && (
          <div className="glass rounded-2xl p-6 border border-[#FF0050]/20 space-y-3">
            <div className="flex items-center gap-2">
              <Crown className="w-5 h-5 text-yellow-400" />
              <h2 className="font-semibold">Accès Premium</h2>
            </div>
            <p className="text-sm text-muted">
              Débloquez les vidéos complètes en streaming HD via Doodstream sur toutes les pages créateurs.
            </p>
            <Link
              to="/contact"
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#FF0050] hover:bg-[#e0004a] text-white text-sm font-semibold transition"
            >
              En savoir plus
            </Link>
          </div>
        )}

        {/* Raccourcis */}
        <div className="glass rounded-2xl p-5 border border-white/10 space-y-3">
          <h2 className="text-sm font-semibold text-muted uppercase tracking-wider">Accès rapide</h2>
          <div className="grid grid-cols-2 gap-3">
            <Link
              to="/createurs"
              className="flex items-center gap-3 p-3 rounded-xl bg-white/3 hover:bg-white/8 transition border border-white/8"
            >
              <span className="text-xl">🎬</span>
              <span className="text-sm font-medium">Créateurs</span>
            </Link>
            <Link
              to="/blog"
              className="flex items-center gap-3 p-3 rounded-xl bg-white/3 hover:bg-white/8 transition border border-white/8"
            >
              <span className="text-xl">📖</span>
              <span className="text-sm font-medium">Blog</span>
            </Link>
          </div>
        </div>

      </div>
    </main>
  )
}