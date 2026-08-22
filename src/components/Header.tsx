import { useState, useEffect, useMemo, useRef, useCallback } from 'react'
import { Menu, X, LogIn, User } from 'lucide-react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { ThemeToggleMini } from './ThemeToggle.tsx'
import { useAuthStore } from '../stores/authStore'
import { BASE_URL } from '../lib/constants'
import { navItems } from '../lib/navigation'
import { cn } from '../lib/utils'

export function Header() {
  const location  = useLocation()
  const navigate  = useNavigate()
  const { user, clearAuth, isLoading } = useAuthStore()
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)
  const [showUserMenu,     setShowUserMenu]      = useState(false)

  useEffect(() => {
    const timer = setTimeout(() => setIsMobileMenuOpen(false), 0)
    return () => clearTimeout(timer)
  }, [location.pathname])

  // Fermer le menu user si on clique ailleurs
  useEffect(() => {
    if (!showUserMenu) return
    const handler = () => setShowUserMenu(false)
    document.addEventListener('click', handler)
    return () => document.removeEventListener('click', handler)
  }, [showUserMenu])

  const isActive = useCallback((to: string) => {
    if (to === '/') return location.pathname === '/'
    return location.pathname.startsWith(to)
  }, [location.pathname])

  const activeIndex = useMemo(() => {
    return navItems.findIndex(item => isActive(item.to))
  }, [isActive])

  const [gliderStyle, setGliderStyle] = useState({ left: 0, width: 0 })
  const navItemRefs = useRef<(HTMLAnchorElement | null)[]>([])

  useEffect(() => {
    const updateGlider = () => {
      // Use requestAnimationFrame to ensure the DOM has settled
      requestAnimationFrame(() => {
        const activeRef = navItemRefs.current[activeIndex]
        if (activeRef) {
          setGliderStyle({
            left: activeRef.offsetLeft,
            width: activeRef.offsetWidth,
          })
        }
      })
    }
    
    updateGlider()
    window.addEventListener('resize', updateGlider)
    return () => window.removeEventListener('resize', updateGlider)
  }, [activeIndex])

  return (
    <header className="fixed top-0 left-0 right-0 z-50 px-4 sm:px-6 py-4 sm:py-6">

      {/* ── Floating pill ── */}
      <div className="max-w-7xl mx-auto flex items-center justify-between bg-black/5 dark:bg-black/20 backdrop-blur-xl border border-black/10 dark:border-white/10 rounded-2xl px-4 sm:px-6 py-3">

        {/* Logo */}
        <button onClick={() => navigate('/')} className="flex items-center gap-2 group shrink-0">
          <img
            src={`${BASE_URL}/logo.png`}
            alt="TikCapture"
            width="160"
            height="40"
            fetchPriority="high"
            className="h-8 md:h-10 w-auto object-contain group-hover:opacity-90 transition-opacity duration-300"
          />
        </button>

        {/* Nav Desktop - New Glass Design */}
        <nav 
          className="hidden lg:flex items-center glass-nav-group relative"
        >
          {activeIndex !== -1 && (
            <div 
              className="nav-glider" 
              style={{ 
                left: `${gliderStyle.left}px`,
                width: `${gliderStyle.width}px`,
                position: 'absolute'
              }} 
            />
          )}
          {navItems.map((item, index) => (
            <Link
              key={item.label}
              to={item.to}
              ref={(el) => { navItemRefs.current[index] = el }}
              className={cn(
                'glass-nav-item flex items-center gap-2',
                isActive(item.to) && 'active'
              )}
            >
              <span className="shrink-0">{item.icon}</span>
              <span>{item.label}</span>
            </Link>
          ))}
        </nav>

        {/* Actions */}
        <div className="flex items-center gap-2 shrink-0">
          <ThemeToggleMini />

          {/* Auth */}
          {!isLoading && (
            <>
              {user ? (
                /* ── Utilisateur connecté ── */
                <div className="relative hidden lg:block">
                  <button
                    onClick={(e) => { e.stopPropagation(); setShowUserMenu(v => !v) }}
                    className="flex items-center gap-2 px-3 py-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 transition"
                  >
                    {user.avatar_url ? (
                      <img src={user.avatar_url} alt={user.username} className="w-6 h-6 rounded-full object-cover" />
                    ) : (
                      <div className="w-6 h-6 rounded-full bg-[#FF0050]/20 flex items-center justify-center">
                        <User className="w-3.5 h-3.5 text-[#FF0050]" />
                      </div>
                    )}
                    <span className="text-sm font-medium max-w-20 truncate">{user.username}</span>
                  </button>

                  {/* Dropdown */}
                  {showUserMenu && (
                    <div className="absolute right-0 top-full mt-2 w-48 glass rounded-xl border border-white/10 py-2 shadow-xl">
                      <Link
                        to="/dashboard"
                        className="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-white/5 transition"
                        onClick={() => setShowUserMenu(false)}
                      >
                        <User className="w-4 h-4 text-muted" />
                        Mon compte
                      </Link>
                      <div className="h-px bg-white/8 my-1" />
                      <button
                        onClick={() => { clearAuth(); setShowUserMenu(false); navigate('/') }}
                        className="flex items-center gap-2 px-4 py-2.5 text-sm text-red-400 hover:bg-red-500/5 transition w-full text-left"
                      >
                        <LogIn className="w-4 h-4 rotate-180" />
                        Déconnexion
                      </button>
                    </div>
                  )}
                </div>
              ) : (
                /* ── Non connecté ── */
                <div className="hidden lg:flex items-center gap-2">
                  <Link
                    to="/connexion"
                    className="flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-medium text-foreground hover:text-foreground/80 transition"
                  >
                    <LogIn className="w-4 h-4" />
                    Connexion
                  </Link>
                  <Link
                    to="/inscription"
                    className="flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold bg-[#FF0050] hover:bg-[#e0004a] text-white transition"
                  >
                    S'inscrire
                  </Link>
                </div>
              )}
            </>
          )}

          <button
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            className="lg:hidden p-2 rounded-xl text-foreground/60 hover:text-foreground hover:bg-black/5 dark:hover:bg-white/10 transition-all duration-200"
            aria-label="Toggle menu"
          >
            {isMobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
          </button>
        </div>
      </div>

      {/* ── Mobile menu ── */}
      <div
        className={cn(
          'lg:hidden max-w-7xl mx-auto mt-2',
          'bg-black/5 dark:bg-black/20 backdrop-blur-xl border border-black/10 dark:border-white/10 rounded-2xl overflow-hidden',
          'transition-all duration-300 ease-in-out',
          isMobileMenuOpen ? 'opacity-100 max-h-screen py-3 px-4' : 'opacity-0 max-h-0 pointer-events-none'
        )}
      >
        <nav className="space-y-1">
          {navItems.map((item) => (
            <Link
              key={item.label}
              to={item.to}
              className={cn(
                'flex items-center gap-3 px-4 py-3 rounded-xl',
                'text-sm font-medium transition-all duration-200 active:scale-95',
                isActive(item.to)
                  ? 'bg-[#FF0050]/10 text-black dark:text-white font-bold border border-[#FF0050]/20'
                  : 'text-foreground hover:bg-black/5 dark:hover:bg-white/5 hover:text-foreground/80'
              )}
            >
              <div className={cn(
                'p-2 rounded-lg',
                isActive(item.to) ? 'bg-[#FF0050]/20 text-[#FF0050]' : 'bg-black/5 dark:bg-white/5 text-[#FF0050]'
              )}>
                {item.icon}
              </div>
              <span>{item.label}</span>
              {isActive(item.to) && (
                <span className="ml-auto w-1.5 h-1.5 rounded-full bg-[#FF0050]" />
              )}
            </Link>
          ))}

          {/* Auth mobile */}
          <div className="pt-2 border-t border-white/8 space-y-1">
            {user ? (
              <>
                <Link to="/dashboard" className="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium hover:bg-white/5 transition">
                  <User className="w-4 h-4 text-[#FF0050]" />
                  <span>Mon compte (@{user.username})</span>
                </Link>
                <button
                  onClick={() => { clearAuth(); navigate('/') }}
                  className="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-red-400 hover:bg-red-500/5 transition w-full text-left"
                >
                  <LogIn className="w-4 h-4 rotate-180" />
                  Déconnexion
                </button>
              </>
            ) : (
              <>
                <Link to="/connexion" className="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium hover:bg-white/5 transition">
                  <LogIn className="w-4 h-4 text-[#FF0050]" />
                  Connexion
                </Link>
                <Link to="/inscription" className="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold bg-[#FF0050]/10 text-[#FF0050] border border-[#FF0050]/20 transition">
                  S'inscrire gratuitement
                </Link>
              </>
            )}
          </div>
        </nav>
      </div>

    </header>
  )
}