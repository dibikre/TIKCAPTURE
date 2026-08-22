import { useState } from 'react'
import {
  Search,
  Radio,
  Settings2,
  CircleDot,
  Download,
  ChevronDown,
  ArrowRight,
  Zap,
  Shield,
  Clock,
  MonitorPlay,
} from 'lucide-react'
import { useNavigate } from 'react-router-dom'   // ← ajout
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
    icon: <Search className="w-5 h-5" />,
    title: 'Recherchez un utilisateur TikTok',
    description:
      "Entrez le nom d'utilisateur TikTok dans la barre de recherche. Vous pouvez utiliser le format @username, une URL complète tiktok.com/@username, ou simplement le nom sans @.",
    tip: "Astuce : collez directement l'URL du profil TikTok pour aller plus vite.",
    imageUrl: '/plateformes/etapes/etape1-chercher-createur.webp',
  },
  {
    number: 2,
    icon: <Radio className="w-5 h-5" />,
    title: 'Vérifiez que le live est actif',
    description:
      "TikCapture détecte automatiquement si l'utilisateur est en live. Vous verrez le nombre de spectateurs, le titre du live et un aperçu en temps réel.",
    tip: "Si l'utilisateur n'est pas en live, revenez plus tard ou activez les notifications TikTok.",
    imageUrl: '/plateformes/etapes/etape1-chercher-createur.webp',
  },
  {
    number: 3,
    icon: <Settings2 className="w-5 h-5" />,
    title: 'Choisissez la qualité',
    description:
      "Sélectionnez la qualité d'enregistrement selon vos besoins : de 360p léger jusqu'à la meilleure qualité disponible en HD. Vous pouvez aussi définir une durée maximale.",
    tip: 'La qualité "Meilleure" utilise le flux HLS natif de TikTok pour une fidélité optimale.',
    imageUrl: '/plateformes/etapes/etape2-selection-de-qualite.webp',
  },
  {
    number: 4,
    icon: <CircleDot className="w-5 h-5" />,
    title: "Lancez l'enregistrement",
    description:
      'Cliquez sur "Enregistrer" et TikCapture capture le flux en temps réel directement dans votre navigateur. Aucune installation requise, aucun logiciel tiers.',
    tip: "Gardez l'onglet actif pendant l'enregistrement pour de meilleures performances.",
    imageUrl: '/plateformes/etapes/etape3-enregistrer.webp',
  },
  {
    number: 5,
    icon: <Download className="w-5 h-5" />,
    title: 'Téléchargez votre vidéo',
    description:
      "Stoppez l'enregistrement quand vous le souhaitez. Le fichier MP4 se télécharge automatiquement dans votre dossier de téléchargements. Sans watermark, sans compression.",
    tip: "Les fichiers sont générés côté client : vos vidéos ne transitent jamais par nos serveurs.",
    imageUrl: '/plateformes/etapes/etape3-enregistrer.webp',
  },
]

const FAQS: FAQ[] = [
  { question: 'Est-ce totalement gratuit ?', answer: 'Oui, TikCapture est 100 % gratuit. Aucun abonnement, aucune limite de durée, aucune carte bancaire requise.' },
  { question: 'Dois-je installer une extension ou un logiciel ?', answer: "Non. Tout fonctionne directement dans votre navigateur web. Aucune installation n'est nécessaire." },
  { question: 'Quelle qualité vidéo puis-je attendre ?', answer: "La qualité dépend du flux TikTok de l'utilisateur. TikCapture récupère le meilleur flux disponible, généralement entre 480p et 1080p." },
  { question: "L'utilisateur TikTok sait-il que j'enregistre ?", answer: "Non. TikCapture lit uniquement le flux public du live, exactement comme n'importe quel spectateur. Aucune notification n'est envoyée." },
  { question: 'Où est stockée ma vidéo ?', answer: "Votre vidéo est générée localement dans votre navigateur et téléchargée directement sur votre appareil. Elle ne passe jamais par nos serveurs." },
  { question: 'Puis-je enregistrer plusieurs lives en simultané ?', answer: 'Vous pouvez ouvrir TikCapture dans plusieurs onglets pour enregistrer plusieurs lives en même temps.' },
]

const FEATURES = [
  { icon: <Zap className="w-5 h-5 text-[#FF0050]" />, title: 'Instantané', desc: 'Démarrez en moins de 10 secondes' },
  { icon: <Shield className="w-5 h-5 text-[#00F2EA]" />, title: 'Privé', desc: 'Vos vidéos restent sur votre appareil' },
  { icon: <Clock className="w-5 h-5 text-[#FF0050]" />, title: 'Sans limite', desc: "Durée d'enregistrement illimitée" },
  { icon: <MonitorPlay className="w-5 h-5 text-[#00F2EA]" />, title: 'Multi-qualité', desc: 'De 360p à la HD native' },
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
      {open && (
        <p className="mt-3 text-sm text-foreground leading-relaxed animate-fade-in-up">
          {faq.answer}
        </p>
      )}
    </button>
  )
}

function StepCard({ step, index }: { step: Step; index: number }) {
  return (
    <div
      className="flex flex-col md:flex-row gap-6 md:gap-10 items-start animate-fade-in-up"
      style={{ animationDelay: `${index * 0.08}s` }}
    >
      <div className="flex-1 min-w-0 space-y-4">
        <div className="flex items-center gap-3">
          <div className="flex items-center justify-center w-9 h-9 rounded-full bg-[#FF0050]/15 border border-[#FF0050]/30 text-[#FF0050] shrink-0">
            {step.icon}
          </div>
          <span className="text-5xl font-black leading-none select-none tabular-nums" style={{ color: 'rgba(255,255,255,0.07)' }}>
            0{step.number}
          </span>
        </div>

        <h3 className="text-xl md:text-2xl font-bold text-foreground leading-snug">
          {step.title}
        </h3>

        <p className="text-muted-foreground leading-relaxed text-sm md:text-base">
          {step.description}
        </p>

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

export function HowToRecord() {   // ← suppression de la prop onNavigateHome
  const navigate = useNavigate()  // ← useNavigate à la place

  useSeoMeta({
    title:         'Comment enregistrer un live TikTok gratuitement – TikCapture',
    description:   "Guide complet en 5 étapes pour enregistrer n'importe quel live TikTok en haute qualité, sans installation ni inscription. Directement depuis votre navigateur.",
    canonical:     `${BASE_URL}/comment-enregistrer`,
    ogTitle:       'How to Record TikTok Live – Complete Guide 2026',
    ogDescription: "Capturez n'importe quel live TikTok en HD en moins de 30 secondes. Guide pas à pas, gratuit, sans logiciel à installer.",
    ogImage:       `${BASE_URL}/og-image.png`,
  })

  return (
    <main className="relative z-10 pt-24 md:pt-32 pb-20">
      <HowToStructuredData 
        name="Comment enregistrer un live TikTok"
        description="Guide complet en 5 étapes pour enregistrer n'importe quel live TikTok en haute qualité, sans installation ni inscription."
        steps={STEPS.map(s => ({
          name: s.title,
          text: s.description
        }))}
      />
      <div className="mx-auto px-4 sm:px-6 w-full max-w-3xl">

        {/* Hero */}
        <div className="text-center mb-14 md:mb-20 animate-fade-in">
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/10 mb-6">
            <MonitorPlay className="w-4 h-4 text-[#FF0050]" />
            <span className="text-sm text-muted-foreground">Guide complet · 5 étapes</span>
          </div>

          <h1 className="text-4xl sm:text-5xl md:text-6xl font-display font-black mb-5 tracking-tight leading-tight uppercase">
            Comment{' '}
            <span className="gradient-text">enregistrer</span>
            <br />
            un live TikTok
          </h1>

          <p className="text-base md:text-lg text-muted max-w-xl mx-auto leading-relaxed">
            En moins de 30 secondes, capturez n'importe quel live TikTok en haute qualité —
            directement depuis votre navigateur, sans installation.
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
            <CircleDot className="w-7 h-7 text-[#FF0050]" />
          </div>
          <h2 className="text-2xl md:text-3xl font-bold text-foreground mb-3">Prêt à enregistrer ?</h2>
          <p className="text-muted-foreground mb-8 max-w-sm mx-auto text-sm md:text-base">
            Lancez-vous maintenant. C'est gratuit, instantané et aucune inscription n'est requise.
          </p>
          <button
            onClick={() => navigate('/')}   // ← navigate('/')
            className="inline-flex items-center gap-2 px-8 py-3.5 rounded-full bg-[#FF0050] hover:bg-[#e0004a] text-white font-semibold transition-all duration-200 hover:scale-105 active:scale-95 shadow-lg shadow-[#FF0050]/30"
          >
            Commencer maintenant
            <ArrowRight className="w-4 h-4" />
          </button>
        </div>

      </div>
    </main>
  )
}