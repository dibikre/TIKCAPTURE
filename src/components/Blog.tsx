import { useState, useEffect } from 'react'
import {
  BookOpen,
  ArrowLeft,
  Calendar,
  RefreshCw,
  AlertCircle,
  Loader2,
  ArrowRight,
  Newspaper,
} from 'lucide-react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import { useSeoMeta } from '../hooks/Useseometa'
import { BASE_URL } from '../lib/constants'

// ─── Types ────────────────────────────────────────────────────────────────────

interface Article {
  id: number
  title: string
  slug: string
  excerpt: string
  content: string
  image_url: string
  created_at: string
  updated_at: string
}

interface ListResponse    { success: boolean; articles?: Article[]; error?: string }
interface ArticleResponse { success: boolean; article?: Article;  error?: string }

// ─── Config ───────────────────────────────────────────────────────────────────

const API_BASE = `${BASE_URL}/segment_page/api/blog-api.php`

function formatDate(str: string) {
  try {
    return new Date(str).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
  } catch { return str }
}

// ─── Article Detail ───────────────────────────────────────────────────────────

function ArticleDetail({ slug }: { slug: string }) {
  const navigate = useNavigate()
  const [article, setArticle] = useState<Article | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError]     = useState<string | null>(null)

  useSeoMeta(
    article
      ? {
          title:         `${article.title}`,
          description:   article.excerpt || `Lisez l'article "${article.title}" sur le blog TikCapture.`,
          canonical:     `${BASE_URL}/blog/${slug}`,
          ogTitle:       article.title,
          ogDescription: article.excerpt || `Lisez l'article "${article.title}" sur le blog TikCapture.`,
          ogImage:       article.image_url || `${BASE_URL}/og-image.png`,
        }
      : {
          title:       'Article – Blog TikCapture',
          description: 'Retrouvez nos guides, tutoriels et actualités pour maîtriser TikTok.',
          canonical:   `${BASE_URL}/blog/${slug}`,
        }
  )

  useEffect(() => {
  const timer = setTimeout(() => {
    setLoading(true)
    setError(null)
    fetch(`${API_BASE}?slug=${encodeURIComponent(slug)}`)
      .then((r) => r.json() as Promise<ArticleResponse>)
      .then((data) => {
        if (data.success && data.article) setArticle(data.article)
        else setError(data.error ?? 'Article introuvable')
      })
      .catch(() => setError("Impossible de charger l'article"))
      .finally(() => setLoading(false))
  }, 0)
  return () => clearTimeout(timer)
}, [slug])

  useEffect(() => { window.scrollTo({ top: 0, behavior: 'smooth' }) }, [slug])

  if (loading) return (
    <div className="flex flex-col items-center justify-center py-32 gap-4">
      <Loader2 className="w-8 h-8 text-[#FF0050] animate-spin" />
      <p className="text-sm text-muted-foreground">Chargement de l'article…</p>
    </div>
  )

  if (error || !article) return (
    <div className="max-w-2xl mx-auto text-center py-24">
      <AlertCircle className="w-12 h-12 text-red-400 mx-auto mb-4" />
      <h2 className="text-xl font-bold text-foreground mb-2">Article introuvable</h2>
      <p className="text-muted-foreground mb-6">{error}</p>
      <button
        onClick={() => navigate('/blog')}
        className="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#FF0050] text-white font-semibold text-sm hover:bg-[#e0004a] transition-all"
      >
        <ArrowLeft className="w-4 h-4" /> Retour au blog
      </button>
    </div>
  )

  return (
    <article className="max-w-2xl mx-auto animate-fade-in-up">
      <Link
        to="/blog"
        className="inline-flex items-center gap-2 text-sm text-[#FF0050] font-semibold mb-8 hover:underline"
      >
        <ArrowLeft className="w-4 h-4" /> Retour au blog
      </Link>

      <header className="mb-8">
        <h1 className="text-3xl sm:text-4xl md:text-5xl font-black text-foreground leading-tight mb-4">
          {article.title}
        </h1>
        <div className="flex items-center gap-3 text-sm text-muted-foreground">
          <span className="inline-flex items-center gap-1.5">
            <Calendar className="w-3.5 h-3.5" />
            {formatDate(article.created_at)}
          </span>
          {article.updated_at && article.updated_at > article.created_at && (
            <span className="inline-flex items-center gap-1.5">
              <RefreshCw className="w-3.5 h-3.5" />
              Mis à jour le {formatDate(article.updated_at)}
            </span>
          )}
        </div>
      </header>

      {article.image_url && (
        <div className="rounded-2xl overflow-hidden mb-8 border border-white/10">
          <img src={article.image_url} alt={article.title} className="w-full h-auto object-cover" />
        </div>
      )}

      <div className="article-body leading-relaxed" dangerouslySetInnerHTML={{ __html: article.content }} />
    </article>
  )
}

// ─── Article Card ─────────────────────────────────────────────────────────────

function ArticleCard({ article }: { article: Article }) {
  return (
    <Link
      to={`/blog/${article.slug}`}
      className="group text-left rounded-2xl glass border border-white/10 overflow-hidden hover:border-white/20 transition-all duration-300 hover:-translate-y-1"
    >
      {article.image_url ? (
        <div className="aspect-video overflow-hidden bg-white/5">
          <img
            src={article.image_url}
            alt={article.title}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          />
        </div>
      ) : (
        <div className="aspect-video bg-linear-to-br from-[#FF0050]/10 to-[#00F2EA]/10 flex items-center justify-center border-b border-white/5">
          <Newspaper className="w-10 h-10 text-white/20" />
        </div>
      )}

      <div className="p-5 space-y-3">
        <h2 className="font-bold text-foreground text-base leading-snug group-hover:text-[#FF0050] transition-colors line-clamp-2">
          {article.title}
        </h2>
        <p className="text-sm text-foreground leading-relaxed line-clamp-3">{article.excerpt}</p>
        <div className="flex items-center justify-between pt-1">
          <span className="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
            <Calendar className="w-3 h-3" />
            {formatDate(article.created_at)}
          </span>
          <span className="inline-flex items-center gap-1 text-xs text-[#FF0050] font-medium opacity-0 group-hover:opacity-100 transition-opacity">
            Lire <ArrowRight className="w-3 h-3" />
          </span>
        </div>
      </div>
    </Link>
  )
}

// ─── Blog List ────────────────────────────────────────────────────────────────

function BlogList() {
  const [articles, setArticles] = useState<Article[]>([])
  const [loading, setLoading]   = useState(true)
  const [error, setError]       = useState<string | null>(null)

  useSeoMeta({
    title:         'Blog TikCapture – Guides, tutoriels et actualités TikTok',
    description:   'Retrouvez nos derniers guides et tutoriels pour enregistrer des lives TikTok, télécharger des vidéos et maîtriser toutes les fonctionnalités de TikCapture.',
    canonical:     `${BASE_URL}/blog`,
    ogTitle:       'Blog TikCapture – Guides & Tutoriels TikTok',
    ogDescription: "Guides pratiques, tutoriels vidéo et actualités pour tout savoir sur l'enregistrement et le téléchargement de contenus TikTok.",
    ogImage:       `${BASE_URL}/og-image.png`,
  })

  const load = () => {
    setLoading(true)
    setError(null)
    fetch(API_BASE)
      .then((r) => r.json() as Promise<ListResponse>)
      .then((data) => {
        if (data.success && data.articles) setArticles(data.articles)
        else setError(data.error ?? 'Erreur lors du chargement')
      })
      .catch(() => setError('Impossible de contacter le serveur'))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
  const timer = setTimeout(() => { load() }, 0)
  return () => clearTimeout(timer)
}, [])

  return (
    <div className="space-y-12">
      <div className="text-center animate-fade-in">
        <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/10 mb-6">
          <BookOpen className="w-4 h-4 text-[#FF0050]" />
          <span className="text-sm text-muted-foreground">Guides · Tutoriels · Actualités</span>
        </div>
        <h1 className="text-4xl sm:text-5xl md:text-6xl font-black mb-4 tracking-tight">
          Blog <span className="gradient-text">TikCapture</span>
        </h1>
        <p className="text-base md:text-lg text-muted max-w-xl mx-auto leading-relaxed">
          Retrouvez nos derniers guides, tutoriels et actualités pour maîtriser l'enregistrement de lives TikTok.
        </p>
      </div>

      {loading && (
        <div className="flex flex-col items-center gap-4 py-24">
          <Loader2 className="w-8 h-8 text-[#FF0050] animate-spin" />
          <p className="text-sm text-muted-foreground">Chargement des articles…</p>
        </div>
      )}

      {error && !loading && (
        <div className="flex flex-col items-center gap-4 py-16">
          <div className="flex items-start gap-3 px-5 py-4 rounded-xl border border-red-500/30 bg-red-500/10 max-w-md">
            <AlertCircle className="w-5 h-5 text-red-400 shrink-0 mt-0.5" />
            <div>
              <p className="font-semibold text-foreground text-sm">Erreur de chargement</p>
              <p className="text-sm text-muted-foreground mt-0.5">{error}</p>
            </div>
          </div>
          <button
            onClick={load}
            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-full glass border border-white/15 text-sm font-medium hover:border-white/30 transition-all"
          >
            <RefreshCw className="w-4 h-4" /> Réessayer
          </button>
        </div>
      )}

      {!loading && !error && articles.length === 0 && (
        <div className="flex flex-col items-center gap-4 py-24 text-center">
          <div className="w-16 h-16 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center mb-2">
            <Newspaper className="w-7 h-7 text-muted-foreground" />
          </div>
          <h3 className="font-bold text-foreground">Aucun article publié</h3>
          <p className="text-sm text-muted-foreground max-w-xs">Les articles apparaîtront ici dès leur publication.</p>
        </div>
      )}

      {!loading && !error && articles.length > 0 && (
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-5 animate-fade-in-up">
          {articles.map((a) => (
            <ArticleCard key={a.id} article={a} />
          ))}
        </div>
      )}
    </div>
  )
}

// ─── Main Page ────────────────────────────────────────────────────────────────

export function Blog() {
  const { slug } = useParams<{ slug?: string }>()

  return (
    <main className="relative z-10 pt-24 md:pt-32 pb-20">
      <div className="mx-auto px-4 sm:px-6 w-full max-w-3xl">
        {slug ? <ArticleDetail slug={slug} /> : <BlogList />}
      </div>

      <style>{`
        .article-body { color: inherit; }
        .article-body h1, .article-body h2, .article-body h3,
        .article-body h4, .article-body h5, .article-body h6 {
          color: currentColor; font-weight: 700; margin: 1.75rem 0 0.75rem; line-height: 1.3;
        }
        .article-body h2 { font-size: 1.5rem; }
        .article-body h3 { font-size: 1.25rem; }
        .article-body h4 { font-size: 1.1rem; }
        .article-body p  { margin-bottom: 1.25rem; color: inherit; }
        .article-body ul, .article-body ol { margin-bottom: 1.25rem; padding-left: 1.5rem; color: inherit; }
        .article-body li { margin-bottom: 0.4rem; }
        .article-body a  { color: #FF0050; text-decoration: underline; }
        .article-body img { max-width: 100%; border-radius: 12px; margin: 1.5rem 0; }
        .article-body blockquote {
          border-left: 3px solid #FF0050; padding-left: 1rem;
          margin: 1.5rem 0; font-style: italic; opacity: 0.7;
        }
        .article-body strong { font-weight: 700; color: inherit; }
        .article-body em { font-style: italic; }
        .article-body code {
          background: rgba(128,128,128,0.15);
          padding: 0.15em 0.4em; border-radius: 4px; font-size: 0.875em;
        }
        .article-body pre {
          background: rgba(128,128,128,0.1);
          border: 1px solid rgba(128,128,128,0.2);
          border-radius: 10px; padding: 1rem; overflow-x: auto; margin-bottom: 1.25rem;
        }
        .article-body pre code { background: none; padding: 0; }
        .article-body table { width: 100%; border-collapse: collapse; margin-bottom: 1.25rem; }
        .article-body th, .article-body td {
          border: 1px solid rgba(128,128,128,0.25); padding: 0.5rem 0.75rem; text-align: left;
        }
        .article-body th { font-weight: 600; background: rgba(128,128,128,0.1); }
        .article-body hr { border: none; border-top: 1px solid rgba(128,128,128,0.2); margin: 2rem 0; }
      `}</style>
    </main>
  )
}