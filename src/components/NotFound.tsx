import { Link } from 'react-router-dom'
import { useSeoMeta } from '../hooks/Useseometa'
import { BASE_URL } from '../lib/constants'

export function NotFound() {
  useSeoMeta({
    title:       '404 – Page introuvable | TikCapture',
    description: 'La page que vous recherchez est introuvable. Retournez à l\'accueil de TikCapture.',
    canonical:   `${BASE_URL}/404`,
    ogTitle:     '404 – Page introuvable | TikCapture',
    ogDescription: 'La page que vous recherchez est introuvable.',
    ogImage:     `${BASE_URL}/og-image.png`,
  })

  return (
    <main className="relative z-10 pt-24 md:pt-32 pb-20">
      <div className="mx-auto px-4 text-center max-w-xl">
        <h1 className="text-8xl font-black gradient-text mb-4">404</h1>
        <p className="text-xl text-foreground mb-8">Page introuvable</p>
        <Link
          to="/"
          className="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-[#FF0050] text-white font-semibold hover:bg-[#e0004a] transition-all"
        >
          Retour à l'accueil
        </Link>
      </div>
    </main>
  )
}