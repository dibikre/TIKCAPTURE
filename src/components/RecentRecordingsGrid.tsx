import { useEffect, useState } from 'react'
import { 
  Clock, 
  Calendar, 
  Monitor, 
  Eye, 
  ArrowRight,
  ShieldCheck,
  Video
} from 'lucide-react'
import { BASE_URL } from '../lib/constants'
import { formatNumber } from '../lib/utils'

interface Recording {
  uniqueId: string
  nickname: string
  avatar: string
  title: string
  viewers: number
  startTime: string
  quality: string
  recordedAt: string
  timestamp: number
}

interface RecentRecordingsGridProps {
  onSelectUser: (username: string) => void
  refreshTrigger?: number | string
}

export function RecentRecordingsGrid({ onSelectUser, refreshTrigger }: RecentRecordingsGridProps) {
  const [recordings, setRecordings] = useState<Recording[]>([])
  const [isLoading, setIsLoading] = useState(true)

  const fetchRecordings = async () => {
    try {
      const res = await fetch(`${BASE_URL}/segment_page/api/recordings.php`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      
      const data = await res.json()
      const recordingsData = data.recordings || (Array.isArray(data) ? data : [])
      setRecordings(recordingsData.slice(0, 14))
    } catch (error) {
      console.error('Failed to fetch recordings:', error)
    } finally {
      setIsLoading(false)
    }
  }

  useEffect(() => {
    fetchRecordings()
  }, [refreshTrigger])

  // On affiche TOUJOURS le conteneur avec l'ID pour que le script de build/SSR puisse injecter le contenu PHP
  return (
    <div id="recent-recordings-ssr-area" className="mt-12 space-y-6 animate-fade-in px-4 sm:px-6 lg:px-8">
      {(recordings.length > 0 || isLoading) && (
        <div className="flex items-center justify-between px-2">
          <h2 className="text-xl font-bold flex items-center gap-2">
            <span className="w-8 h-8 rounded-lg bg-[#FF0050]/10 flex items-center justify-center text-[#FF0050]">
              <Video className="w-4 h-4" />
            </span>
            Enregistrements <span className="gradient-text">récents</span>
          </h2>
        </div>
      )}
      {isLoading ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 gap-3">
          {Array.from({ length: 14 }).map((_, i) => (
            <div key={i} className="animate-pulse">
               <div className="aspect-square rounded-2xl bg-white/8" />
               <div className="mt-2 space-y-1.5 px-1">
                 <div className="h-3 w-3/4 rounded bg-white/8" />
                 <div className="h-3 w-1/2 rounded bg-white/8" />
               </div>
            </div>
          ))}
        </div>
      ) : recordings.length > 0 ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 gap-3">
          {recordings.map((rec) => {
            const date = new Date(rec.recordedAt)
            const formattedDate = date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })
            const formattedTime = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
          
            const title = rec.title || `${rec.nickname} enregistré le ${formattedDate} à ${formattedTime}`
            const avatarUrl = rec.avatar.startsWith('/') ? `${BASE_URL}${rec.avatar}` : rec.avatar
            
            return (
              <button
                key={`${rec.uniqueId}-${rec.timestamp}`}
                onClick={() => onSelectUser(rec.uniqueId)}
                className="group flex flex-col text-left glass rounded-2xl overflow-hidden border border-white/10 hover:border-[#FF0050]/40 transition-all duration-300 hover:scale-[1.02]"
              >
                <div className="relative aspect-square overflow-hidden bg-white/5 border-b border-white/8">
                  <img
                    src={avatarUrl}
                    alt={rec.nickname}
                    className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                    referrerPolicy="no-referrer"
                    width={200}
                    height={200}
                    loading="lazy"
                  />
                  
                  {/* Badge plateforme */}
                  <div className="absolute bottom-2 left-2">
                    <div className="flex items-center gap-1 bg-black/60 backdrop-blur-sm rounded-lg px-2 py-1">
                      <img 
                        src={`${BASE_URL}/plateformes/tiktok.png`} 
                        alt="TikTok" 
                        className="w-3.5 h-3.5 object-contain"
                        width={14}
                        height={14}
                        loading="lazy"
                      />
                    </div>
                  </div>

                  {/* Badge New/Recording */}
                  <div className="absolute top-2 left-2">
                    <span className="bg-[#FF0050]/90 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full uppercase tracking-wide">
                      Récent
                    </span>
                  </div>
                </div>

                <div className="p-3 flex-1 flex flex-col space-y-2">
                  <div>
                    <h3 className="text-sm font-bold text-foreground truncate group-hover:text-[#FF0050] transition-colors leading-tight">
                      {rec.nickname}
                    </h3>
                    <p className="text-[11px] text-muted-foreground truncate">@{rec.uniqueId}</p>
                  </div>

                  <p className="text-[10px] text-muted leading-tight line-clamp-2 h-7 italic opacity-80">
                    "{title}"
                  </p>

                  <div className="grid grid-cols-2 gap-y-1.5 pt-2 border-t border-white/5">
                    <div className="flex items-center gap-1.5 min-w-0">
                      <Calendar className="w-3 h-3 text-[#00F2EA] shrink-0" />
                      <span className="text-[10px] text-muted-foreground truncate">{formattedDate}</span>
                    </div>
                    <div className="flex items-center gap-1.5 min-w-0">
                      <Clock className="w-3 h-3 text-[#00F2EA] shrink-0" />
                      <span className="text-[10px] text-muted-foreground truncate">{formattedTime}</span>
                    </div>
                    <div className="flex items-center gap-1.5 min-w-0">
                      <Eye className="w-3 h-3 text-[#FF0050] shrink-0" />
                      <span className="text-[10px] text-muted-foreground truncate">{formatNumber(rec.viewers)}</span>
                    </div>
                    <div className="flex items-center gap-1.5 min-w-0">
                      <Monitor className="w-3 h-3 text-[#FF0050] shrink-0" />
                      <div className="flex items-center gap-0.5 min-w-0">
                        <span className="text-[10px] text-muted-foreground truncate">{rec.quality || 'Auto'}</span>
                        {rec.quality?.includes('1080') && <ShieldCheck className="w-2.5 h-2.5 text-[#00F2EA]" />}
                      </div>
                    </div>
                  </div>

                  {rec.startTime && (
                    <div className="pt-1 flex items-center gap-1.5 bg-white/5 rounded-lg px-2 py-1">
                      <div className="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse" />
                      <span className="text-[9px] text-muted uppercase font-bold tracking-wider truncate">Début: {rec.startTime}</span>
                    </div>
                  )}

                  <div className="pt-1 flex justify-end">
                    <ArrowRight className="w-3.5 h-3.5 text-[#FF0050] transform translate-x-2 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all duration-300" />
                  </div>
                </div>
              </button>
            )
          })}
        </div>
      ) : null}
    </div>
  )
}
