import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import type { Actor } from '../content/actors-content'
import { fetchActorById } from '../lib/actors-api'
import { useSeoMeta } from '../hooks/Useseometa'
import { CreatorStructuredData } from './StructuredData'
import { Play, Clock, ChevronLeft, ChevronRight } from 'lucide-react'
import { BASE_URL } from '../lib/constants'

const VIDEOS_PER_PAGE = 12

export function ActorDetails() {
  const { actorId } = useParams()
  const [isLoading, setIsLoading] = useState(true)
  const [page, setPage]   = useState(1)
  const [actor, setActor] = useState<Actor | null>(null)

  useSeoMeta({
    title:         actor ? `${actor.name} — Créateur Live` : 'Créateur – TikCapture',
    description:   actor
      ? `Regardez les lives enregistrés de ${actor.name} sur ${actor.platform}. ${actor.description || ''}`
      : 'Profil créateur sur TikCapture.',
    canonical:     actor ? `${BASE_URL}/createurs/${actor.id}` : `${BASE_URL}/createurs/${actorId}`,
    ogTitle:       actor ? `${actor.name} — Lives en replay` : 'Créateur – TikCapture',
    ogDescription: actor ? `${actor.totalVideos} vidéo(s) — ${actor.name} sur ${actor.platform}.` : '',
    ogImage:       actor?.avatar || `${BASE_URL}/images/og-image.jpg`,
  })

  useEffect(() => {
    let isMounted = true
    ;(async () => {
      if (!actorId) { setIsLoading(false); return }
      setIsLoading(true)
      try {
        const row = await fetchActorById(actorId)
        if (!isMounted) return
        setActor(row)
      } catch {
        if (!isMounted) return
        setActor(null)
      } finally {
        if (isMounted) {
          setIsLoading(false)
        }
      }
    })()
    return () => { isMounted = false }
  }, [actorId])

  const totalPages     = actor ? Math.max(1, Math.ceil(actor.videos.length / VIDEOS_PER_PAGE)) : 1
  const currentPage    = Math.min(page, totalPages)
  const paginatedVideos = actor
    ? actor.videos.slice((currentPage - 1) * VIDEOS_PER_PAGE, currentPage * VIDEOS_PER_PAGE)
    : []

  if (!isLoading && !actor) {
    return (
      <main className="relative z-10 pt-28 pb-14">
        <section className="container mx-auto px-4 max-w-4xl">
          <div className="glass rounded-2xl p-6 text-center">
            <h1 className="text-2xl font-bold">Créateur introuvable</h1>
            <Link to="/createurs" className="inline-flex mt-5 px-4 py-2 rounded-xl bg-[#FF0050] text-white font-semibold">
              Retour à la liste
            </Link>
          </div>
        </section>
      </main>
    )
  }

  const formatDuration = (duration: string): string => {
    const parts = duration.split(':').map(Number)
    if (parts.length === 3 && parts[0] > 0) return duration // déjà hh:mm:ss
    if (parts.length === 2) {
      const [m, s] = parts
      const totalSeconds = m * 60 + s
      if (totalSeconds >= 3600) {
        const h = Math.floor(totalSeconds / 3600)
        const min = Math.floor((totalSeconds % 3600) / 60)
        const sec = totalSeconds % 60
        return `${String(h).padStart(2, '0')}:${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`
      }
    }
    return duration
  }

  return (
    <main className="relative z-10 pt-28 pb-16">
      <div className="container mx-auto px-4 max-w-7xl">

        {isLoading ? (
          <div className="animate-pulse space-y-6">
            <div className="h-40 rounded-2xl bg-white/8" />
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
              {Array.from({ length: 6 }).map((_, i) => (
                <div key={i}>
                  <div className="aspect-video rounded-xl bg-white/8" />
                  <div className="h-3 mt-2 w-3/4 rounded bg-white/8" />
                </div>
              ))}
            </div>
          </div>
        ) : actor ? (
          <>
            {/* ── Profil compact horizontal ── */}
            <CreatorStructuredData actor={actor} />
            <div className="flex items-center gap-5 mb-8 p-5 glass rounded-2xl border border-white/8">
              {/* Avatar */}
              <div className="relative shrink-0">
                <img
                  src={actor.avatar}
                  alt={actor.name}
                  loading="lazy"
                  referrerPolicy="no-referrer"
                  className="w-20 h-20 md:w-24 md:h-24 rounded-2xl object-cover border-2 border-white/10"
                />
                <img
                  src={`/plateformes/${actor.platform}.png`}
                  alt={actor.platform}
                  loading="lazy"
                  referrerPolicy="no-referrer"
                  className="absolute -bottom-1.5 -right-1.5 w-6 h-6 object-contain bg-black rounded-full p-0.5 border border-white/20"
                  onError={(e) => {
                    (e.target as HTMLImageElement).src = `/plateformes/${actor.platform}.jpg`
                  }}
                />
              </div>

              {/* Infos */}
              <div className="flex-1 min-w-0">
                <div className="flex flex-wrap items-center gap-3 mb-1">
                  <h1 className="text-xl md:text-2xl font-bold truncate">{actor.name}</h1>
                  <span className="text-xs px-2 py-0.5 rounded-full bg-white/8 text-muted capitalize border border-white/10">
                    {actor.platform}
                  </span>
                </div>
                {actor.description && (
                  <p className="text-sm text-muted line-clamp-2">{actor.description}</p>
                )}
                <div className="flex items-center gap-4 mt-2">
                  <span className="text-sm text-muted">
                    <span className="text-foreground font-semibold">{actor.totalVideos}</span> vidéo{actor.totalVideos > 1 ? 's' : ''}
                  </span>
                  {actor.profileName && (
                    <span className="text-xs text-muted/60">@{actor.profileName}</span>
                  )}
                </div>
              </div>

              {/* Lien retour */}
              <Link
                to="/createurs"
                className="shrink-0 text-xs text-muted hover:text-[#FF0050] transition hidden sm:block"
              >
                ← Tous les créateurs
              </Link>
            </div>

            {/* ── Titre section ── */}
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-base font-semibold text-muted uppercase tracking-wider">
                Lives enregistrés
              </h2>
              <Link to="/createurs" className="text-xs text-[#FF0050] hover:underline sm:hidden">
                ← Retour
              </Link>
            </div>

            {/* ── Grille vidéos compacte ── */}
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
              {paginatedVideos.map((video) => {
                const spriteUrl = (video as { sprite_url?: string }).sprite_url || ''
                const slug = (video as { slug?: string }).slug || video.id
                const COLS = 6, ROWS = 6
                const totalFrames = COLS * ROWS
                return (
                  <Link
                    key={video.id}
                    to={`/createurs/${actor.id}/videos/${slug}`}
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
                      {spriteUrl && (
                        <div
                          className="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-150"
                          style={{
                            backgroundImage: `url(${spriteUrl})`,
                            backgroundSize: `${COLS * 100}% ${ROWS * 100}%`,
                            backgroundPosition: '0% 0%',
                            backgroundRepeat: 'no-repeat',
                            animation: 'none',
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
                            ;(el as HTMLElement & { _spriteInterval?: ReturnType<typeof setInterval> })._spriteInterval = interval
                          }}
                          onMouseLeave={(e) => {
                            const el = e.currentTarget as HTMLElement & { _spriteInterval?: ReturnType<typeof setInterval> }
                            clearInterval(el._spriteInterval)
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
                      <span className="flex items-center gap-1 text-xs text-muted">
                          <Clock className="w-3 h-3" />{formatDuration(video.duration)}
                        </span>
                    </div>
                    <div className="mt-1.5 px-0.5">
                      <p className="text-xs font-medium text-foreground line-clamp-2 leading-tight group-hover:text-[#FF0050] transition-colors">
                        {video.title}
                      </p>
                      <div className="flex items-center gap-2 mt-1">
                        <span className="flex items-center gap-1 text-xs text-muted">
                          <Clock className="w-3 h-3" />{video.duration}
                        </span>
                      </div>
                    </div>
                  </Link>
                )
              })}
            </div>

            {/* ── Pagination ── */}
            {totalPages > 1 && (
              <div className="flex items-center justify-center gap-2 mt-8">
                <button
                  onClick={() => setPage(p => Math.max(1, p - 1))}
                  disabled={currentPage === 1}
                  className="p-2 rounded-xl border border-white/10 disabled:opacity-30 hover:border-white/30 transition"
                >
                  <ChevronLeft className="w-4 h-4" />
                </button>
                <span className="text-sm text-muted px-3">
                  {currentPage} / {totalPages}
                </span>
                <button
                  onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                  disabled={currentPage === totalPages}
                  className="p-2 rounded-xl border border-white/10 disabled:opacity-30 hover:border-white/30 transition"
                >
                  <ChevronRight className="w-4 h-4" />
                </button>
              </div>
            )}

            {/* ── Bloc SEO textuel ── */}
            <div className="mt-16 space-y-6 border-t border-white/8 pt-10">
              <div className="max-w-3xl space-y-4">
                <h2 className="text-lg font-bold text-foreground">
                  Regardez les lives de {actor.name} en replay
                </h2>
                <p className="text-sm text-muted leading-relaxed">
                  {actor.name} est un créateur actif sur {actor.platform} avec {actor.totalVideos} live{Number(actor.totalVideos) > 1 ? 's' : ''} enregistré{Number(actor.totalVideos) > 1 ? 's' : ''} disponibles sur TikCapture.
                  {actor.description ? ` ${actor.description}` : ''}
                  {' '}Retrouvez ici l'ensemble de ses sessions en replay, capturées automatiquement en haute qualité sans watermark.
                </p>
                <p className="text-sm text-muted leading-relaxed">
                  TikCapture vous permet de regarder et d'enregistrer les lives {actor.platform} de {actor.name} même après leur diffusion.
                  Chaque vidéo est archivée avec sa durée complète, son aperçu en miniature et une transcription automatique quand elle est disponible.
                  Plus besoin de rester connecté pendant des heures — laissez TikCapture capturer à votre place.
                </p>
                <p className="text-sm text-muted leading-relaxed">
                  Vous pouvez parcourir les {actor.totalVideos} vidéo{Number(actor.totalVideos) > 1 ? 's' : ''} archivée{Number(actor.totalVideos) > 1 ? 's' : ''} de {actor.name} et accéder à la version complète de chaque live après inscription gratuite.
                  Les aperçus sont accessibles sans compte.
                </p>
              </div>

              <div className="flex flex-wrap gap-2 pt-2">
                {[
                  `Live ${actor.platform} ${actor.name}`,
                  `${actor.name} replay`,
                  `Enregistrement live ${actor.platform}`,
                  `VOD ${actor.name}`,
                  `${actor.name} TikCapture`,
                  `Live ${actor.platform} replay gratuit`,
                ].map(tag => (
                  <span
                    key={tag}
                    className="text-xs px-3 py-1 rounded-full bg-white/5 border border-white/10 text-muted"
                  >
                    {tag}
                  </span>
                ))}
              </div>
            </div>
          </>
        ) : null}
      </div>
    </main>
  )
}