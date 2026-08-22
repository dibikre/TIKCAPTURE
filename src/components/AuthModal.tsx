import { Link } from 'react-router-dom'
import { X, LogIn, UserPlus, Lock } from 'lucide-react'

interface AuthModalProps {
  onClose: () => void
}

export function AuthModal({ onClose }: AuthModalProps) {
  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4"
      onClick={(e) => { if (e.target === e.currentTarget) onClose() }}
    >
      {/* Backdrop */}
      <div className="absolute inset-0 bg-black/60 backdrop-blur-sm" onClick={onClose} />

      {/* Modal */}
      <div className="relative glass rounded-2xl p-6 border border-white/15 w-full max-w-sm space-y-5 shadow-2xl">

        {/* Bouton fermer */}
        <button
          onClick={onClose}
          className="absolute top-4 right-4 text-muted hover:text-foreground transition"
        >
          <X className="w-5 h-5" />
        </button>

        {/* Icône + titre */}
        <div className="text-center space-y-2 pt-1">
          <div className="w-12 h-12 rounded-2xl bg-[#FF0050]/10 border border-[#FF0050]/20 flex items-center justify-center mx-auto">
            <Lock className="w-5 h-5 text-[#FF0050]" />
          </div>
          <h2 className="text-lg font-bold">Contenu réservé</h2>
          <p className="text-sm text-muted leading-relaxed">
            Connectez-vous ou créez un compte pour regarder la vidéo complète en streaming.
          </p>
        </div>

        {/* Boutons */}
        <div className="space-y-3">
          <Link
            to="/connexion"
            onClick={onClose}
            className="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-[#FF0050] hover:bg-[#e0004a] text-white font-semibold text-sm transition"
          >
            <LogIn className="w-4 h-4" />
            Se connecter
          </Link>
          <Link
            to="/inscription"
            onClick={onClose}
            className="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-foreground font-semibold text-sm transition"
          >
            <UserPlus className="w-4 h-4" />
            Créer un compte gratuit
          </Link>
        </div>

        <p className="text-center text-xs text-muted">
          La démo reste accessible sans compte.
        </p>
      </div>
    </div>
  )
}