import { Play, MonitorPlay, Download, ArrowRight, Clock, ChevronRight } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { useSeoMeta } from '../hooks/Useseometa'
import { BASE_URL } from '../lib/constants'

const TUTORIALS = [
  {
    category: 'Premiers pas',
    items: [
      { title: 'Enregistrer votre premier live TikTok', duration: '2 min', desc: "Guide pas-à-pas pour capturer un live TikTok en haute qualité depuis votre navigateur.", icon: <Play className="w-5 h-5" /> },
      { title: 'Télécharger une vidéo TikTok sans filigrane', duration: '1 min', desc: "Comment obtenir une vidéo TikTok propre en MP4, sans logo TikTok.", icon: <Download className="w-5 h-5" /> },
    ],
  },
  {
    category: 'Fonctionnalités avancées',
    items: [
      { title: "Choisir la bonne qualité d'enregistrement", duration: '3 min', desc: "Comprendre les différences entre 360p, 720p et HD native pour choisir selon vos besoins.", icon: <MonitorPlay className="w-5 h-5" /> },
      { title: 'Enregistrer plusieurs lives en simultané', duration: '2 min', desc: "Astuce pour capturer plusieurs créateurs en même temps en utilisant plusieurs onglets.", icon: <Play className="w-5 h-5" /> },
      { title: 'Utiliser TikCapture sur mobile', duration: '2 min', desc: "Comment enregistrer et télécharger depuis un smartphone iOS ou Android.", icon: <MonitorPlay className="w-5 h-5" /> },
    ],
  },
  {
    category: 'Résolution de problèmes',
    items: [
      { title: 'La vidéo s\'ouvre au lieu de se télécharger', duration: '1 min', desc: "Solution simple pour forcer le téléchargement sur tous les navigateurs.", icon: <Download className="w-5 h-5" /> },
      { title: 'Live non détecté — que faire ?', duration: '2 min', desc: "Causes possibles et solutions quand TikCapture ne trouve pas de live actif.", icon: <Play className="w-5 h-5" /> },
    ],
  },
]

export function Tutorials() {
  const navigate = useNavigate()
  useSeoMeta({
    title:         'Tutoriels vidéo TikCapture – Guides pratiques pas à pas',
    description:   'Apprenez à utiliser TikCapture grâce à nos tutoriels vidéo : enregistrer un live TikTok, télécharger sans filigrane, résoudre les problèmes courants et plus.',
    canonical:     `${BASE_URL}/tutoriels-video`,
    ogTitle:       'Tutoriels TikCapture – Maîtrisez l\'enregistrement TikTok',
    ogDescription: 'Guides pratiques pour enregistrer des lives TikTok, télécharger des vidéos en HD et utiliser toutes les fonctionnalités de TikCapture.',
    ogImage:       `${BASE_URL}/og-image.png`,
  })

  return (
    <main className="relative z-10 pt-24 md:pt-32 pb-20">
      <div className="mx-auto px-4 sm:px-6 w-full max-w-3xl">

        <div className="text-center mb-14 animate-fade-in">
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/10 mb-6">
            <MonitorPlay className="w-4 h-4 text-[#FF0050]" />
            <span className="text-sm text-muted-foreground">Guides vidéo</span>
          </div>
          <h1 className="text-4xl sm:text-5xl font-black mb-4 tracking-tight">
            Tutoriels <span className="gradient-text">vidéo</span>
          </h1>
          <p className="text-base md:text-lg text-muted max-w-xl mx-auto leading-relaxed">
            Maîtrisez TikCapture en quelques minutes grâce à nos guides pratiques.
          </p>
        </div>

        <div className="space-y-10">
          {TUTORIALS.map((section) => (
            <section key={section.category}>
              <h2 className="text-xs font-semibold uppercase tracking-widest text-muted-foreground/50 mb-4 pl-1">{section.category}</h2>
              <div className="space-y-3">
                {section.items.map((item) => (
                  <div key={item.title} className="group flex items-start gap-4 p-5 rounded-xl glass border border-white/10 hover:border-white/20 transition-all cursor-default">
                    <div className="w-10 h-10 rounded-xl bg-[#FF0050]/15 border border-[#FF0050]/30 flex items-center justify-center text-[#FF0050] shrink-0">
                      {item.icon}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 mb-1">
                        <h3 className="font-semibold text-foreground text-sm">{item.title}</h3>
                        <span className="inline-flex items-center gap-1 text-[10px] text-muted-foreground shrink-0">
                          <Clock className="w-3 h-3" />{item.duration}
                        </span>
                      </div>
                      <p className="text-xs text-muted-foreground leading-relaxed">{item.desc}</p>
                    </div>
                    <ChevronRight className="w-4 h-4 text-muted-foreground shrink-0 mt-1 opacity-0 group-hover:opacity-100 transition-opacity" />
                  </div>
                ))}
              </div>
            </section>
          ))}
        </div>

        <div className="mt-16 text-center rounded-2xl glass border border-white/10 p-10 glow-pink">
          <h2 className="text-xl font-bold text-foreground mb-2">Prêt à commencer ?</h2>
          <p className="text-muted-foreground text-sm mb-6">Lancez-vous maintenant — c'est gratuit et sans inscription.</p>
          <button
            onClick={() => navigate('/')}
            className="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-[#FF0050] hover:bg-[#e0004a] text-white font-semibold text-sm transition-all hover:scale-105"
          >
            Commencer maintenant <ArrowRight className="w-4 h-4" />
          </button>
        </div>

      </div>
    </main>
  )
}