import { useEffect, useState, useRef } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import type { Actor } from '../content/actors-content'
import { fetchActors } from '../lib/actors-api'
import { useSeoMeta } from '../hooks/Useseometa'
import { Search } from 'lucide-react'
import { BASE_URL } from '../lib/constants'

const SKELETON_CARDS = 10

export function Actors() {
  const [isLoading, setIsLoading]   = useState(true)  // premier chargement uniquement
  const [isFetching, setIsFetching] = useState(false)
  const [actors, setActors]       = useState<Actor[]>([])
  const [error, setError]         = useState<string | null>(null)
  const [query, setQuery]         = useState('')
  const [searchParams, setSearchParams] = useSearchParams()
  const [page, setPage] = useState(() => Number(searchParams.get('page')) || 1)
  const [totalPages, setTotalPages] = useState(1)
  const [total, setTotal]         = useState(0)
  const LIMIT = 24

  useSeoMeta({
    title:         'Créateurs Live – TikCapture',
    description:   'Découvrez les créateurs de lives sur TikTok, YouTube, Twitch, Kick, Bigo et plus. Regardez leurs enregistrements en replay.',
    canonical:     page > 1 ? `${BASE_URL}/createurs?page=${page}` : `${BASE_URL}/createurs`,
    ogTitle:       'Créateurs Live – TikCapture',
    ogDescription: 'Accédez aux profils et vidéos des meilleurs créateurs de lives sur toutes les plateformes.',
    ogImage:       `${BASE_URL}/images/og-image.jpg`,
    robots:        page > 1 ? 'noindex, follow' : 'index, follow',
  })

  const isFirstLoad = useRef(true)

  useEffect(() => {
    let isMounted = true
    ;(async () => {
      try {
        if (isFirstLoad.current) {
          setIsLoading(true)
        } else {
          setIsFetching(true)
        }
        const result = await fetchActors(page, LIMIT, query)
        if (!isMounted) return
        setActors(result.data)
        setTotal(result.total)
        setTotalPages(result.totalPages)
      } catch {
        if (!isMounted) return
        setError('Impossible de charger les créateurs.')
      } finally {
        if (isMounted) {
          isFirstLoad.current = false
          setIsLoading(false)
          setIsFetching(false)
        }
      }
    })()
    return () => { isMounted = false }
  }, [page, query])

  const topRef = useRef<HTMLElement>(null)
  const scrollToTop = () => topRef.current?.scrollIntoView({ behavior: 'instant' })
  const goTo = (p: number) => {
    setPage(p)
    setSearchParams(p > 1 ? { page: String(p) } : {})
    scrollToTop()
  }

  return (
    <main ref={topRef} className="relative z-10 pt-24 md:pt-28 pb-16">
      <section className="container mx-auto px-4 max-w-7xl">

        {/* Header compact */}
        <div className="mb-8">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 className="text-2xl md:text-3xl font-bold">
                Créateurs
                <span className="gradient-text"> Live</span>
              </h1>
              <p className="text-muted text-sm mt-1">
                {isLoading ? '…' : `${total} créateur${total > 1 ? 's' : ''} disponible${total > 1 ? 's' : ''}`}
              </p>
            </div>

            {/* Barre de recherche */}
            <div className="relative w-full sm:w-64">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" />
              <input
                type="text"
                placeholder="Rechercher un créateur…"
                value={query}
                onChange={e => { goTo(1); setQuery(e.target.value) }}
                className="w-full bg-white/5 border border-white/10 rounded-xl pl-9 pr-4 py-2 text-sm text-foreground placeholder:text-muted focus:outline-none focus:border-[#FF0050]/50 transition"
              />
            </div>
          </div>
        </div>

        {/* Grille */}
        {isLoading ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            {Array.from({ length: SKELETON_CARDS }).map((_, i) => (
              <div key={i} className="animate-pulse">
                <div className="aspect-square rounded-2xl bg-white/8" />
                <div className="mt-2 space-y-1.5 px-1">
                  <div className="h-3 w-3/4 rounded bg-white/8" />
                  <div className="h-3 w-1/2 rounded bg-white/8" />
                </div>
              </div>
            ))}
          </div>
        ) : error ? (
          <div className="glass rounded-2xl p-6 text-center text-muted">{error}</div>
        ) : actors.length === 0 ? (
          <div className="glass rounded-2xl p-10 text-center text-muted">
            Aucun créateur trouvé pour "{query}"
          </div>
        ) : (
          <>
            {isFetching && (
              <div className="flex justify-center mb-4">
                <div className="flex items-center gap-2 px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-sm text-muted">
                  <div className="w-3.5 h-3.5 rounded-full border-2 border-[#FF0050]/30 border-t-[#FF0050] animate-spin" />
                  Chargement…
                </div>
              </div>
            )}
            <div className={`grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 transition-opacity duration-200 ${isFetching ? 'opacity-50' : 'opacity-100'}`}>
              {actors.map((actor: Actor) => (
                <Link
                  to={`/createurs/${actor.id}`}
                  key={actor.id}
                  className="group flex flex-col"
                >
                  {/* Avatar carré */}
                  <div className="relative aspect-square rounded-2xl overflow-hidden bg-white/5 border border-white/8 group-hover:border-[#FF0050]/40 transition-all duration-300 group-hover:scale-[1.02]">
                    <div className="absolute inset-0 flex items-center justify-center bg-white/5">
                      <div className="w-6 h-6 rounded-full border-2 border-[#FF0050]/30 border-t-[#FF0050] animate-spin" />
                    </div>
                    <img
                      src={actor.avatar}
                      alt={actor.name}
                      className="w-full h-full object-cover"
                      loading="lazy"
                      referrerPolicy="no-referrer"
                      onLoad={e => (e.currentTarget.previousElementSibling as HTMLElement | null)?.remove()}
                    />
                    {/* Badge plateforme */}
                    <div className="absolute bottom-2 left-2">
                      <div className="flex items-center gap-1 bg-black/60 backdrop-blur-sm rounded-lg px-2 py-1">
                        <img
                          src={`/plateformes/${actor.platform}.png`}
                          alt={actor.platform}
                          loading="lazy"
                          referrerPolicy="no-referrer"
                          className="w-3.5 h-3.5 object-contain"
                          onError={(e) => {
                            (e.target as HTMLImageElement).src = `/plateformes/${actor.platform}.jpg`
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
                  </div>

                  {/* Infos */}
                  <div className="mt-2 px-0.5">
                    <p className="text-sm font-semibold text-foreground truncate group-hover:text-[#FF0050] transition-colors">
                      {actor.name}
                    </p>
                    <p className="text-xs text-muted capitalize">{actor.platform}</p>
                  </div>
                </Link>
              ))}
            </div>
            {totalPages > 1 && (() => {
              const delta = 2
              const range: number[] = []
              for (let i = Math.max(1, page - delta); i <= Math.min(totalPages, page + delta); i++) {
                range.push(i)
              }
              const showLeftDots  = range[0] > 2
              const showRightDots = range[range.length - 1] < totalPages - 1

              return (
                <div className="flex items-center justify-center gap-1.5 mt-10 flex-wrap">
                  {/* Précédent */}
                  <button
                    onClick={() => goTo(page - 1)}
                    disabled={page === 1}
                    className="flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-medium
                      bg-white/5 border border-white/10 text-muted
                      hover:bg-white/10 hover:text-foreground hover:border-white/20
                      disabled:opacity-30 disabled:cursor-not-allowed
                      transition-all duration-200"
                  >
                    ← Précédent
                  </button>

                  {/* Page 1 toujours visible */}
                  {range[0] > 1 && (
                    <button
                      onClick={() => goTo(1)}
                      className="w-10 h-10 rounded-xl text-sm font-medium bg-white/5 border border-white/10 text-muted hover:bg-white/10 hover:text-foreground transition-all duration-200"
                    >
                      1
                    </button>
                  )}

                  {/* Dots gauche */}
                  {showLeftDots && (
                    <span className="w-10 h-10 flex items-center justify-center text-muted/50 text-sm">…</span>
                  )}

                  {/* Pages du range */}
                  {range.map(p => (
                    <button
                      key={p}
                      onClick={() => goTo(p)}
                      className={`w-10 h-10 rounded-xl text-sm font-medium transition-all duration-200 border
                        ${p === page
                          ? 'bg-[#FF0050] border-[#FF0050] text-white shadow-lg shadow-[#FF0050]/25 scale-105'
                          : 'bg-white/5 border-white/10 text-muted hover:bg-white/10 hover:text-foreground hover:border-white/20'
                        }`}
                    >
                      {p}
                    </button>
                  ))}

                  {/* Dots droite */}
                  {showRightDots && (
                    <span className="w-10 h-10 flex items-center justify-center text-muted/50 text-sm">…</span>
                  )}

                  {/* Dernière page toujours visible */}
                  {range[range.length - 1] < totalPages && (
                    <button
                      onClick={() => goTo(totalPages)}
                      className="w-10 h-10 rounded-xl text-sm font-medium bg-white/5 border border-white/10 text-muted hover:bg-white/10 hover:text-foreground transition-all duration-200"
                    >
                      {totalPages}
                    </button>
                  )}

                  {/* Suivant */}
                  <button
                    onClick={() => goTo(page + 1)}
                    disabled={page === totalPages}
                    className="flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-medium
                      bg-white/5 border border-white/10 text-muted
                      hover:bg-white/10 hover:text-foreground hover:border-white/20
                      disabled:opacity-30 disabled:cursor-not-allowed
                      transition-all duration-200"
                  >
                    Suivant →
                  </button>
                </div>
              )
            })()}
          </>
        )}
      </section>
    </main>
  )
}