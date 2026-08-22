import { FileText } from 'lucide-react'
import { useSeoMeta } from '../hooks/Useseometa'
import { BASE_URL } from '../lib/constants'

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="space-y-3">
      <h2 className="text-xl font-bold text-foreground">{title}</h2>
      <div className="text-sm text-foreground leading-relaxed space-y-2">{children}</div>
    </section>
  )
}

export function CGV() {
  useSeoMeta({
    title:         'Conditions générales de vente – TikCapture',
    description:   'Conditions générales de vente de TikCapture. Service entièrement gratuit, sans transaction financière. Encadrement des éventuelles offres premium futures.',
    canonical:     `${BASE_URL}/cgv`,
    ogTitle:       'CGV – Conditions générales de vente TikCapture',
    ogDescription: 'TikCapture est un service gratuit. Consultez nos conditions générales de vente pour en savoir plus sur les éventuelles offres futures.',
    ogImage:       `${BASE_URL}/og-image.png`,
  })

  return (
    <main className="relative z-10 pt-24 md:pt-32 pb-20">
      <div className="mx-auto px-4 sm:px-6 w-full max-w-3xl">

        <div className="text-center mb-12 animate-fade-in">
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/10 mb-6">
            <FileText className="w-4 h-4 text-[#FF0050]" />
            <span className="text-sm text-muted-foreground">Dernière mise à jour : janvier 2026</span>
          </div>
          <h1 className="text-4xl sm:text-5xl font-black mb-4 tracking-tight">
            Conditions de <span className="gradient-text">vente</span>
          </h1>
        </div>

        <div className="rounded-2xl glass border border-white/10 p-6 sm:p-10 space-y-8 animate-fade-in-up">

          <div className="flex items-start gap-3 px-4 py-3 rounded-lg bg-[#00F2EA]/5 border border-[#00F2EA]/20">
            <span className="text-[#00F2EA] shrink-0 text-sm mt-0.5">💡</span>
            <p className="text-sm text-[#00F2EA]/80">TikCapture est un service entièrement gratuit. Aucun paiement n'est requis pour utiliser les fonctionnalités de base.</p>
          </div>

          <Section title="1. Objet">
            <p>Les présentes Conditions Générales de Vente (CGV) régissent les éventuelles transactions commerciales réalisées sur TikCapture (tikcapture.live).</p>
            <p>À ce jour, TikCapture propose un service gratuit sans transaction financière. Ces CGV ont vocation à encadrer d'éventuelles offres premium futures.</p>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="2. Service gratuit">
            <p>L'ensemble des fonctionnalités actuellement disponibles sur TikCapture est accessible gratuitement :</p>
            <ul className="list-disc list-inside space-y-1 pl-2">
              <li>Enregistrement de lives TikTok</li>
              <li>Téléchargement de vidéos TikTok</li>
              <li>Accès aux guides et tutoriels</li>
            </ul>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="3. Offres futures">
            <p>TikCapture se réserve le droit de proposer à l'avenir des fonctionnalités premium payantes. Le cas échéant, les présentes CGV seront mises à jour et les utilisateurs en seront informés.</p>
            <p>L'accès aux fonctionnalités gratuites ne sera jamais conditionné à un paiement.</p>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="4. Droit applicable">
            <p>Les présentes CGV sont soumises au droit français. Tout litige relatif à leur interprétation ou exécution relève de la compétence des tribunaux français.</p>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="5. Contact">
            <p>Pour toute question relative aux présentes CGV, contactez-nous via la page Contact.</p>
          </Section>

        </div>
      </div>
    </main>
  )
}