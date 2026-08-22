import { useEffect } from 'react'
import { useLiveStore } from '../stores/liveStore'
import { formatNumber } from '../lib/utils'
import { BadgeCheck, Users, UserPlus, Eye, Radio } from 'lucide-react'
import { QualitySelector } from './QualitySelector'
import { DurationPicker } from './DurationPicker'
import { RecordButton } from './RecordButton'
import { RecordingProgress } from './RecordingProgress'
import { LiveSuggestions } from './Livesuggestions'

interface LiveDetailsProps {
  onSearch: (username: string) => void
  notFoundUsername?: string | null
}

export function LiveDetails({ onSearch, notFoundUsername }: LiveDetailsProps) {
  const { searchResult, selectedQuality, setSelectedQuality } = useLiveStore()

  useEffect(() => {
    if (searchResult && !selectedQuality) {
      const qualities = Object.keys(searchResult.streams)
      if (qualities.length > 0) setSelectedQuality(qualities[0])
    }
  }, [searchResult, selectedQuality, setSelectedQuality])

  // Cas utilisateur introuvable
  if (!searchResult && notFoundUsername) {
    return (
      <div className="w-full max-w-4xl mx-auto mt-8 animate-fade-in-up">
        <div className="glass rounded-3xl p-8 mb-6 border border-red-500/20">
          <div className="flex items-center gap-3 mb-4">
            <div className="w-10 h-10 rounded-full bg-red-500/10 flex items-center justify-center text-red-500 text-lg leading-none">⚠️</div>
            <div>
              <h3 className="text-lg font-semibold">Utilisateur introuvable</h3>
              <p className="text-sm text-muted-foreground">
                <strong className="text-foreground">@{notFoundUsername}</strong> n'existe pas sur TikTok ou le nom est incorrect. Pour une meilleure solution, allez sur le live en cours de ce créateur et copiez le lien sous la forme https://www.tiktok.com/@nom_profil/live  .
              </p>
            </div>
          </div>
          <LiveSuggestions onSelectUser={onSearch} keyword={notFoundUsername} />
        </div>
      </div>
    )
  }

  if (!searchResult) return null

  const { user, stats, live } = searchResult

  return (
    <div className="w-full max-w-4xl mx-auto mt-8 animate-fade-in-up">
      {/* Profile Card */}
      <div className="glass rounded-3xl p-6 md:p-8 mb-6">
        <div className="flex flex-col md:flex-row gap-6 items-start">
          {/* Avatar with live indicator */}
          <div className="relative shrink-0">
            <div className="w-24 h-24 md:w-32 md:h-32 rounded-2xl overflow-hidden ring-2 ring-border">
              <img
                src={user.avatar}
                alt={user.nickname}
                className="w-full h-full object-cover"
                loading="lazy"
                referrerPolicy="no-referrer"
                onError={(e) => {
                  (e.target as HTMLImageElement).src = 'https://via.placeholder.com/128'
                }}
              />
            </div>
            {live.isLive && (
              <div className="absolute -bottom-2 -right-2 flex items-center gap-1.5 px-3 py-1.5 bg-red-500 rounded-full text-white text-xs font-bold animate-pulse">
                <Radio className="w-3 h-3" />
                LIVE
              </div>
            )}
          </div>

          {/* Info */}
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-3 mb-2">
              <h2 className="text-2xl md:text-3xl font-bold truncate">
                {user.nickname}
              </h2>
              {user.verified && (
                <BadgeCheck className="w-6 h-6 text-[#00F2EA] shrink-0" />
              )}
            </div>

            <p className="text-lg text-muted-foreground mb-2">
              @{user.uniqueId}
            </p>

            {user.bio && (
              <p className="text-sm text-muted-foreground line-clamp-2 mb-4">
                {user.bio}
              </p>
            )}

            {/* Stats */}
            <div className="flex flex-wrap gap-6">
              <div className="flex items-center gap-2">
                <UserPlus className="w-5 h-5 text-muted-foreground" />
                <div>
                  <p className="font-semibold">{formatNumber(stats.followers)}</p>
                  <p className="text-xs text-muted-foreground">abonnés</p>
                </div>
              </div>

              <div className="flex items-center gap-2">
                <Users className="w-5 h-5 text-muted-foreground" />
                <div>
                  <p className="font-semibold">{formatNumber(stats.following)}</p>
                  <p className="text-xs text-muted-foreground">abonnements</p>
                </div>
              </div>

              {live.isLive && (
                <div className="flex items-center gap-2">
                  <Eye className="w-5 h-5 text-[#FF0050]" />
                  <div>
                    <p className="font-semibold text-[#FF0050]">
                      {formatNumber(Math.max(live.viewers - 1, 0))}
                    </p>
                    <p className="text-xs text-muted-foreground">spectateurs</p>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Live Info */}
        {live.isLive && (
          <div className="mt-6 pt-6 border-t border-border">
            <div className="flex items-center gap-2 mb-2">
              <span className="w-2 h-2 rounded-full bg-red-500 animate-pulse" />
              <span className="text-sm font-medium text-red-500">EN DIRECT</span>
            </div>
            <h3 className="text-lg font-semibold mb-1">{live.title || 'Sans titre'}</h3>
            <p className="text-sm text-muted-foreground">Depuis {live.startTime}</p>
          </div>
        )}
      </div>

      {/* Stream Options — only if live */}
      {live.isLive && (
        <div className="glass rounded-3xl p-6 md:p-8 space-y-6">
          <h3 className="text-xl font-bold flex items-center gap-2">
            <span className="w-8 h-8 rounded-lg bg-[#FF0050]/10 flex items-center justify-center text-[#FF0050]">
              ⚙️
            </span>
            Configuration de l'enregistrement
          </h3>

          <QualitySelector />
          <DurationPicker />
          <RecordButton />
          <RecordingProgress />

          {/* ← Suggestions de lives affichées après l'arrêt */}
          <LiveSuggestions onSelectUser={onSearch} />
        </div>
      )}

      {/* Offline message */}
      {!live.isLive && (
        <div className="glass rounded-3xl p-8 text-center">
          <div className="w-16 h-16 rounded-full bg-muted/20 flex items-center justify-center mx-auto mb-4">
            <Radio className="w-8 h-8 text-muted-foreground" />
          </div>
          <h3 className="text-xl font-semibold mb-2">Hors ligne</h3>
          <p className="text-muted-foreground">
            @{user.uniqueId} n'est pas en live actuellement.
            <br />
            Revenez plus tard ou recherchez un autre créateur.
          </p>
        </div>
      )}
    </div>
  )
}