import { Shield } from 'lucide-react'
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

export function CGU() {
  useSeoMeta({
    title:         'CGU & Confidentialité – TikCapture',
    description:   "Conditions générales d'utilisation et politique de confidentialité de TikCapture. Utilisation du service, propriété intellectuelle, cookies et données personnelles.",
    canonical:     `${BASE_URL}/cgu`,
    ogTitle:       'CGU & Politique de confidentialité – TikCapture',
    ogDescription: "Consultez les conditions d'utilisation et la politique de confidentialité de TikCapture : collecte de données, cookies, droits des utilisateurs.",
    ogImage:       `${BASE_URL}/og-image.png`,
  })

  return (
    <main className="relative z-10 pt-24 md:pt-32 pb-20">
      <div className="mx-auto px-4 sm:px-6 w-full max-w-3xl">

        <div className="text-center mb-12 animate-fade-in">
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/10 mb-6">
            <Shield className="w-4 h-4 text-[#FF0050]" />
            <span className="text-sm text-muted-foreground">Dernière mise à jour : mars 2026</span>
          </div>
          <h1 className="text-4xl sm:text-5xl font-black mb-4 tracking-tight">
            CGU &amp; <span className="gradient-text">Confidentialité</span>
          </h1>
        </div>

        <div className="rounded-2xl glass border border-white/10 p-6 sm:p-10 space-y-8 animate-fade-in-up">

          <Section title="1. Présentation">
            <p>TikCapture (accessible à l'adresse tikcapture.live) est un service en ligne permettant d'enregistrer des lives en direct et de télécharger des vidéos publiques depuis diverses plateformes (TikTok, YouTube, Twitch, Kick, Bigo, DLive et autres).</p>
            <p>En utilisant TikCapture, vous acceptez les présentes Conditions Générales d'Utilisation (CGU).</p>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="2. Contenu publié sur TikCapture">
            <p>TikCapture publie des enregistrements de lives et vidéos issus de plateformes publiques. Ces contenus sont présentés à titre informatif et de replay uniquement.</p>
            <p><strong className="text-foreground">Droit de retrait :</strong> Tout créateur de contenu souhaitant faire retirer un enregistrement le concernant peut en faire la demande à tout moment en contactant notre équipe à l'adresse suivante :</p>
            <p className="font-semibold text-[#FF0050]">contact@tikcapture.live</p>
            <p>La demande doit préciser :</p>
            <ul className="list-disc list-inside space-y-1 pl-2">
              <li>Le nom d'utilisateur ou profil concerné</li>
              <li>Le lien ou identifiant de la vidéo à retirer</li>
              <li>La raison de la demande (optionnel)</li>
            </ul>
            <p>Toute demande de retrait sera traitée dans un délai de <strong className="text-foreground">72 heures ouvrées</strong>.</p>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="3. Utilisation du service">
            <p>TikCapture est destiné à un usage personnel et non commercial uniquement. En utilisant notre service, vous vous engagez à :</p>
            <ul className="list-disc list-inside space-y-1 pl-2">
              <li>Respecter les droits d'auteur des créateurs de contenu</li>
              <li>Ne pas utiliser le service à des fins illégales</li>
              <li>Ne pas revendre ou distribuer commercialement les contenus disponibles</li>
              <li>Ne pas tenter de contourner les mesures techniques de protection</li>
            </ul>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="4. Propriété intellectuelle">
            <p>Les contenus présentés sur TikCapture restent la propriété exclusive de leurs auteurs respectifs. TikCapture ne revendique aucun droit de propriété sur ces contenus.</p>
            <p>TikCapture n'est affilié à aucune des plateformes référencées (TikTok, YouTube, Twitch, Kick, Bigo, DLive). Ces marques sont la propriété de leurs détenteurs respectifs.</p>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="5. Politique de confidentialité">
            <p><strong className="text-foreground">Données collectées :</strong> TikCapture ne collecte aucune donnée personnelle identifiable pour les visiteurs. Aucune inscription n'est requise pour accéder aux contenus.</p>
            <p><strong className="text-foreground">Cookies :</strong> Nous utilisons des cookies techniques nécessaires au bon fonctionnement du service et des cookies analytiques anonymisés (Yandex Metrika) pour améliorer notre service.</p>
            <p><strong className="text-foreground">Données de navigation :</strong> Les URLs soumises pour lecture ou téléchargement transitent par nos serveurs mais ne sont pas conservées de manière permanente.</p>
            <p><strong className="text-foreground">Partage de données :</strong> Nous ne vendons ni ne partageons vos données avec des tiers à des fins commerciales.</p>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="6. Limitation de responsabilité">
            <p>TikCapture est fourni "tel quel", sans garantie d'aucune sorte. Nous ne pouvons être tenus responsables de :</p>
            <ul className="list-disc list-inside space-y-1 pl-2">
              <li>L'indisponibilité temporaire du service</li>
              <li>L'utilisation des contenus en violation des droits d'auteur par les utilisateurs</li>
              <li>Tout dommage résultant de l'utilisation du service</li>
              <li>L'exactitude ou la disponibilité des contenus tiers référencés</li>
            </ul>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="7. Modifications">
            <p>TikCapture se réserve le droit de modifier les présentes CGU à tout moment. Les modifications entrent en vigueur dès leur publication sur cette page.</p>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="8. Contact">
            <p>Pour toute question relative aux présentes CGU, une demande de retrait de contenu, ou tout autre sujet :</p>
            <p className="font-semibold text-[#FF0050]">contact@tikcapture.live</p>
          </Section>

        </div>
      </div>
    </main>
  )
}