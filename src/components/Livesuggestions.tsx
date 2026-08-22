import { Eye, Users, RefreshCw } from 'lucide-react'
import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'
import { useLiveStore } from '../stores/liveStore'
import { useLiveSuggestions } from '../hooks/Uselivesuggestions'

function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

function formatNumber(n: number): string {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K'
  return String(n)
}

interface LiveSuggestionsProps {
  onSelectUser: (username: string) => void
  keyword?: string | null  // forcé depuis LiveDetails quand utilisateur non trouvé
}

export function LiveSuggestions({ onSelectUser, keyword: keywordProp }: LiveSuggestionsProps) {
  const { recording, showSuggestion, searchResult } = useLiveStore()

  // Si keyword forcé (cas not found), on l'utilise directement
  const keyword = keywordProp ?? searchResult?.user?.uniqueId ?? ''
  const shouldFetch = keyword.length > 0 && (keywordProp != null || (showSuggestion && !recording.isRecording))

  const { data: suggestions, isLoading, isFetching, isError, refetch } = useLiveSuggestions(keyword, shouldFetch)

  // isLoading = premier chargement, isFetching = aussi pendant refetch
  const showSkeleton = isLoading || isFetching

  if (!showSuggestion || recording.isRecording) return null

  return (
    <div className="mt-6 animate-fade-in">
      {/* Titre + bouton actualiser */}
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-3">
          <span className="w-2 h-2 rounded-full bg-red-500 animate-pulse" />
          <h4 className="font-semibold text-foreground">
            Autres lives en cours
          </h4>
        </div>
        <button
          onClick={() => refetch()}
          disabled={isFetching}
          className={cn(
            'flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm',
            'bg-white/5 border border-white/10 text-muted-foreground',
            'transition-all duration-200',
            'hover:bg-white/10 hover:text-foreground hover:border-[#FF0050]/40',
            'active:scale-95 active:bg-[#FF0050]/10 active:border-[#FF0050]/60 active:text-[#FF0050]',
            isFetching && 'opacity-60 cursor-not-allowed'
          )}
        >
          <RefreshCw className={cn('w-3.5 h-3.5 transition-transform', isFetching && 'animate-spin')} />
          {isFetching ? 'Actualisation...' : 'Actualiser'}
        </button>
      </div>

      {/* Skeleton — premier chargement ET refetch */}
      {showSkeleton && (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
          {Array.from({ length: 6 }).map((_, i) => (
            <div
              key={i}
              className="rounded-2xl bg-white/5 overflow-hidden animate-pulse"
            >
              <div className="aspect-video bg-white/10" />
              <div className="p-3 space-y-2">
                <div className="h-3 bg-white/10 rounded w-3/4" />
                <div className="h-3 bg-white/10 rounded w-1/2" />
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Erreur */}
      {isError && (
        <p className="text-sm text-muted-foreground text-center py-4">
          Impossible de charger les suggestions.
        </p>
      )}

      {/* Grille de lives */}
      {!showSkeleton && !isError && suggestions && suggestions.length > 0 && (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
          {suggestions.map((live) => (
            <button
              key={live.username}
              onClick={() => onSelectUser(live.username)}
              className={cn(
                'group rounded-2xl overflow-hidden text-left',
                'bg-white/5 border border-white/10',
                'transition-all duration-300',
                'hover:border-[#FF0050]/50 hover:bg-white/10 hover:scale-[1.02]',
                'hover:shadow-[0_0_20px_rgba(255,0,80,0.15)]'
              )}
            >
              {/* Cover / capture stream */}
              <div className="relative aspect-video overflow-hidden bg-black">
                <img
                  src={live.cover || live.avatar}
                  alt={live.nickname}
                  loading="lazy"
                  referrerPolicy="no-referrer"
                  className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                  onError={(e) => {
                    const img = e.target as HTMLImageElement
                    if (img.src !== live.avatar) img.src = live.avatar
                  }}
                />

                {/* Badge LIVE */}
                <span className="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-red-500 text-white text-[10px] font-bold tracking-wide">
                  LIVE
                </span>

                {/* Viewers */}
                <span className="absolute bottom-2 right-2 flex items-center gap-1 px-2 py-0.5 rounded-full bg-black/60 text-white text-xs">
                  <Eye className="w-3 h-3" />
                  {formatNumber(live.viewers)}
                </span>
              </div>

              {/* Infos */}
              <div className="p-3">
                <div className="flex items-center gap-2 mb-1">
                  <img
                    src={live.avatar}
                    alt={live.nickname}
                    loading="lazy"
                    referrerPolicy="no-referrer"
                    className="w-6 h-6 rounded-full object-cover shrink-0"
                    onError={(e) => { (e.target as HTMLImageElement).style.display = 'none' }}
                  />
                  <span className="text-sm font-semibold truncate text-foreground">
                    {live.nickname}
                  </span>
                </div>

                {live.title && (
                  <p className="text-xs text-muted-foreground line-clamp-2 mb-1">
                    {live.title}
                  </p>
                )}

                <div className="flex items-center gap-1 text-xs text-muted-foreground">
                  <Users className="w-3 h-3" />
                  <span>{formatNumber(live.followers)} abonnés</span>
                </div>
              </div>
            </button>
          ))}
        </div>
      )}

      {/* Aucun résultat */}
      {!showSkeleton && !isError && suggestions && suggestions.length === 0 && (
        <p className="text-sm text-muted-foreground text-center py-4">
          Aucun live trouvé pour le moment.
        </p>
      )}
    </div>
  )
}