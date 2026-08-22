import { useState } from 'react'
import {
  Link2,
  Download,
  ChevronDown,
  ArrowRight,
  Zap,
  Shield,
  WifiOff,
  FileVideo,
  Scissors,
  Search,
} from 'lucide-react'
import { Link } from 'react-router-dom'
import { cn } from '../lib/utils'
import { useSeoMeta } from '../hooks/Useseometa'
import { BASE_URL } from '../lib/constants'
import { HowToStructuredData } from './StructuredData'

interface Step {
  number: number
  icon: React.ReactNode
  title: string
  description: string
  tip?: string
  imageUrl: string
}

interface FAQ {
  question: string
  answer: string
}

// ─── Steps data ───────────────────────────────────────────────────────────────

const STEPS: Step[] = [
  {
    number: 1,
    icon: <Link2 className="w-5 h-5" />,
    title: "Copiez l'URL de la vidéo TikTok",
    description:
      "Ouvrez TikTok dans votre navigateur ou sur l'application mobile. Trouvez la vidéo que vous souhaitez télécharger, puis copiez son lien — via le bouton \"Partager\" → \"Copier le lien\".",
    tip: 'Tous les formats sont acceptés : URL complète, URL courte (vm.tiktok.com), ou avec des paramètres (?is_from_webapp=…).',
    imageUrl: '/plateformes/etapes/etape1-chercher-createur.webp',
  },
  {
    number: 2,
    icon: <Search className="w-5 h-5" />,
    title: "Collez l'URL dans TikCapture",
    description:
      "Rendez-vous sur la page \"Télécharger Vidéos\" de TikCapture. Collez l'URL dans le champ de recherche. TikCapture détecte et nettoie automatiquement l'URL.",
    tip: "L'URL est validée en temps réel — un indicateur vert confirme qu'elle est reconnue avant même de lancer la recherche.",
    imageUrl: '/plateformes/etapes/etape1-chercher-createur.webp',
  },
  {
    number: 3,
    icon: <FileVideo className="w-5 h-5" />,
    title: 'Consultez les informations de la vidéo',
    description:
      "TikCapture récupère automatiquement les métadonnées : miniature, description, hashtags, statistiques (vues, commentaires, partages), résolution et taille du fichier.",
    tip: "Les vidéos recommandées en bas de page vous permettent de télécharger d'autres vidéos similaires en un clic.",
    imageUrl: '/plateformes/etapes/etape2-selection-de-qualite.webp',
  },
  {
    number: 4,
    icon: <Download className="w-5 h-5" />,
    title: 'Choisissez votre version et téléchargez',
    description:
      "Deux options disponibles : \"Sans filigrane (HD)\" pour une vidéo propre en haute qualité, ou \"Avec filigrane\" avec le logo TikTok d'origine. Cliquez et la vidéo se télécharge directement.",
    tip: "Si la vidéo s'ouvre dans le navigateur au lieu de se télécharger, faites Ctrl+S (ou Cmd+S sur Mac) pour la sauvegarder.",
    imageUrl: '/plateformes/etapes/etape2-selection-de-qualite.webp',
  },
  {
    number: 5,
    icon: <Scissors className="w-5 h-5" />,
    title: 'Profitez de votre vidéo hors-ligne',
    description:
      'La vidéo est enregistrée dans votre dossier Téléchargements au format MP4. Lisible sur tous les appareils, partageable facilement, et conservée indéfiniment sans connexion.',
    tip: 'Sur mobile, appuyez longuement sur la vidéo dans le navigateur pour afficher l\'option "Enregistrer la vidéo".',
    imageUrl: '/plateformes/etapes/etape3-enregistrer.webp',
  },
]

const FAQS: FAQ[] = [
  { question: "Est-ce gratuit et sans inscription ?",                                   answer: "Oui, totalement gratuit et sans création de compte. Collez l'URL et téléchargez — c'est tout." },
  { question: "Puis-je télécharger des vidéos privées ?",                               answer: "Non. TikCapture ne peut télécharger que les vidéos publiques. Les vidéos privées ou réservées aux amis ne sont pas accessibles." },
  { question: "Pourquoi la vidéo s'ouvre dans le navigateur au lieu de se télécharger ?", answer: "Cela dépend de la configuration de votre navigateur. Appuyez sur Ctrl+S (ou Cmd+S sur Mac) pour sauvegarder la page vidéo. Sur mobile, appuyez longuement sur la vidéo." },
  { question: "La qualité est-elle identique à l'original TikTok ?",                   answer: "Oui. TikCapture télécharge le fichier source directement depuis les serveurs TikTok, sans recompression ni perte de qualité." },
  { question: "Combien de vidéos puis-je télécharger ?",                               answer: "Il n'y a aucune limite. Vous pouvez télécharger autant de vidéos que vous le souhaitez, sans restriction." },
  { question: "Mes téléchargements sont-ils stockés sur vos serveurs ?",               answer: "Non. Le fichier est transféré directement depuis TikTok vers votre appareil via notre proxy. Nous ne conservons aucune vidéo." },
]

const FEATURES = [
  { icon: <Zap      className="w-5 h-5 text-[#FF0050]" />, title: 'Instantané',     desc: 'Téléchargement en quelques secondes' },
  { icon: <Shield   className="w-5 h-5 text-[#00F2EA]" />, title: 'Sans watermark', desc: 'Vidéo propre sans logo TikTok' },
  { icon: <WifiOff  className="w-5 h-5 text-[#FF0050]" />, title: 'Hors-ligne',     desc: 'Regardez sans connexion' },
  { icon: <FileVideo className="w-5 h-5 text-[#00F2EA]" />, title: 'HD native',     desc: 'Qualité originale préservée' },
]

// ─── Sub-components ───────────────────────────────────────────────────────────

function FAQItem({ faq }: { faq: FAQ }) {
  const [open, setOpen] = useState(false)
  return (
    <button
      onClick={() => setOpen((o) => !o)}
      className="w-full text-left rounded-xl glass border border-white/10 px-5 py-4 transition-all hover:border-white/20 focus:outline-none"
    >
      <div className="flex items-center justify-between gap-4">
        <span className="text-sm font-medium text-foreground">{faq.question}</span>
        <ChevronDown className={cn('w-4 h-4 text-muted-foreground shrink-0 transition-transform duration-300', open && 'rotate-180')} />
      </div>
      {open && <p className="mt-3 text-sm text-foreground leading-relaxed animate-fade-in-up">{faq.answer}</p>}
    </button>
  )
}

function StepCard({ step, index }: { step: Step; index: number }) {
  return (
    <div className="flex flex-col md:flex-row gap-6 md:gap-10 items-start animate-fade-in-up" style={{ animationDelay: `${index * 0.08}s` }}>
      <div className="flex-1 min-w-0 space-y-4">
        <div className="flex items-center gap-3">
          <div className="flex items-center justify-center w-9 h-9 rounded-full bg-[#FF0050]/15 border border-[#FF0050]/30 text-[#FF0050] shrink-0">
            {step.icon}
          </div>
          <span className="text-5xl font-black leading-none select-none tabular-nums" style={{ color: 'rgba(255,255,255,0.07)' }}>
            0{step.number}
          </span>
        </div>
        <h3 className="text-xl md:text-2xl font-bold text-foreground leading-snug">{step.title}</h3>
        <p className="text-muted-foreground leading-relaxed text-sm md:text-base">{step.description}</p>
        {step.tip && (
          <div className="flex items-start gap-3 px-4 py-3 rounded-lg bg-[#00F2EA]/5 border border-[#00F2EA]/20">
            <span className="text-[#00F2EA] shrink-0 text-xs mt-0.5">💡</span>
            <p className="text-sm text-[#00F2EA]/80 leading-relaxed">{step.tip}</p>
          </div>
        )}
      </div>
      <div className="w-full md:w-80 shrink-0 relative group">
        <div className="absolute -inset-1 bg-linear-to-r from-[#FF0050] to-[#00F2EA] rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-500"></div>
        <div className="relative rounded-xl overflow-hidden border border-white/10 bg-card/50">
          <img 
            src={step.imageUrl} 
            alt={step.title}
            className="w-full h-auto object-cover"
            loading="lazy"
          />
        </div>
      </div>
    </div>
  )
}

// ─── Main Page ────────────────────────────────────────────────────────────────

export function HowToDownload() {
  useSeoMeta({
    title:         'Comment télécharger une vidéo TikTok sans filigrane – TikCapture',
    description:   "Guide complet en 5 étapes pour télécharger n'importe quelle vidéo TikTok en MP4 HD, sans filigrane et sans inscription. Rapide, gratuit, sans installation.",
    canonical:     `${BASE_URL}/comment-telecharger`,
    ogTitle:       'Comment télécharger une vidéo TikTok – Guide complet 2026',
    ogDescription: "Téléchargez n'importe quelle vidéo TikTok en haute qualité sans filigrane en moins de 30 secondes. Guide pas à pas, 100% gratuit.",
    ogImage:       `${BASE_URL}/og-image.png`,
  })

  return (
    <main className="relative z-10 pt-24 md:pt-32 pb-20">
      <HowToStructuredData 
        name="Comment télécharger une vidéo TikTok sans filigrane"
        description="Guide complet en 5 étapes pour télécharger n'importe quelle vidéo TikTok en MP4 HD, sans filigrane et sans inscription."
        steps={STEPS.map(s => ({
          name: s.title,
          text: s.description
        }))}
      />
      <div className="mx-auto px-4 sm:px-6 w-full max-w-3xl">

        {/* Hero */}
        <div className="text-center mb-14 md:mb-20 animate-fade-in">
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/10 mb-6">
            <Download className="w-4 h-4 text-[#FF0050]" />
            <span className="text-sm text-muted-foreground">Guide complet · 5 étapes</span>
          </div>
          <h1 className="text-4xl sm:text-5xl md:text-6xl font-display font-black mb-5 tracking-tight leading-tight uppercase">
            Comment{' '}
            <span className="gradient-text">télécharger</span>
            <br />
            une vidéo TikTok
          </h1>
          <p className="text-base md:text-lg text-muted max-w-xl mx-auto leading-relaxed">
            Téléchargez n'importe quelle vidéo TikTok en haute qualité, sans filigrane —
            en moins de 30 secondes, sans installation.
          </p>
          <div className="mt-10 grid grid-cols-2 md:grid-cols-4 gap-3">
            {FEATURES.map((f) => (
              <div key={f.title} className="flex flex-col items-center gap-2 px-3 py-4 rounded-xl glass border border-white/10">
                {f.icon}
                <span className="text-sm font-semibold text-foreground">{f.title}</span>
                <span className="text-xs text-muted-foreground text-center leading-snug">{f.desc}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Steps */}
        <section className="mb-24">
          {STEPS.map((step, i) => (
            <div key={step.number}>
              <StepCard step={step} index={i} />
              {i < STEPS.length - 1 && (
                <div className="my-8 md:my-12 flex items-center gap-4">
                  <div className="flex-1 h-px bg-linear-to-r from-transparent via-white/10 to-transparent" />
                  <div className="w-1.5 h-1.5 rounded-full bg-[#FF0050]/40" />
                  <div className="flex-1 h-px bg-linear-to-r from-transparent via-white/10 to-transparent" />
                </div>
              )}
            </div>
          ))}
        </section>

        {/* FAQ */}
        <section className="mb-20">
          <div className="text-center mb-10">
            <h2 className="text-2xl md:text-3xl font-bold text-foreground mb-3">Questions fréquentes</h2>
            <p className="text-muted-foreground text-sm md:text-base">Tout ce que vous devez savoir avant de commencer.</p>
          </div>
          <div className="space-y-3">
            {FAQS.map((faq) => <FAQItem key={faq.question} faq={faq} />)}
          </div>
        </section>

        {/* CTA */}
        <div className="text-center rounded-2xl glass border border-white/10 p-10 md:p-14 glow-pink animate-fade-in-up">
          <div className="w-14 h-14 mx-auto mb-6 rounded-2xl bg-[#FF0050]/15 border border-[#FF0050]/30 flex items-center justify-center">
            <Download className="w-7 h-7 text-[#FF0050]" />
          </div>
          <h2 className="text-2xl md:text-3xl font-bold text-foreground mb-3">Prêt à télécharger ?</h2>
          <p className="text-muted-foreground mb-8 max-w-sm mx-auto text-sm md:text-base">
            Lancez-vous maintenant. C'est gratuit, instantané et aucune inscription n'est requise.
          </p>
          <Link
            to="/tiktok-video"
            className="inline-flex items-center gap-2 px-8 py-3.5 rounded-full bg-[#FF0050] hover:bg-[#e0004a] text-white font-semibold transition-all duration-200 hover:scale-105 active:scale-95 shadow-lg shadow-[#FF0050]/30"
          >
            Télécharger une vidéo
            <ArrowRight className="w-4 h-4" />
          </Link>
        </div>

      </div>
    </main>
  )
}