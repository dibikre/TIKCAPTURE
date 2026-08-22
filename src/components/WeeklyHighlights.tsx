import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Play, Clock } from 'lucide-react'
import { BASE_URL } from '../lib/constants'

// ─── Types ────────────────────────────────────────────────────────────────────

interface WeeklyCreator {
  id: string
  name: string
  profileName: string
  platform: string
  avatar: string
  coverImage: string
  description: string
  totalVideos: number
}

interface WeeklyVideo {
  id: string
  creator_id: string
  title: string
  thumbnail: string
  sprite_url: string
  duration: string
  views: string
  creatorName: string
  platform: string
  creatorAvatar: string
  slug: string
}

interface WeeklyData {
  weekStart: string
  creators: WeeklyCreator[]
  videos: WeeklyVideo[]
}

// ─── Cache clé basée sur le lundi courant ─────────────────────────────────────

function getMondayKey(): string {
  const now = new Date()
  const day = now.getUTCDay() // 0=Dim … 6=Sam
  const diff = (day === 0 ? -6 : 1 - day) // décalage vers lundi
  const monday = new Date(now)
  monday.setUTCDate(now.getUTCDate() + diff)
  monday.setUTCHours(0, 0, 0, 0)
  return monday.toISOString().slice(0, 10) // "YYYY-MM-DD"
}

const CACHE_KEY = `weekly_highlights_${getMondayKey()}`
const API_URL   = `${BASE_URL}/api/weekly-highlights.php`

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatDuration(duration: string): string {
  const parts = duration.split(':').map(Number)
  if (parts.length === 3 && parts[0] > 0) return duration
  if (parts.length === 2) {
    const [m, s] = parts
    const total  = m * 60 + s
    if (total >= 3600) {
      const h   = Math.floor(total / 3600)
      const min = Math.floor((total % 3600) / 60)
      const sec = total % 60
      return `${String(h).padStart(2, '0')}:${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`
    }
  }
  return duration
}

// ─── Composant principal ──────────────────────────────────────────────────────

export function WeeklyHighlights() {
  const [data, setData]         = useState<WeeklyData | null>(null)
  const [isLoading, setLoading] = useState(true)

  useEffect(() => {
    // Lecture cache sessionStorage (valable jusqu'à fermeture du tab)
    try {
      const cached = sessionStorage.getItem(CACHE_KEY)
      if (cached) {
        setData(JSON.parse(cached))
        setLoading(false)
        return
      }
    } catch { /* ignore */ }

    let mounted = true
    ;(async () => {
      try {
        const res = await fetch(API_URL)
        if (!res.ok) throw new Error('API error')
        const json = await res.json()
        if (!mounted) return
        if (json.status === 'success') {
          setData(json)
          try { sessionStorage.setItem(CACHE_KEY, JSON.stringify(json)) } catch { /* ignore */ }
        }
      } catch {
        // silently fail — section simply won't show
      } finally {
        if (mounted) setLoading(false)
      }
    })()
    return () => { mounted = false }
  }, [])

  // Ne rien afficher si pas de données (erreur réseau ou semaine vide)
  if (!isLoading && (!data || (data.creators.length === 0 && data.videos.length === 0))) {
    return null
  }

  const COLS = 6, ROWS = 6
  const totalFrames = COLS * ROWS

  return (
    <div className="max-w-4xl mx-auto mt-20 space-y-14">

      {/* ── Créateurs récents ───────────────────────────────────────────────── */}
      {(isLoading || (data && data.creators.length > 0)) && (
        <section>
          <div className="flex items-center justify-between mb-5">
            <h2 className="text-xl font-bold text-foreground">
              Créateurs <span className="gradient-text">récents</span>
            </h2>
            <Link
              to="/createurs"
              className="text-xs text-[#FF0050] hover:underline"
            >
              Voir tous →
            </Link>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-3">
            {isLoading
              ? Array.from({ length: 8 }).map((_, i) => (
                  <div key={i} className="animate-pulse">
                    <div className="aspect-square rounded-2xl bg-white/8" />
                    <div className="mt-2 space-y-1.5 px-1">
                      <div className="h-3 w-3/4 rounded bg-white/8" />
                      <div className="h-3 w-1/2 rounded bg-white/8" />
                    </div>
                  </div>
                ))
              : data!.creators
                  .filter(actor => actor.platform === 'tiktok')
                  .map((actor) => (
                  <Link
                    to={`/createurs/${actor.id}`}
                    key={actor.id}
                    className="group flex flex-col"
                  >
                    <div className="relative aspect-square rounded-2xl overflow-hidden bg-white/5 border border-white/8 group-hover:border-[#FF0050]/40 transition-all duration-300 group-hover:scale-[1.02]">
                      <img
                        src={actor.avatar}
                        alt={actor.name}
                        className="w-full h-full object-cover"
                        loading="lazy"
                        referrerPolicy="no-referrer"
                        width={200}
                        height={200}
                      />
                      {/* Badge plateforme */}
                      <div className="absolute bottom-2 left-2">
                        <div className="flex items-center gap-1 bg-black/60 backdrop-blur-sm rounded-lg px-2 py-1">
                          <img
                            src={`${BASE_URL}/plateformes/${actor.platform}.png`}
                            alt={actor.platform}
                            className="w-3.5 h-3.5 object-contain"
                            loading="lazy"
                            referrerPolicy="no-referrer"
                            width={14}
                            height={14}
                            onError={(e) => {
                              (e.target as HTMLImageElement).src = `${BASE_URL}/plateformes/${actor.platform}.jpg`
                            }}
                          />
                        </div>
                      </div>
                      {/* Badge nb vidéos */}
                      <div className="absolute top-2 right-2">
                        <span className="bg-black/60 backdrop-blur-sm text-white text-xs px-2 py-0.5 rounded-full">
                          {actor.totalVideos}
                        </span>
                      </div>
                      {/* Badge NEW */}
                      <div className="absolute top-2 left-2">
                        <span className="bg-[#FF0050]/90 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full uppercase tracking-wide">
                          Nouveau
                        </span>
                      </div>
                    </div>
                    <div className="mt-2 px-0.5">
                      <p className="text-sm font-semibold text-foreground truncate group-hover:text-[#FF0050] transition-colors">
                        {actor.name}
                      </p>
                      <p className="text-xs text-muted capitalize">{actor.platform}</p>
                    </div>
                  </Link>
                ))
            }
          </div>
        </section>
      )}

      {/* ── Vidéos à découvrir ──────────────────────────────────────────────── */}
      {(isLoading || (data && data.videos.length > 0)) && (
        <section>
          <div className="flex items-center justify-between mb-5">
            <h2 className="text-xl font-bold text-foreground">
              Des vidéos à <span className="gradient-text">découvrir</span>
            </h2>
            <Link
              to="/createurs"
              className="text-xs text-[#FF0050] hover:underline"
            >
              Voir plus →
            </Link>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
            {isLoading
              ? Array.from({ length: 6 }).map((_, i) => (
                  <div key={i} className="animate-pulse">
                    <div className="aspect-video rounded-xl bg-white/8" />
                    <div className="h-3 mt-2 w-3/4 rounded bg-white/8" />
                    <div className="h-3 mt-1 w-1/2 rounded bg-white/8" />
                  </div>
                ))
              : data!.videos
                  .filter(video => video.platform === 'tiktok')
                  .map((video) => (
                  <Link
                    key={video.id}
                    to={`/createurs/${video.creator_id}/videos/${video.slug}`}
                    className="group"
                  >
                    <div className="relative aspect-video rounded-xl overflow-hidden bg-white/5 border border-white/8 group-hover:border-[#FF0050]/40 transition-all duration-200 group-hover:scale-[1.02]">
                      {/* Thumbnail statique */}
                      <img
                        src={video.thumbnail}
                        alt={video.title}
                        className="w-full h-full object-cover group-hover:opacity-0 transition-opacity duration-150"
                        loading="lazy"
                        referrerPolicy="no-referrer"
                      />

                      {/* Sprite animé au hover */}
                      {video.sprite_url && (
                        <div
                          className="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-150"
                          style={{
                            backgroundImage: `url(${video.sprite_url})`,
                            backgroundSize: `${COLS * 100}% ${ROWS * 100}%`,
                            backgroundPosition: '0% 0%',
                            backgroundRepeat: 'no-repeat',
                          }}
                          onMouseEnter={(e) => {
                            let frame = 0
                            const el = e.currentTarget
                            const interval = setInterval(() => {
                              frame = (frame + 1) % totalFrames
                              const col = frame % COLS
                              const row = Math.floor(frame / COLS)
                              const x = col * (100 / (COLS - 1))
                              const y = row * (100 / (ROWS - 1))
                              el.style.backgroundPosition = `${x}% ${y}%`
                            }, 800)
                            ;(el as HTMLElement & { _si?: ReturnType<typeof setInterval> })._si = interval
                          }}
                          onMouseLeave={(e) => {
                            const el = e.currentTarget as HTMLElement & { _si?: ReturnType<typeof setInterval> }
                            clearInterval(el._si)
                            el.style.backgroundPosition = '0% 0%'
                          }}
                        />
                      )}

                      {/* Overlay play */}
                      <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center pointer-events-none">
                        <div className="opacity-0 group-hover:opacity-100 transition transform scale-75 group-hover:scale-100">
                          <div className="w-10 h-10 rounded-full bg-[#FF0050]/90 flex items-center justify-center">
                            <Play className="w-4 h-4 text-white fill-white ml-0.5" />
                          </div>
                        </div>
                      </div>

                      {/* Durée */}
                      <div className="absolute bottom-2 right-2 flex items-center gap-1 bg-black/60 backdrop-blur-sm text-white text-xs px-2 py-0.5 rounded-full">
                        <Clock className="w-3 h-3" />
                        {formatDuration(video.duration)}
                      </div>
                    </div>

                    {/* Infos sous la vignette */}
                    <div className="mt-2 px-0.5 flex items-center gap-2">
                      <img
                        src={video.creatorAvatar}
                        alt={video.creatorName}
                        className="w-6 h-6 rounded-full object-cover shrink-0 border border-white/10"
                        loading="lazy"
                        referrerPolicy="no-referrer"
                      />
                      <div className="min-w-0">
                        <p className="text-xs font-medium text-foreground line-clamp-2 leading-tight group-hover:text-[#FF0050] transition-colors">
                          {video.title}
                        </p>
                        <p className="text-[11px] text-muted capitalize truncate">{video.creatorName}</p>
                      </div>
                    </div>
                  </Link>
                ))
            }
          </div>
        </section>
      )}
    </div>
  )
}