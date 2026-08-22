import { useEffect, useState } from 'react'
import { Link, useParams, useNavigate } from 'react-router-dom'
import type { ActorVideoPageData } from '../content/actors-content'
import { fetchActorVideoPage } from '../lib/actors-api'
import { useSeoMeta } from '../hooks/Useseometa'
import { VideoStructuredData } from './StructuredData'
import { Play, Clock, ChevronLeft, Tv } from 'lucide-react'
import { BASE_URL } from '../lib/constants'


export function ActorVideoDetails() {
  const { actorId, videoId } = useParams()
  const [isLoading, setIsLoading] = useState(true)
  const [payload, setPayload]     = useState<ActorVideoPageData | null>(null)
  const [showFullTranscript, setShowFullTranscript] = useState(false)
  const [useEmbed,  setUseEmbed]  = useState(false)

  const videoSlug = (payload?.video as { slug?: string })?.slug || videoId

  useSeoMeta({
    title:         payload ? `${payload.video.title} — ${payload.actor.name}` : 'Vidéo Live – TikCapture',
    description:   payload
      ? `Replay HD 2026 : Live de ${payload.actor.name} sur ${payload.actor.platform} (${payload.video.duration}). ${payload.video.description || 'Retrouvez l\'archive complète et sécurisée de ce live TikTok sur TikCapture.'}`
      : 'Archive vidéo live TikTok en replay HD sur TikCapture — Édition 2026.',
    canonical:     `${BASE_URL}/createurs/${actorId}/videos/${videoSlug}`,
    ogTitle:       payload ? payload.video.title : 'Vidéo Live – TikCapture',
    ogDescription: payload ? `${payload.actor.name} sur ${payload.actor.platform} — ${payload.video.duration}` : '',
    ogImage:       payload?.video.thumbnail || `${BASE_URL}/images/og-image.jpg`,
  })

  useEffect(() => {
    let isMounted = true
    ;(async () => {
      if (!actorId || !videoId) { setIsLoading(false); return }
      setIsLoading(true)
      try {
        const data = await fetchActorVideoPage(actorId, videoId)
        if (!isMounted) return
        setPayload(data)
      } catch {
        if (!isMounted) return
        setPayload(null)
      } finally {
        if (isMounted) {
          setIsLoading(false)
        }
      }
    })()
    return () => { isMounted = false }
  }, [actorId, videoId])

  useEffect(() => { setUseEmbed(false) }, [videoId])

  const handleEmbedClick = () => {
    setUseEmbed(true)
  }

  const navigate = useNavigate()

  if (!isLoading && !payload) {
    return (
      <main className="relative z-10 pt-28 pb-14">
        <section className="container mx-auto px-4 max-w-4xl">
          <div className="glass rounded-2xl p-6 text-center">
            <h1 className="text-2xl font-bold">Vidéo introuvable</h1>
            <Link to="/createurs" className="inline-flex mt-5 px-4 py-2 rounded-xl bg-[#FF0050] text-white font-semibold">
              Retour aux créateurs
            </Link>
          </div>
        </section>
      </main>
    )
  }

  const embedUrl = (payload?.video as { doodstream_embed?: string })?.doodstream_embed || ''

  return (
    <>
      <main className="relative z-10 pt-28 md:pt-32 pb-16">
        <div className="container mx-auto px-4 max-w-7xl">

          {isLoading ? (
            <div className="animate-pulse space-y-4">
              <div className="h-8 w-48 rounded bg-white/8" />
              <div className="grid lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-4">
                  <div className="aspect-video rounded-2xl bg-white/8" />
                  <div className="h-6 w-2/3 rounded bg-white/8" />
                </div>
                <div className="space-y-3">
                  {Array.from({ length: 4 }).map((_, i) => (
                    <div key={i} className="flex gap-3">
                      <div className="w-24 aspect-video rounded-lg bg-white/8 shrink-0" />
                      <div className="flex-1 space-y-2">
                        <div className="h-3 rounded bg-white/8" />
                        <div className="h-3 w-2/3 rounded bg-white/8" />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          ) : payload ? (
            <>
              {/* Breadcrumb */}
              <div className="flex items-center gap-2 text-sm text-muted mb-4">
                <Link to="/createurs" className="hover:text-[#FF0050] transition">Créateurs</Link>
                <span>/</span>
                <Link to={`/createurs/${payload.actor.id}`} className="hover:text-[#FF0050] transition truncate max-w-30">
                  {payload.actor.name}
                </Link>
                <span>/</span>
                <span className="text-foreground truncate max-w-50">{payload.video.title}</span>
              </div>

              <VideoStructuredData video={payload.video} actor={payload.actor} />

              <div className="grid lg:grid-cols-3 gap-6">

                {/* ── Colonne principale ── */}
                <div className="lg:col-span-2 space-y-4">

                  {/* Titre au-dessus du player */}
                  <h1 className="text-xl md:text-2xl font-display font-black leading-tight uppercase mb-2">{payload.video.title}</h1>

                  {/* Player */}
                  <div className="rounded-2xl overflow-hidden bg-black border border-white/8">
                    {useEmbed && embedUrl ? (
                      <iframe
                        src={embedUrl}
                        className="w-full aspect-video"
                        allowFullScreen
                        allow="autoplay; fullscreen"
                        frameBorder="0"
                      />
                    ) : (
                      <video
                        controls
                        className="w-full aspect-video"
                        poster={payload.video.thumbnail}
                      >
                        <source src={payload.video.videoUrl} type="video/mp4" />
                      </video>
                    )}
                  </div>
                  {/* Bouton switcher demo ↔ embed + vérifier live */}
                  <div className="flex items-center gap-3 flex-wrap">
                    {payload.actor.profileName && (
                      <button
                        onClick={() => navigate(`/?u=${encodeURIComponent(payload.actor.profileName)}`)}
                        className="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-[#00F2EA]/30 bg-[#00F2EA]/5 hover:bg-[#00F2EA]/10 text-[#00F2EA] text-sm font-semibold transition"
                      >
                        🔴 Vérifier si @{payload.actor.profileName} est en live
                      </button>
                    )}
                    {!useEmbed ? (
                      <button
                        onClick={handleEmbedClick}
                        className="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-[#FF0050]/30 bg-[#FF0050]/5 hover:bg-[#FF0050]/10 text-[#FF0050] text-sm font-semibold transition"
                      >
                        <Tv className="w-4 h-4" />
                        Regarder la version complète
                      </button>
                    ) : (
                      <button
                        onClick={() => setUseEmbed(false)}
                        className="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 text-muted text-sm font-medium transition"
                      >
                        <Play className="w-4 h-4" />
                        Revenir à la démo
                      </button>
                    )}
                  </div>

                  {/* Infos vidéo */}
                  <div className="space-y-3">
                    {payload.video.description && (
                      <p className="text-sm text-muted leading-relaxed">{payload.video.description}</p>
                    )}

                    <div className="flex flex-wrap items-center gap-4 text-sm text-muted">
                      <span className="flex items-center gap-1.5"><Clock className="w-4 h-4" />{payload.video.duration}</span>
                    </div>

                    <Link
                      to={`/createurs/${payload.actor.id}`}
                      className="flex items-center gap-3 p-3 glass rounded-xl border border-white/8 hover:border-[#FF0050]/30 transition w-fit"
                    >
                      <img src={payload.actor.avatar} alt={payload.actor.name} className="w-9 h-9 rounded-xl object-cover" />
                      <div>
                        <p className="text-sm font-semibold">{payload.actor.name}</p>
                        <div className="flex items-center gap-1.5">
                          <img
                            src={`/plateformes/${payload.actor.platform}.png`}
                            alt={payload.actor.platform}
                            className="w-3.5 h-3.5 object-contain"
                            onError={(e) => { (e.target as HTMLImageElement).src = `/plateformes/${payload.actor.platform}.jpg` }}
                          />
                          <span className="text-xs text-muted capitalize">{payload.actor.platform}</span>
                        </div>
                        {payload.actor.profileName && (
                          <span className="text-xs text-muted/60">@{payload.actor.profileName}</span>
                        )}
                      </div>
                      <ChevronLeft className="w-4 h-4 text-muted ml-2 rotate-180" />
                    </Link>
                  </div>

                  {/* Sprite */}
                  {payload.sprite?.imageUrl && (
                    <div className="glass rounded-2xl p-4 border border-white/8 space-y-3">
                      <h2 className="text-sm font-semibold text-muted uppercase tracking-wider">Aperçu vidéo</h2>
                      <div
                        className="grid gap-0.5 rounded-xl overflow-hidden"
                        style={{ gridTemplateColumns: `repeat(${payload.sprite.columns}, minmax(0, 1fr))` }}
                      >
                        {Array.from({ length: payload.sprite.columns * payload.sprite.rows }).map((_, idx) => {
                          const col = idx % payload.sprite.columns
                          const row = Math.floor(idx / payload.sprite.columns)
                          return (
                            <div
                              key={idx}
                              className="aspect-video"
                              style={{
                                backgroundImage:    `url(${payload.sprite.imageUrl})`,
                                backgroundSize:     `${payload.sprite.columns * 100}% ${payload.sprite.rows * 100}%`,
                                backgroundPosition: `${col * (100 / (payload.sprite.columns - 1))}% ${row * (100 / (payload.sprite.rows - 1))}%`,
                                backgroundRepeat:   'no-repeat',
                              }}
                            />
                          )
                        })}
                      </div>
                    </div>
                  )}

                  {/* Transcript */}
                  {payload.video.transcript && (
                    <div className="glass rounded-2xl p-4 border border-white/8 space-y-3">
                      <div className="flex items-center justify-between">
                        <h2 className="text-sm font-semibold text-muted uppercase tracking-wider">Transcript</h2>
                        <button onClick={() => setShowFullTranscript(v => !v)} className="text-xs text-[#FF0050] hover:underline">
                          {showFullTranscript ? 'Réduire' : 'Voir tout'}
                        </button>
                      </div>
                      <div className={`space-y-1 overflow-hidden transition-all ${showFullTranscript ? '' : 'max-h-48'}`}>
                        {payload.video.transcript.split('\n').filter(Boolean).map((line, i) => (
                          <p key={i} className="text-xs leading-relaxed text-muted font-mono">{line}</p>
                        ))}
                      </div>
                      {!showFullTranscript && (
                        <div className="h-8 bg-linear-to-t from-[#1E2330] to-transparent -mt-8 relative pointer-events-none" />
                      )}
                    </div>
                  )}

                  {/* ── SEO Text Content 2026 ── */}
                  <div className="glass rounded-2xl p-6 border border-white/8 space-y-6">
                    <section className="space-y-4">
                      <h2 className="text-xl font-bold text-foreground">
                        Pourquoi regarder les replays TikTok Live sur TikCapture en 2026 ?
                      </h2>
                      <p className="text-sm text-muted leading-relaxed">
                        En 2026, l'économie des lives a atteint son apogée. Des créateurs comme <strong>{payload.actor.name}</strong> 
                        repoussent sans cesse les limites du divertissement en direct. Cependant, avec l'abondance de contenu, 
                        il est impossible d'être présent à chaque diffusion. C'est là que TikCapture intervient, en offrant 
                        un accès exclusif aux archives de vos lives préférés sur {payload.actor.platform}.
                      </p>
                    </section>

                    <section className="grid sm:grid-cols-2 gap-6">
                      <div className="space-y-2">
                        <h3 className="text-sm font-semibold text-[#FF0050] uppercase tracking-wider">Qualité HD Native</h3>
                        <p className="text-xs text-muted leading-relaxed">
                          Contrairement aux simples captures d'écran, TikCapture enregistre les flux originaux. 
                          En 2026, la fidélité visuelle est primordiale pour apprécier chaque détail des performances en direct.
                        </p>
                      </div>
                      <div className="space-y-2">
                        <h3 className="text-sm font-semibold text-[#00F2EA] uppercase tracking-wider">Accessibilité Illimitée</h3>
                        <p className="text-xs text-muted leading-relaxed">
                          Regardez vos replays n'importe où, n'importe quand. Notre plateforme est optimisée pour tous les 
                          appareils modernes, garantissant une lecture fluide sans latence.
                        </p>
                      </div>
                    </section>

                    <div className="pt-4 border-t border-white/5">
                      <h3 className="text-sm font-bold text-foreground mb-3">Questions sur {payload.actor.name}</h3>
                      <div className="space-y-4">
                        <div className="space-y-1">
                          <p className="text-xs font-semibold text-foreground">Où trouver les anciens lives de {payload.actor.name} ?</p>
                          <p className="text-xs text-muted">Sur TikCapture, nous indexons et archivons automatiquement les diffusions marquantes pour vous permettre de ne rien rater.</p>
                        </div>
                        <div className="space-y-1">
                          <p className="text-xs font-semibold text-foreground">Comment enregistrer un live en 2026 ?</p>
                          <p className="text-xs text-muted">Avec notre outil, il suffit d'entrer l'URL ou le nom du créateur. Notre système s'occupe du reste, sécurisant le contenu sur nos serveurs de haute performance.</p>
                        </div>
                      </div>
                    </div>

                    <p className="text-[10px] text-muted/60 italic text-center">
                      TikCapture n'est pas affilié à TikTok. Nous prônons un usage responsable des contenus archivés.
                    </p>
                  </div>
                </div>

                {/* ── Sidebar ── */}
                <div className="space-y-5">
                  {payload.relatedVideos?.length > 0 && (
                    <div className="space-y-3">
                      <h2 className="text-sm font-semibold text-muted uppercase tracking-wider">Vidéos suggérées</h2>
                      <div className="space-y-2">
                        {payload.relatedVideos.map((entry) => (
                          <Link key={entry.video.id} to={`/createurs/${entry.actorId}/videos/${(entry.video as { slug?: string }).slug || entry.video.id}`} className="group flex gap-3 p-2 rounded-xl hover:bg-white/5 transition">
                            <div className="relative w-28 shrink-0 aspect-video rounded-lg overflow-hidden bg-white/5">
                              <img src={entry.video.thumbnail} alt={entry.video.title} className="w-full h-full object-cover" loading="lazy" />
                              <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition bg-black/30">
                                <Play className="w-4 h-4 text-white fill-white" />
                              </div>
                            </div>
                            <div className="flex-1 min-w-0">
                              <p className="text-xs font-medium line-clamp-2 leading-snug group-hover:text-[#FF0050] transition-colors">{entry.video.title}</p>
                              <p className="text-xs text-muted mt-1">{entry.actorName}</p>
                              <p className="text-xs text-muted">{entry.video.duration}</p>
                            </div>
                          </Link>
                        ))}
                      </div>
                    </div>
                  )}

                  {payload.relatedCreators?.length > 0 && (
                    <div className="space-y-3">
                      <h2 className="text-sm font-semibold text-muted uppercase tracking-wider">Créateurs suggérés</h2>
                      <div className="space-y-2">
                        {payload.relatedCreators.map((creator) => (
                          <Link key={creator.id} to={`/createurs/${creator.id}`} className="flex items-center gap-3 p-2 rounded-xl hover:bg-white/5 transition group">
                            <img src={creator.avatar} alt={creator.name} className="w-10 h-10 rounded-xl object-cover shrink-0" loading="lazy" />
                            <div className="min-w-0">
                              <p className="text-sm font-medium truncate group-hover:text-[#FF0050] transition-colors">{creator.name}</p>
                              <p className="text-xs text-muted">{creator.totalVideos} vidéo{Number(creator.totalVideos) > 1 ? 's' : ''}</p>
                            </div>
                          </Link>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </>
          ) : null}
        </div>
      </main>
    </>
  )
}