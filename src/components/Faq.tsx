import { useState } from 'react'
import { HelpCircle, ChevronDown, ArrowRight } from 'lucide-react'
import { Link } from 'react-router-dom'
import { cn } from '../lib/utils'
import { useSeoMeta } from '../hooks/Useseometa'
import { BASE_URL } from '../lib/constants'

const FAQS = [
  {
    category: 'Général',
    items: [
      { q: 'TikCapture est-il gratuit ?', a: "Oui, TikCapture est 100 % gratuit. Aucun abonnement, aucune carte bancaire, aucune limite d'utilisation." },
      { q: 'Dois-je créer un compte ?', a: "Non. Aucune inscription n'est requise. Utilisez TikCapture directement depuis votre navigateur." },
      { q: 'TikCapture fonctionne-t-il sur mobile ?', a: "Oui, TikCapture est entièrement responsive et fonctionne sur iOS et Android depuis votre navigateur mobile." },
    ],
  },
  {
    category: 'Enregistrement de Lives',
    items: [
      { q: "Comment enregistrer un live TikTok ?", a: "Entrez le nom d'utilisateur TikTok dans la barre de recherche, vérifiez que le live est actif, choisissez la qualité et cliquez sur Enregistrer. Tout se passe dans votre navigateur." },
      { q: "L'utilisateur TikTok sait-il que j'enregistre ?", a: "Non. TikCapture lit le flux public du live comme n'importe quel spectateur. Aucune notification n'est envoyée à l'utilisateur." },
      { q: "Puis-je enregistrer plusieurs lives en simultané ?", a: "Oui, ouvrez TikCapture dans plusieurs onglets pour enregistrer plusieurs lives en même temps." },
      { q: "Quelle est la qualité d'enregistrement ?", a: "TikCapture propose plusieurs qualités : de 360p à la HD native selon le flux disponible. La qualité 'Meilleure' utilise le flux HLS natif de TikTok." },
      { q: "Y a-t-il une limite de durée d'enregistrement ?", a: "Non. Vous pouvez enregistrer aussi longtemps que le live est actif, sans aucune limite de durée." },
    ],
  },
  {
    category: 'Téléchargement de Vidéos',
    items: [
      { q: "Comment télécharger une vidéo TikTok ?", a: "Copiez l'URL de la vidéo depuis TikTok, collez-la dans la page 'Télécharger Vidéos' et cliquez sur Télécharger. La vidéo se télécharge en MP4." },
      { q: "Puis-je télécharger une vidéo sans filigrane ?", a: "Oui. TikCapture propose deux options : sans filigrane (HD) et avec filigrane TikTok d'origine." },
      { q: "Puis-je télécharger des vidéos privées ?", a: "Non. TikCapture ne peut télécharger que les vidéos publiques. Les vidéos privées ou réservées aux amis ne sont pas accessibles." },
      { q: "La qualité est-elle identique à l'original ?", a: "Oui. TikCapture télécharge le fichier source directement depuis les serveurs TikTok, sans recompression." },
    ],
  },
  {
    category: 'Confidentialité & Données',
    items: [
      { q: "Mes vidéos sont-elles stockées sur vos serveurs ?", a: "Non. Les fichiers enregistrés sont générés localement dans votre navigateur. Les vidéos téléchargées transitent via notre proxy mais ne sont pas conservées." },
      { q: "Quelles données collectez-vous ?", a: "TikCapture ne collecte aucune donnée personnelle. Consultez notre politique de confidentialité pour plus de détails." },
    ],
  },
]

function FAQItem({ q, a }: { q: string; a: string }) {
  const [open, setOpen] = useState(false)
  return (
    <button
      onClick={() => setOpen(o => !o)}
      className="w-full text-left rounded-xl glass border border-white/10 px-5 py-4 transition-all hover:border-white/20 focus:outline-none"
    >
      <div className="flex items-center justify-between gap-4">
        <span className="text-sm font-medium text-foreground">{q}</span>
        <ChevronDown className={cn('w-4 h-4 text-muted-foreground shrink-0 transition-transform duration-300', open && 'rotate-180')} />
      </div>
      {open && <p className="mt-3 text-sm text-foreground leading-relaxed animate-fade-in-up">{a}</p>}
    </button>
  )
}

export function FAQ() {
  useSeoMeta({
    title:         'FAQ – Questions fréquentes sur TikCapture',
    description:   'Trouvez les réponses à toutes vos questions sur TikCapture : enregistrement de lives TikTok, téléchargement de vidéos, confidentialité et plus encore.',
    canonical:     `${BASE_URL}/faq`,
    ogTitle:       'FAQ TikCapture – Toutes vos questions, toutes les réponses',
    ogDescription: 'Comment enregistrer un live TikTok ? Comment télécharger une vidéo sans filigrane ? Retrouvez toutes les réponses dans notre FAQ complète.',
    ogImage:       `${BASE_URL}/og-image.png`,
  })

  return (
    <main className="relative z-10 pt-24 md:pt-32 pb-20">
      <div className="mx-auto px-4 sm:px-6 w-full max-w-3xl">

        <div className="text-center mb-14 animate-fade-in">
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/10 mb-6">
            <HelpCircle className="w-4 h-4 text-[#FF0050]" />
            <span className="text-sm text-muted-foreground">Questions fréquentes</span>
          </div>
          <h1 className="text-4xl sm:text-5xl font-black mb-4 tracking-tight">
            Aide &amp; <span className="gradient-text">FAQ</span>
          </h1>
          <p className="text-base md:text-lg text-muted max-w-xl mx-auto leading-relaxed">
            Trouvez rapidement les réponses à vos questions sur TikCapture.
          </p>
        </div>

        <div className="space-y-10">
          {FAQS.map((section) => (
            <section key={section.category}>
              <h2 className="text-xs font-semibold uppercase tracking-widest text-muted-foreground/50 mb-4 pl-1">
                {section.category}
              </h2>
              <div className="space-y-2">
                {section.items.map((item) => <FAQItem key={item.q} q={item.q} a={item.a} />)}
              </div>
            </section>
          ))}
        </div>

        <div className="mt-16 text-center rounded-2xl glass border border-white/10 p-10 glow-pink">
          <h2 className="text-xl font-bold text-foreground mb-2">Vous n'avez pas trouvé votre réponse ?</h2>
          <p className="text-muted-foreground text-sm mb-6">Contactez-nous, nous répondons sous 24h.</p>
          <Link
            to="/contact"
            className="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-[#FF0050] hover:bg-[#e0004a] text-white font-semibold text-sm transition-all hover:scale-105"
          >
            Nous contacter <ArrowRight className="w-4 h-4" />
          </Link>
        </div>

      </div>
    </main>
  )
}