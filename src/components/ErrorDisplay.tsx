import { AlertCircle, Search, Lightbulb } from 'lucide-react'
import { useLiveStore } from '../stores/liveStore'

interface ErrorDisplayProps {
  error: Error
  onRetry?: () => void
}

export function ErrorDisplay({ error, onRetry }: ErrorDisplayProps) {
  const { setSearchQuery } = useLiveStore()
  
  const errorMessage = error.message
  
  // Déterminer le type d'erreur
  const isNotFound = errorMessage.includes('non trouvé') || errorMessage.includes('not found')
  const isInvalidFormat = errorMessage.includes('Format invalide') || errorMessage.includes('invalide')
  
  // Suggestions de noms d'utilisateur populaires
  const popularUsers = ['charlidamelio', 'khaby.lame', 'zachking', 'bellapoarch', 'mrbeast']
  
  return (
    <div className="w-full max-w-2xl mx-auto mt-8 animate-fade-in-up">
      <div className="glass rounded-3xl p-8 text-center">
        {/* Icon */}
        <div className="w-20 h-20 rounded-full bg-red-500/10 flex items-center justify-center mx-auto mb-6">
          <AlertCircle className="w-10 h-10 text-red-500" />
        </div>
        
        {/* Title */}
        <h3 className="text-2xl font-bold mb-3">
          {isNotFound ? 'Utilisateur introuvable' : 
           isInvalidFormat ? 'Format invalide' : 
           'Erreur de recherche'}
        </h3>
        
        {/* Message */}
        <p className="text-muted-foreground mb-6 max-w-md mx-auto">
          {errorMessage}
        </p>
        
        {/* Suggestions si utilisateur non trouvé */}
        {isNotFound && (
          <div className="mb-6">
            <div className="flex items-center gap-2 justify-center mb-4 text-sm text-muted-foreground">
              <Lightbulb className="w-4 h-4" />
              <span>Essayez ces créateurs populaires :</span>
            </div>
            <div className="flex flex-wrap justify-center gap-2">
              {popularUsers.map((user) => (
                <button
                  key={user}
                  onClick={() => {
                    setSearchQuery(user)
                    onRetry?.()
                  }}
                  className="px-4 py-2 rounded-full bg-white/5 hover:bg-[#FF0050]/10 
                           border border-border hover:border-[#FF0050]/50
                           text-sm transition-all duration-300"
                >
                  @{user}
                </button>
              ))}
            </div>
          </div>
        )}
        
        {/* Aide format */}
        {isInvalidFormat && (
          <div className="mb-6 p-4 rounded-2xl bg-white/5 text-left text-sm">
            <p className="font-medium mb-2 text-foreground">Formats acceptés :</p>
            <ul className="space-y-2 text-muted-foreground">
              <li className="flex items-center gap-2">
                <span className="w-1.5 h-1.5 rounded-full bg-[#FF0050]" />
                <code className="bg-white/10 px-2 py-0.5 rounded">@username</code>
              </li>
              <li className="flex items-center gap-2">
                <span className="w-1.5 h-1.5 rounded-full bg-[#FF0050]" />
                <code className="bg-white/10 px-2 py-0.5 rounded">username</code>
              </li>
              <li className="flex items-center gap-2">
                <span className="w-1.5 h-1.5 rounded-full bg-[#FF0050]" />
                <code className="bg-white/10 px-2 py-0.5 rounded text-xs">tiktok.com/@username</code>
              </li>
              <li className="flex items-center gap-2">
                <span className="w-1.5 h-1.5 rounded-full bg-[#FF0050]" />
                <code className="bg-white/10 px-2 py-0.5 rounded text-xs">vt.tiktok.com/xxxxx</code>
                <span className="text-xs text-yellow-500/80">(URL courte)</span>
              </li>
            </ul>
          </div>
        )}
        
        {/* Retry button */}
        {onRetry && (
          <button
            onClick={onRetry}
            className="inline-flex items-center gap-2 px-6 py-3 rounded-full
                     bg-[#FF0050] hover:bg-[#FF0050]/90 text-white font-semibold
                     transition-all duration-300 hover:scale-105"
          >
            <Search className="w-4 h-4" />
            Réessayer
          </button>
        )}
      </div>
    </div>
  )
}